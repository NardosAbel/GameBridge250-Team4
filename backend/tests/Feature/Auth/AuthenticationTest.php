<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Identity\Enums\UserStatusEnum;
use App\Domain\Identity\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can authenticate via email and receive token', function () {
    $user = User::factory()->create([
        'email' => 'player@example.com',
        'password' => Hash::make('Secret123!'),
        'status' => UserStatusEnum::ACTIVE,
    ]);
    $user->assignRole(RoleEnum::PLAYER->value);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'player@example.com',
        'password' => 'Secret123!',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Logged in successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'uuid', 'name', 'email'],
                'token',
            ],
        ]);

    expect(AuditLog::where('action', 'user_logged_in')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('user can authenticate via phone number', function () {
    $user = User::factory()->create([
        'phone' => '+251911998877',
        'password' => Hash::make('Secret123!'),
        'status' => UserStatusEnum::ACTIVE,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => '+251911998877',
        'password' => 'Secret123!',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('authentication rejects incorrect credentials', function () {
    User::factory()->create([
        'email' => 'valid@example.com',
        'password' => Hash::make('Secret123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'valid@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ])
        ->assertJsonValidationErrors(['login']);
});

test('banned user is denied login', function () {
    User::factory()->create([
        'email' => 'banned@example.com',
        'password' => Hash::make('Secret123!'),
        'status' => UserStatusEnum::BANNED,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'banned@example.com',
        'password' => 'Secret123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['login']);
});

test('authenticated user can retrieve their profile and logout', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);
    $token = $user->createToken('test')->plainTextToken;

    // Me endpoint
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);

    // Logout endpoint
    $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($user->tokens()->count())->toBe(0);
});
