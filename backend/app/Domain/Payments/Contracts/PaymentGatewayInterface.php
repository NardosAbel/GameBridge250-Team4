<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initiate or process a payment charge with provider.
     *
     * @param  array<string, mixed>  $options
     * @return array{success: bool, status: string, transaction_reference: ?string, redirect_url: ?string, message: ?string, raw: array}
     */
    public function charge(Payment $payment, array $options = []): array;

    /**
     * Verify authenticity of an incoming provider webhook.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array{valid: bool, event_type: string, provider_reference: ?string, status: string, error: ?string}
     */
    public function verifyWebhook(array $payload, ?string $signature = null, array $headers = []): array;

    /**
     * Process a full or partial refund.
     *
     * @return array{success: bool, refund_reference: ?string, message: ?string}
     */
    public function refund(Payment $payment, float $amount, string $reason): array;
}
