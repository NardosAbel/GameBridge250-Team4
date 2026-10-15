<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Str;

class ManualVerificationGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $options = []): array
    {
        $ref = 'CASH-'.strtoupper(Str::random(8));

        return [
            'success' => true,
            'status' => 'pending',
            'transaction_reference' => $ref,
            'redirect_url' => null,
            'message' => 'Pay cash directly at venue gate or registration desk.',
            'raw' => [
                'cash_desk_ref' => $ref,
            ],
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        $ref = $payload['cash_desk_ref'] ?? $payload['transaction_reference'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? 'paid'));

        $isPaid = ($status === 'paid' || $status === 'verified');

        return [
            'valid' => true,
            'event_type' => $isPaid ? 'payment.success' : 'payment.failed',
            'provider_reference' => $ref,
            'status' => $isPaid ? 'paid' : 'failed',
            'error' => null,
        ];
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return [
            'success' => true,
            'refund_reference' => 'CASH-REF-'.strtoupper(Str::random(6)),
            'message' => "Cash refund of {$amount} {$payment->currency} authorized at gate desk.",
        ];
    }
}
