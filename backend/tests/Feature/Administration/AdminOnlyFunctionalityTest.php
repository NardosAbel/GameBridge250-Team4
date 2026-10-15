<?php

use App\Domain\Administration\Models\PlatformFeatureFlag;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->player = User::factory()->create([
        'name' => 'Regular Player',
        'email' => 'player@campus.et',
    ]);
    $this->player->assignRole(RoleEnum::PLAYER->value);

    $this->admin = User::factory()->create([
        'name' => 'Root Admin',
        'email' => 'root@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);

    $this->flag = PlatformFeatureFlag::create([
        'key' => 'sponsorship_marketplace_enabled',
        'name' => 'Sponsorship Marketplace',
        'is_enabled' => true,
    ]);
});

test('denies non-admin from toggling platform feature flags', function () {
    $response = $this->actingAs($this->player, 'sanctum')
        ->putJson("/api/v1/admin/feature-flags/{$this->flag->key}", [
            'is_enabled' => false,
        ]);

    $response->assertStatus(403);
});

test('allows super admin to toggle feature flags in real-time', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/admin/feature-flags/{$this->flag->key}", [
            'is_enabled' => false,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'key' => 'sponsorship_marketplace_enabled',
                'is_enabled' => false,
            ],
        ]);

    $this->assertDatabaseHas('platform_feature_flags', [
        'key' => 'sponsorship_marketplace_enabled',
        'is_enabled' => false,
        'updated_by_id' => $this->admin->id,
    ]);
});

test('super admin can monitor live system health', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/system-health');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'operational',
                'database' => 'healthy',
            ],
        ]);
});
