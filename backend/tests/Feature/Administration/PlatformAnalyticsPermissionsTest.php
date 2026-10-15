<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->student = User::factory()->create([
        'name' => 'Campus Student',
        'email' => 'student@astu.edu.et',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->creator = User::factory()->create([
        'name' => 'Campus Streamer',
        'email' => 'streamer@creator.et',
    ]);
    $this->creator->assignRole(RoleEnum::CREATOR->value);

    $this->admin = User::factory()->create([
        'name' => 'Executive Platform Super Admin',
        'email' => 'executive@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
});

test('denies regular player from accessing platform executive analytics', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson('/api/v1/admin/analytics');

    $response->assertStatus(403);
});

test('allows super admin to retrieve executive platform analytics with all domain metrics', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/analytics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'users' => ['total_users', 'verified_students', 'verification_rate'],
                'tournaments' => ['total', 'completion_rate', 'total_matches', 'dispute_rate'],
                'ticketing' => ['tickets_sold', 'attendance_rate', 'gross_revenue'],
                'learning' => ['total_enrollments', 'completion_rate', 'certificates_issued'],
                'sponsorship' => ['total_deals', 'active_deals', 'total_volume'],
                'system_health' => ['status', 'php_version', 'laravel_version'],
            ],
        ]);
});

test('allows creator to view their creator studio insights and audience metrics', function () {
    $response = $this->actingAs($this->creator, 'sanctum')
        ->getJson('/api/v1/creator/insights');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'creator_id',
                'content_metrics' => ['total_posts', 'total_likes', 'avg_engagement_per_post'],
                'audience_insights' => ['total_followers', 'primary_university'],
                'sponsored_opportunities',
            ],
        ]);
});
