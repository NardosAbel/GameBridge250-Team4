<?php

use App\Domain\Identity\Actions\SendOtpAction;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can verify OTP code and mark email verified', function () {
    $user = User::factory()->create([
        'email' => 'otpuser@example.com',
        'email_verified_at' => null,
    ]);

    $sendAction = app(SendOtpAction::class);
    $otpResult = $sendAction->execute('otpuser@example.com', 'email_verification', $user);

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'recipient' => 'otpuser@example.com',
        'code' => $otpResult['code'],
        'type' => 'email_verification',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Verification successful.',
        ]);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('verification fails for invalid code and tracks attempts', function () {
    $user = User::factory()->create(['email' => 'verifyfail@example.com']);

    $sendAction = app(SendOtpAction::class);
    $sendAction->execute('verifyfail@example.com', 'email_verification', $user);

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'recipient' => 'verifyfail@example.com',
        'code' => '000000',
        'type' => 'email_verification',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});
