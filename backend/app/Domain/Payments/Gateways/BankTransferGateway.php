<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Support\GatewayCheckout;
use App\Domain\Payments\Support\WebhookAuthenticator;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly WebhookAuthenticator $authenticator) {}

    public function charge(Payment $payment, array $options = []): array
    {
        $accounts = config('services.payments.providers.bank_transfer.accounts', []);

        return GatewayCheckout::start(
            $payment,
            'bank_transfer',
            'BANK',
            'Bank transfer reference created. The buyer must use the configured settlement account.',
            ['accounts' => is_array($accounts) ? $accounts : []],
        );
    }

    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array
    {
        return $this->authenticator->authenticate(
            provider: 'bank_transfer',
            payload: $payload,
            signature: $signature,
            referenceKeys: ['reference_code', 'transaction_reference', 'reference'],
            paidStatuses: ['paid', 'verified', 'completed', 'success'],
        );
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return GatewayCheckout::refund('bank_transfer', $amount, (string) $payment->currency);
    }
}
