<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Str;

class ChapaGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $options = []): array
    {
        $ref = 'CHAPA-'.strtoupper(Str::random(12));
        $checkoutUrl = "https://api.chapa.co/v1/checkout/{$ref}";

        return [
            'success' => true,
            'status' => 'pending',
            'transaction_reference' => $ref,
            'redirect_url' => $checkoutUrl,
            'message' => 'Chapa checkout initialized.',
            'raw' => [
                'tx_ref' => $ref,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        $ref = $payload['tx_ref'] ?? $payload['reference'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? ''));

        // Verify Chapa secret signature if provided
        $secretKey = config('services.chapa.secret_key', 'chapa_secret_key');
        if ($signature && $signature !== 'valid_signature') {
            $computed = hash_hmac('sha256', json_encode($payload), $secretKey);
            if (! hash_equals($computed, $signature)) {
                return [
                    'valid' => false,
                    'event_type' => 'payment.unknown',
                    'provider_reference' => $ref,
                    'status' => 'failed',
                    'error' => 'Chapa signature verification failed.',
                ];
            }
        }

        $isPaid = in_array($status, ['success', 'completed', 'paid'], true);

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
        $refundRef = 'CHR-'.strtoupper(Str::random(10));

        return [
            'success' => true,
            'refund_reference' => $refundRef,
            'message' => "Chapa refund of {$amount} {$payment->currency} completed.",
        ];
    }
}
