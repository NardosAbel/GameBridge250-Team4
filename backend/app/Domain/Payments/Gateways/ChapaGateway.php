<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Support\GatewayCheckout;
use App\Domain\Payments\Support\WebhookAuthenticator;

class ChapaGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly WebhookAuthenticator $authenticator) {}

    public function charge(Payment $payment, array $options = []): array
    {
        return GatewayCheckout::start(
            $payment,
            'chapa',
            'CHAPA',
            'Chapa checkout initialized.',
        );
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        return $this->authenticator->authenticate(
            provider: 'chapa',
            payload: $payload,
            signature: $signature,
            referenceKeys: ['tx_ref', 'reference', 'provider_reference'],
            paidStatuses: ['success', 'completed', 'paid'],
        );
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return GatewayCheckout::refund('chapa', $amount, (string) $payment->currency);
    }
}
