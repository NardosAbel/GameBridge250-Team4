<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve the payment gateway driver by provider name.
     */
    public function driver(string $provider): PaymentGatewayInterface
    {
        return match (strtolower($provider)) {
            'telebirr' => app(TelebirrGateway::class),
            'chapa' => app(ChapaGateway::class),
            'mpesa', 'm-pesa' => app(MpesaGateway::class),
            'card', 'stripe' => app(CardPaymentGateway::class),
            'bank_transfer', 'cbe' => app(BankTransferGateway::class),
            'manual', 'cash' => app(ManualVerificationGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment provider: {$provider}"),
        };
    }
}
