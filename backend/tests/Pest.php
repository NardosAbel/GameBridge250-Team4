<?php

use App\Domain\Payments\Support\WebhookAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to watch for specific events, perform database operations, or interact with HTTP.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Sign a payment webhook the same way WebhookAuthenticator verifies it.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function paymentWebhookPayload(string $provider, array $fields): array
{
    $secret = config("services.payments.providers.{$provider}.webhook_secret");
    $fields['signature'] = WebhookAuthenticator::sign($fields, (string) $secret);

    return $fields;
}
