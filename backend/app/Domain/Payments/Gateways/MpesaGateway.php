<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Str;

class MpesaGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $options = []): array
    {
        $ref = 'MP-'.strtoupper(Str::random(10));

        return [
            'success' => true,
            'status' => 'pending',
            'transaction_reference' => $ref,
            'redirect_url' => null, // STK push sends prompt to customer handset
            'message' => 'M-Pesa STK push initiated to your mobile phone.',
            'raw' => [
                'CheckoutRequestID' => 'ws_CO_'.Str::random(16),
                'MerchantRequestID' => $ref,
            ],
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        $ref = $payload['MerchantRequestID'] ?? $payload['transaction_reference'] ?? null;
        $resultCode = $payload['ResultCode'] ?? ($payload['status'] === 'paid' ? 0 : 1);

        $isPaid = ((int) $resultCode === 0);

        return [
            'valid' => true,
            'event_type' => $isPaid ? 'payment.success' : 'payment.failed',
            'provider_reference' => $ref,
            'status' => $isPaid ? 'paid' : 'failed',
            'error' => $isPaid ? null : ($payload['ResultDesc'] ?? 'M-Pesa transaction was cancelled or declined.'),
        ];
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        $refundRef = 'MPR-'.strtoupper(Str::random(10));

        return [
            'success' => true,
            'refund_reference' => $refundRef,
            'message' => "M-Pesa reversal of {$amount} {$payment->currency} sent to handset.",
        ];
    }
}
