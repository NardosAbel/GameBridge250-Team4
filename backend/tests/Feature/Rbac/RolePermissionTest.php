<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('all 9 mandatory roles are seeded', function () {
    foreach (RoleEnum::cases() as $roleEnum) {
        expect(Role::where('name', $roleEnum->value)->exists())->toBeTrue();
    }
});

test('super admin has full permissions and can view audit logs', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $token = $admin->createToken('admin')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/audit-logs');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('regular player cannot access audit logs endpoint', function () {
    $player = User::factory()->create();
    $player->assignRole(RoleEnum::PLAYER->value);
    $token = $player->createToken('player')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/audit-logs');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'This action is unauthorized.',
        ]);
});
