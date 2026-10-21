<?php

namespace App\Domain\Payments\Support;

/**
 * Fail-closed HMAC verification for payment provider webhooks.
 *
 * A missing secret, a missing signature, or the historical bypass string
 * "valid_signature" is always rejected. The signed body is the payload with
 * the signature field removed, keys sorted, so the amount and reference
 * cannot be swapped after signing.
 */
final class WebhookAuthenticator
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $referenceKeys
     * @param  list<string>  $paidStatuses
     * @return array{valid: bool, event_type: string, provider_reference: ?string, status: string, error: ?string, amount: ?float, currency: ?string}
     */
    public function authenticate(
        string $provider,
        array $payload,
        ?string $signature,
        array $referenceKeys,
        array $paidStatuses,
    ): array {
        $reference = $this->reference($payload, $referenceKeys);

        if (! config('services.payments.sandbox')) {
            return $this->reject($reference, 'Live '.$provider.' verification is not implemented. This provider stays disabled.');
        }

        $secret = config("services.payments.providers.{$provider}.webhook_secret");

        if (! is_string($secret) || $secret === '') {
            return $this->reject($reference, 'Webhook secret is not configured.');
        }

        if (! is_string($signature) || $signature === '' || $signature === 'valid_signature') {
            return $this->reject($reference, 'Webhook signature is required.');
        }

        $unsigned = $payload;
        unset($unsigned['signature']);
        $computed = self::sign($unsigned, $secret);

        if (! hash_equals($computed, $signature)) {
            return $this->reject($reference, 'Webhook signature verification failed.');
        }

        $rawStatus = $payload['status']
            ?? $payload['tradeStatus']
            ?? $payload['ResultCode']
            ?? null;

        if ($rawStatus === null || $rawStatus === '') {
            return $this->reject($reference, 'Webhook status is required.');
        }

        $normalized = strtolower((string) $rawStatus);
        $isPaid = in_array($normalized, $paidStatuses, true)
            || (is_numeric($rawStatus) && in_array((string) (int) $rawStatus, $paidStatuses, true));

        $amount = isset($payload['amount']) ? round((float) $payload['amount'], 2) : null;
        $currency = isset($payload['currency']) ? strtoupper((string) $payload['currency']) : null;

        return [
            'valid' => true,
            'event_type' => $isPaid ? 'payment.success' : 'payment.failed',
            'provider_reference' => $reference,
            'status' => $isPaid ? 'paid' : 'failed',
            'error' => $isPaid ? null : 'Provider reported a failed payment.',
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function sign(array $payload, string $secret): string
    {
        unset($payload['signature']);

        return hash_hmac('sha256', self::canonical($payload), $secret);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function canonical(array $payload): string
    {
        $sorted = self::sort($payload);
        $encoded = json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $referenceKeys
     */
    private function reference(array $payload, array $referenceKeys): ?string
    {
        foreach ($referenceKeys as $key) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    /**
     * @return array{valid: bool, event_type: string, provider_reference: ?string, status: string, error: ?string, amount: null, currency: null}
     */
    private function reject(?string $reference, string $error): array
    {
        return [
            'valid' => false,
            'event_type' => 'payment.unknown',
            'provider_reference' => $reference,
            'status' => 'failed',
            'error' => $error,
            'amount' => null,
            'currency' => null,
        ];
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function sort(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sort($item);
            }
        }

        if (array_is_list($value)) {
            return $value;
        }

        ksort($value);

        return $value;
    }
}
