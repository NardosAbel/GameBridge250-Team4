<?php

namespace App\Domain\Payments\Services;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Payments\Gateways\PaymentGatewayManager;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Domain\Ticketing\Actions\ReleaseTicketInventoryAction;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected NotificationService $notificationService,
        protected ReleaseTicketInventoryAction $releaseTicketInventoryAction,
    ) {}

    /**
     * Request a refund for a ticket order.
     */
    public function requestRefund(
        TicketOrder $order,
        User $requester,
        float $amount,
        string $reason,
        ?string $reasonCategory = 'attendee_request'
    ): Refund {
        if ($order->user_id !== $requester->id && ! $requester->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            throw new AuthorizationException('You can only request refunds for your own orders.');
        }

        if ($order->status !== 'completed' && $order->status !== 'paid' && $order->status !== 'partially_refunded') {
            throw new InvalidArgumentException("Cannot refund order with status '{$order->status}'.");
        }

        if ((float) $order->total_amount <= 0) {
            throw new InvalidArgumentException('Free orders cannot be refunded.');
        }

        if ($amount <= 0 || $amount > $order->total_amount) {
            throw new InvalidArgumentException("Refund amount must be between 0 and order total ({$order->total_amount}).");
        }

        $payment = $order->payments()->where('status', 'paid')->latest()->first();
        if ($payment === null) {
            throw new InvalidArgumentException('This order has no settled payment to refund.');
        }

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'ticket_order_id' => $order->id,
            'requested_by_id' => $requester->id,
            'amount' => $amount,
            'reason' => $reason,
            'reason_category' => $reasonCategory,
            'status' => 'pending',
        ]);

        AuditLogger::log(
            action: 'refund.requested',
            user: $requester,
            entityType: 'refund',
            entityId: $refund->id,
            metadata: [
                'order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
            ]
        );

        return $refund;
    }

    /**
     * Organizer or Administrator approves or rejects the refund request.
     */
    public function processRefund(
        Refund $refund,
        User $processor,
        bool $approve,
        ?string $rejectionReason = null
    ): Refund {
        $order = $refund->order;
        $event = $order?->event;
        $isAuthorized = $processor->hasRole(RoleEnum::SUPER_ADMIN->value)
            || ($event && $event->organizer_id === $processor->id)
            || ($event && $event->venue && $event->venue->isStaff($processor, 'manager'));

        if (! $isAuthorized) {
            throw new AuthorizationException('Only event organizers, venue managers, or admins can process refunds.');
        }

        if ($refund->status !== 'pending') {
            throw new InvalidArgumentException("Refund request is already {$refund->status}.");
        }

        return DB::transaction(function () use ($refund, $processor, $approve, $rejectionReason, $order) {
            if (! $approve) {
                $refund->update([
                    'status' => 'rejected',
                    'processed_by_id' => $processor->id,
                    'rejection_reason' => $rejectionReason ?? 'Organizer rejected refund request.',
                    'processed_at' => now(),
                ]);

                AuditLogger::log(
                    action: 'refund.rejected',
                    user: $processor,
                    entityType: 'refund',
                    entityId: $refund->id,
                    metadata: [
                        'rejection_reason' => $refund->rejection_reason,
                    ]
                );

                if ($refund->requestedBy) {
                    $this->notificationService->send(
                        $refund->requestedBy,
                        new GenericNotification(
                            title: 'Refund Request Rejected',
                            message: "Your refund request for order #{$order->uuid} was rejected: {$refund->rejection_reason}",
                            extraData: ['refund_id' => $refund->id]
                        )
                    );
                }

                return $refund;
            }

            // 1. Process via Payment Gateway if payment exists
            $payment = $refund->payment;
            if ($payment && $payment->provider) {
                $gateway = $this->gatewayManager->driver($payment->provider);
                $providerResult = $gateway->refund($payment, (float) $refund->amount, $refund->reason);
                if (! (bool) ($providerResult['success'] ?? false)) {
                    throw new InvalidArgumentException('The payment provider did not confirm this refund. Tickets were not changed.');
                }
            }

            if ($order) {
                $order->tickets()->update(['status' => 'refunded']);

                $isFullRefund = ((float) $refund->amount >= (float) $order->total_amount);
                if ($isFullRefund) {
                    $this->releaseTicketInventoryAction->execute($order);
                }
                $order->update([
                    'status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                ]);
            }

            // 3. Update payment status
            if ($payment) {
                $payment->update([
                    'status' => ($refund->amount >= $payment->amount) ? 'refunded' : 'partially_refunded',
                ]);
            }

            $refund->update([
                'status' => 'approved',
                'processed_by_id' => $processor->id,
                'processed_at' => now(),
            ]);

            AuditLogger::log(
                action: 'refund.approved',
                user: $processor,
                entityType: 'refund',
                entityId: $refund->id,
                metadata: [
                    'amount' => $refund->amount,
                    'order_id' => $order?->id,
                    'provider_settled' => true,
                ]
            );

            // Notify attendee
            if ($refund->requestedBy) {
                $this->notificationService->send(
                    $refund->requestedBy,
                    new GenericNotification(
                        title: 'Refund Approved',
                        message: "Your refund of {$refund->amount} ETB for order #{$order->uuid} has been approved and processed.",
                        extraData: ['refund_id' => $refund->id]
                    )
                );
            }

            return $refund;
        });
    }
}
