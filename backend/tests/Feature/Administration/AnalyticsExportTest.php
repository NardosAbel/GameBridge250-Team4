<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->student = User::factory()->create([
        'name' => 'Student Player',
        'email' => 'student@campus.et',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->admin = User::factory()->create([
        'name' => 'Data Analyst Admin',
        'email' => 'data@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
});

test('denies non-admin from exporting executive platform metrics CSV', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->get('/api/v1/admin/analytics/export');

    $response->assertStatus(403);
});

test('allows super admin to export platform executive metrics as CSV', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->get('/api/v1/admin/analytics/export');

    $response->assertStatus(200);
    $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

    $content = $response->getContent();
    $this->assertStringContainsString('Domain Category', $content);
    $this->assertStringContainsString('Key Metric', $content);
    $this->assertStringContainsString('Total Registered Users', $content);
    $this->assertStringContainsString('Tournament Completion Rate (%)', $content);
    $this->assertStringContainsString('Gross Ticketing Revenue (ETB)', $content);
    $this->assertStringContainsString('Active Brand Deals', $content);
});
