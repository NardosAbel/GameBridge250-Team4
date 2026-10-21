<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Support\GatewayCheckout;
use App\Domain\Payments\Support\WebhookAuthenticator;

class MpesaGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly WebhookAuthenticator $authenticator) {}

    public function charge(Payment $payment, array $options = []): array
    {
        return GatewayCheckout::start(
            $payment,
            'mpesa',
            'MP',
            'M-Pesa checkout initialized.',
        );
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        return $this->authenticator->authenticate(
            provider: 'mpesa',
            payload: $payload,
            signature: $signature,
            referenceKeys: ['MerchantRequestID', 'transaction_reference', 'reference'],
            paidStatuses: ['0', 'success', 'paid', 'completed'],
        );
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return GatewayCheckout::refund('mpesa', $amount, (string) $payment->currency);
    }
}
