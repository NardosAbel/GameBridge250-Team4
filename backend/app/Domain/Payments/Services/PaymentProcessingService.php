<?php

namespace App\Domain\Payments\Services;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Payments\Gateways\PaymentGatewayManager;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentWebhook;
use App\Domain\Ticketing\Actions\IssueTicketsAction;
use App\Domain\Ticketing\Models\TicketOrder;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected IssueTicketsAction $issueTicketsAction
    ) {}

    /**
     * Initiate a payment transaction for a ticket order.
     *
     * @param  array<string, mixed>  $options
     */
    public function initiatePayment(TicketOrder $order, string $provider, array $options = []): Payment
    {
        return DB::transaction(function () use ($order, $provider, $options) {
            $gateway = $this->gatewayManager->driver($provider);

            $payment = Payment::create([
                'ticket_order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->total_amount,
                'fee' => $order->service_fee,
                'currency' => $order->currency,
                'provider' => strtolower($provider),
                'status' => 'pending',
                'metadata' => $options,
            ]);

            $result = $gateway->charge($payment, $options);

            $payment->update([
                'provider_reference' => $result['transaction_reference'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'redirect_url' => $result['redirect_url'],
                    'gateway_raw' => $result['raw'],
                ]),
            ]);

            $order->update([
                'payment_method' => strtolower($provider),
            ]);

            AuditLogger::log(
                action: 'payment.initiated',
                user: $order->user,
                entityType: 'payment',
                entityId: $payment->id,
                metadata: [
                    'order_id' => $order->id,
                    'provider' => $provider,
                    'amount' => $payment->amount,
                ]
            );

            return $payment;
        });
    }

    /**
     * Process an incoming webhook with signature verification and idempotency check.
     */
    public function processWebhook(string $provider, array $payload, ?string $signature = null, array $headers = []): PaymentWebhook
    {
        $gateway = $this->gatewayManager->driver($provider);
        $verification = $gateway->verifyWebhook($payload, $signature, $headers);

        $providerRef = $verification['provider_reference'] ?? ($payload['provider_reference'] ?? null);

        // 1. Signature Check
        if (! $verification['valid']) {
            return PaymentWebhook::create([
                'provider' => strtolower($provider),
                'event_type' => $verification['event_type'],
                'provider_reference' => $providerRef,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'failed',
                'error_message' => $verification['error'] ?? 'Invalid webhook signature.',
            ]);
        }

        return DB::transaction(function () use ($provider, $payload, $signature, $verification, $providerRef) {
            // 2. Idempotency Check: Prevent duplicate webhook replay attacks
            $existingWebhook = PaymentWebhook::where('provider', strtolower($provider))
                ->where('provider_reference', $providerRef)
                ->where('status', 'processed')
                ->first();

            if ($existingWebhook) {
                return PaymentWebhook::create([
                    'provider' => strtolower($provider),
                    'event_type' => $verification['event_type'],
                    'provider_reference' => $providerRef,
                    'payload' => $payload,
                    'signature' => $signature,
                    'status' => 'duplicate',
                    'error_message' => 'Duplicate webhook event already processed.',
                ]);
            }

            // 3. Locate matching Payment record
            $payment = Payment::where(function ($q) use ($providerRef) {
                $q->where('provider_reference', $providerRef)
                    ->orWhere('uuid', $providerRef);
            })->first();

            $webhook = PaymentWebhook::create([
                'provider' => strtolower($provider),
                'event_type' => $verification['event_type'],
                'provider_reference' => $providerRef,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'processed',
            ]);

            if ($payment) {
                $payment->update([
                    'status' => $verification['status'],
                    'webhook_payload' => $payload,
                    'paid_at' => ($verification['status'] === 'paid') ? now() : null,
                ]);

                if ($verification['status'] === 'paid' && $payment->order) {
                    $payment->order->update(['status' => 'paid']);
                    // Issue active tickets with signed QR tokens
                    $this->issueTicketsAction->execute($payment->order);
                } elseif ($verification['status'] === 'failed' && $payment->order) {
                    $payment->order->update(['status' => 'failed']);
                }

                AuditLogger::log(
                    action: "payment.{$verification['status']}",
                    user: $payment->user,
                    entityType: 'payment',
                    entityId: $payment->id,
                    metadata: [
                        'provider' => $provider,
                        'provider_reference' => $providerRef,
                        'status' => $verification['status'],
                    ]
                );
            }

            return $webhook;
        });
    }

    /**
     * Reconcile payment with provider and update database state.
     */
    public function reconcilePayment(Payment $payment): Payment
    {
        if ($payment->status === 'paid') {
            return $payment;
        }

        // Check if matching successful webhook arrived
        $matchingWebhook = PaymentWebhook::where('provider', $payment->provider)
            ->where('provider_reference', $payment->provider_reference)
            ->where('status', 'processed')
            ->first();

        if ($matchingWebhook) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($payment->order && $payment->order->status !== 'completed') {
                $payment->order->update(['status' => 'completed']);
                $this->issueTicketsAction->execute($payment->order);
            }
        }

        return $payment;
    }
}
