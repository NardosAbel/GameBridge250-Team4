<?php

namespace App\Domain\Payments\Support;

use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Str;

/**
 * Builds a pending checkout session.
 *
 * Sandbox mode is explicit and never claims a provider moved funds.
 * Production charge calls fail closed until a real provider verifier exists;
 * a configured merchant id alone does not enable live settlement.
 */
final class GatewayCheckout
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{success: bool, status: string, transaction_reference: ?string, redirect_url: ?string, message: ?string, raw: array<string, mixed>}
     */
    public static function start(Payment $payment, string $provider, string $prefix, string $message, array $raw = []): array
    {
        $sandbox = (bool) config('services.payments.sandbox');

        if (! $sandbox) {
            return [
                'success' => false,
                'status' => 'failed',
                'transaction_reference' => null,
                'redirect_url' => null,
                'message' => "{$provider} is disabled until live provider verification and transaction processing are implemented.",
                'raw' => [],
            ];
        }

        $reference = $prefix.'-'.strtoupper(Str::random(12));

        return [
            'success' => true,
            'status' => 'pending',
            'transaction_reference' => $reference,
            'redirect_url' => $sandbox ? url("/payments/sandbox/{$provider}/{$reference}") : null,
            'message' => $sandbox ? "Sandbox checkout created for {$provider}. No funds will move." : $message,
            'raw' => array_merge($raw, [
                'provider' => $provider,
                'sandbox' => $sandbox,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ]),
        ];
    }

    /**
     * @return array{success: bool, refund_reference: ?string, message: ?string}
     */
    public static function refund(string $provider, float $amount, string $currency): array
    {
        $configured = filled(config("services.payments.providers.{$provider}.merchant_id"));
        $sandbox = (bool) config('services.payments.sandbox');

        if ($sandbox) {
            return [
                'success' => true,
                'refund_reference' => 'SANDBOX-'.strtoupper(Str::random(10)),
                'message' => "Sandbox refund of {$amount} {$currency} recorded for {$provider}. No funds were moved.",
            ];
        }

        if (! $configured) {
            return [
                'success' => false,
                'refund_reference' => null,
                'message' => "{$provider} refund API is not configured. Settle this refund manually and keep the audit record.",
            ];
        }

        return [
            'success' => false,
            'refund_reference' => null,
            'message' => "{$provider} refund must be confirmed by the provider API before funds are marked settled.",
        ];
    }
}
