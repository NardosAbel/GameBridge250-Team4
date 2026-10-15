<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Str;

class CardPaymentGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $options = []): array
    {
        $ref = 'CARD-'.strtoupper(Str::random(12));

        return [
            'success' => true,
            'status' => 'pending',
            'transaction_reference' => $ref,
            'redirect_url' => "https://secure-card-gateway.et/checkout/{$ref}",
            'message' => 'Credit/Debit card 3D-Secure payment session created.',
            'raw' => [
                'session_id' => $ref,
                'provider' => 'card',
            ],
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        $ref = $payload['session_id'] ?? $payload['transaction_reference'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? ''));

        $isPaid = in_array($status, ['success', 'captured', 'paid'], true);

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
        $refundRef = 'CRD-REF-'.strtoupper(Str::random(8));

        return [
            'success' => true,
            'refund_reference' => $refundRef,
            'message' => "Card refund of {$amount} {$payment->currency} credited back to card.",
        ];
    }
}
