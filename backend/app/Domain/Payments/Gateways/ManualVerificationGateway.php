<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Support\GatewayCheckout;
use App\Domain\Payments\Support\WebhookAuthenticator;

class ManualVerificationGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly WebhookAuthenticator $authenticator) {}

    public function charge(Payment $payment, array $options = []): array
    {
        return GatewayCheckout::start(
            $payment,
            'manual',
            'CASH',
            'Manual payment reference created. A staff member must confirm receipt.',
        );
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        return $this->authenticator->authenticate(
            provider: 'manual',
            payload: $payload,
            signature: $signature,
            referenceKeys: ['cash_desk_ref', 'transaction_reference', 'reference'],
            paidStatuses: ['paid', 'verified', 'completed', 'success'],
        );
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return GatewayCheckout::refund('manual', $amount, (string) $payment->currency);
    }
}
