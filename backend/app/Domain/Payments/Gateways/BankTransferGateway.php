<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Str;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function charge(Payment $payment, array $options = []): array
    {
        $ref = 'BANK-'.strtoupper(Str::random(10));

        return [
            'success' => true,
            'status' => 'pending',
            'transaction_reference' => $ref,
            'redirect_url' => null,
            'message' => 'Please transfer the exact amount to Commercial Bank of Ethiopia (CBE) Account: 1000234567890 or Awash Bank: 01320876543200 and attach slip reference.',
            'raw' => [
                'bank_accounts' => [
                    ['bank' => 'Commercial Bank of Ethiopia', 'account' => '1000234567890', 'name' => 'Campus Esports Ethiopia'],
                    ['bank' => 'Awash Bank', 'account' => '01320876543200', 'name' => 'Campus Esports Ethiopia'],
                ],
                'reference_code' => $ref,
            ],
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        $ref = $payload['reference_code'] ?? $payload['transaction_reference'] ?? null;
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
            'refund_reference' => 'BNK-REF-'.strtoupper(Str::random(8)),
            'message' => "Bank transfer refund of {$amount} {$payment->currency} marked for wire reversal.",
        ];
    }
}
