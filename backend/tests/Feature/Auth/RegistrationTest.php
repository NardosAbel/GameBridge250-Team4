<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Models\AuditLog;
use App\Domain\Identity\Models\Otp;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can register via email with gamer tag and gets player role', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Kidus Tesfaye',
        'email' => 'kidus@example.com',
        'password' => 'Passw0rd123!',
        'password_confirmation' => 'Passw0rd123!',
        'gamer_tag' => 'KidusGamer',
        'university' => 'Addis Ababa University',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User registered successfully. Verification OTP dispatched.',
        ])
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'uuid', 'name', 'email', 'roles'],
                'token',
                'otp' => ['recipient', 'type', 'expires_in_seconds'],
            ],
        ]);

    $user = User::where('email', 'kidus@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(RoleEnum::PLAYER->value))->toBeTrue()
        ->and($user->profile)->not->toBeNull()
        ->and($user->profile->gamer_tag)->toBe('KidusGamer');

    // Audit log created
    expect(AuditLog::where('action', 'user_registered')->where('user_id', $user->id)->exists())->toBeTrue();

    // OTP created
    expect(Otp::where('recipient', 'kidus@example.com')->where('type', 'email_verification')->exists())->toBeTrue();
});

test('user can register via phone number', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Almaz Bekele',
        'phone' => '+251911223344',
        'password' => 'Passw0rd123!',
        'password_confirmation' => 'Passw0rd123!',
        'gamer_tag' => 'AlmazShooter',
        'university' => 'Hawassa University',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $user = User::where('phone', '+251911223344')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(RoleEnum::PLAYER->value))->toBeTrue();
});

test('registration rejects duplicate email or gamer tag', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'First User',
        'email' => 'duplicate@example.com',
        'password' => 'Passw0rd123!',
        'password_confirmation' => 'Passw0rd123!',
        'gamer_tag' => 'UniqueTag1',
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Second User',
        'email' => 'duplicate@example.com',
        'password' => 'Passw0rd123!',
        'password_confirmation' => 'Passw0rd123!',
        'gamer_tag' => 'UniqueTag2',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ])
        ->assertJsonValidationErrors(['email']);
});
