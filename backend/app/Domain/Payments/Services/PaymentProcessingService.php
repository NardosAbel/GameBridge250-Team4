<?php

namespace App\Domain\Payments\Services;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Payments\Gateways\PaymentGatewayManager;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentWebhook;
use App\Domain\Ticketing\Actions\IssueTicketsAction;
use App\Domain\Ticketing\Actions\ReleaseTicketInventoryAction;
use App\Domain\Ticketing\Models\TicketOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentProcessingService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected IssueTicketsAction $issueTicketsAction,
        protected ReleaseTicketInventoryAction $releaseTicketInventoryAction,
    ) {}

    /**
     * Initiate a payment transaction for a ticket order.
     *
     * @param  array<string, mixed>  $options
     */
    public function initiatePayment(TicketOrder $order, string $provider, array $options = []): Payment
    {
        if ($order->status !== 'pending') {
            throw new InvalidArgumentException('Only a pending order can start a payment.');
        }

        return DB::transaction(function () use ($order, $provider, $options) {
            $order = TicketOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
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

            if (($result['success'] ?? false) !== true || empty($result['transaction_reference'])) {
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'gateway_message' => $result['message'] ?? 'Charge was rejected.',
                    ]),
                ]);

                throw new InvalidArgumentException($result['message'] ?? 'Payment provider rejected the charge.');
            }

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

            return $payment->fresh() ?? $payment;
        });
    }

    /**
     * Process an incoming webhook with signature verification and idempotency check.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function processWebhook(string $provider, array $payload, ?string $signature = null, array $headers = []): PaymentWebhook
    {
        try {
            $gateway = $this->gatewayManager->driver($provider);
        } catch (InvalidArgumentException $exception) {
            return PaymentWebhook::create([
                'provider' => strtolower($provider),
                'event_type' => 'payment.unknown',
                'provider_reference' => null,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }

        $verification = $gateway->verifyWebhook($payload, $signature, $headers);
        $providerRef = $verification['provider_reference'] ?? null;

        if (! ($verification['valid'] ?? false)) {
            return PaymentWebhook::create([
                'provider' => strtolower($provider),
                'event_type' => $verification['event_type'] ?? 'payment.unknown',
                'provider_reference' => $providerRef,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'failed',
                'error_message' => $verification['error'] ?? 'Invalid webhook signature.',
            ]);
        }

        return DB::transaction(function () use ($provider, $payload, $signature, $verification, $providerRef) {
            $existingWebhook = PaymentWebhook::where('provider', strtolower($provider))
                ->where('provider_reference', $providerRef)
                ->where('status', 'processed')
                ->lockForUpdate()
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

            $payment = Payment::query()
                ->where('provider', strtolower($provider))
                ->where('provider_reference', $providerRef)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return PaymentWebhook::create([
                    'provider' => strtolower($provider),
                    'event_type' => $verification['event_type'],
                    'provider_reference' => $providerRef,
                    'payload' => $payload,
                    'signature' => $signature,
                    'status' => 'failed',
                    'error_message' => 'No matching payment for this provider reference.',
                ]);
            }

            if ($verification['status'] === 'paid' && ! $this->amountMatches($payment, $verification)) {
                return PaymentWebhook::create([
                    'provider' => strtolower($provider),
                    'event_type' => 'payment.unknown',
                    'provider_reference' => $providerRef,
                    'payload' => $payload,
                    'signature' => $signature,
                    'status' => 'failed',
                    'error_message' => 'Webhook amount or currency does not match the payment.',
                ]);
            }

            $webhook = PaymentWebhook::create([
                'provider' => strtolower($provider),
                'event_type' => $verification['event_type'],
                'provider_reference' => $providerRef,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'processed',
            ]);

            $payment->update([
                'status' => $verification['status'],
                'webhook_payload' => $payload,
                'paid_at' => ($verification['status'] === 'paid') ? now() : null,
            ]);

            if ($verification['status'] === 'paid') {
                $this->fulfill($payment);
            } elseif ($verification['status'] === 'failed' && $payment->order && $payment->order->status === 'pending') {
                $this->releaseTicketInventoryAction->execute($payment->order);
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

            return $webhook;
        });
    }

    /**
     * Reconcile a payment from an already verified webhook without issuing tickets twice.
     */
    public function reconcilePayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'paid') {
                $this->fulfill($payment);

                return $payment->fresh() ?? $payment;
            }

            $matchingWebhook = PaymentWebhook::where('provider', $payment->provider)
                ->where('provider_reference', $payment->provider_reference)
                ->where('status', 'processed')
                ->where('event_type', 'payment.success')
                ->first();

            if ($matchingWebhook) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at ?? now(),
                ]);
                $this->fulfill($payment);
            }

            return $payment->fresh() ?? $payment;
        });
    }

    /**
     * @param  array{amount?: float|null, currency?: string|null}  $verification
     */
    private function amountMatches(Payment $payment, array $verification): bool
    {
        if (! isset($verification['amount']) || $verification['amount'] === null) {
            return false;
        }

        // Compare minor units (cents) so float noise cannot widen the match window.
        $paymentCents = (int) round(((float) $payment->amount) * 100);
        $webhookCents = (int) round(((float) $verification['amount']) * 100);
        $amountsMatch = $paymentCents === $webhookCents;
        $currency = $verification['currency'] ?? null;
        $currencyMatches = is_string($currency)
            && strtoupper($currency) === strtoupper((string) $payment->currency);

        return $amountsMatch && $currencyMatches;
    }

    private function fulfill(Payment $payment): void
    {
        $order = $payment->order;
        if (! $order) {
            return;
        }

        if (in_array($order->status, ['refunded', 'partially_refunded', 'cancelled'], true)) {
            return;
        }

        $this->issueTicketsAction->execute($order);
        $order->update(['status' => 'completed']);
    }
}
