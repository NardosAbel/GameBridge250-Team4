<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Support\GatewayCheckout;
use App\Domain\Payments\Support\WebhookAuthenticator;

class TelebirrGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly WebhookAuthenticator $authenticator) {}

    public function charge(Payment $payment, array $options = []): array
    {
        return GatewayCheckout::start(
            $payment,
            'telebirr',
            'TB',
            'Telebirr checkout initialized.',
        );
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        return $this->authenticator->authenticate(
            provider: 'telebirr',
            payload: $payload,
            signature: $signature,
            referenceKeys: ['outTradeNo', 'tradeNo', 'provider_reference', 'reference'],
            paidStatuses: ['success', 'paid', 'completed', '2'],
        );
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return GatewayCheckout::refund('telebirr', $amount, (string) $payment->currency);
    }
}
