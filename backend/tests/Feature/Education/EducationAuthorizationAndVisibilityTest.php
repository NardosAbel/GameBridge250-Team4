<?php

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Models\Course;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(EducationSeeder::class);

    $this->student = User::factory()->create([
        'name' => 'Henok Alemu',
        'email' => 'henok@gmail.com',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->organizer = User::factory()->create([
        'name' => 'Bethlehem Tilahun',
        'email' => 'bethlehem@esports.et',
    ]);
    $this->organizer->assignRole(RoleEnum::ORGANIZER->value);

    $this->sponsor = User::factory()->create([
        'name' => 'Brand Sponsor Rep',
        'email' => 'sponsor@brand.et',
    ]);
    $this->sponsor->assignRole(RoleEnum::SPONSOR->value);

    $this->admin = User::factory()->create([
        'name' => 'Root Admin',
        'email' => 'root@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
});

test('denies regular player from creating courses without proper instructor or organizer role', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson('/api/v1/education/courses', [
            'title' => 'Unauthorized Course Attempt',
            'category' => 'coding',
            'level' => 'beginner',
        ]);

    $response->assertStatus(403);
});

test('allows organizer to create course and publish it', function () {
    $createRes = $this->actingAs($this->organizer, 'sanctum')
        ->postJson('/api/v1/education/courses', [
            'title' => 'Esports Event Production & Streaming in Ethiopia',
            'category' => 'content_creation',
            'level' => 'intermediate',
            'status' => 'published',
        ]);

    $createRes->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Esports Event Production & Streaming in Ethiopia');
});

test('allows sponsor to create awareness campaign and track share engagement', function () {
    $createRes = $this->actingAs($this->sponsor, 'sanctum')
        ->postJson('/api/v1/education/campaigns', [
            'title' => 'Healthy Energy & Ergonomics in Gaming',
            'category' => 'healthy_gaming',
            'description' => 'Promoting physical well-being, posture, and screen breaks for student athletes.',
            'status' => 'published',
        ]);

    $createRes->assertStatus(201)
        ->assertJsonPath('success', true);

    $campaignId = $createRes->json('data.id');

    // Share on Telegram
    $shareRes = $this->postJson("/api/v1/campaigns/{$campaignId}/share", [
        'platform' => 'telegram',
    ]);

    $shareRes->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.analytics.shares_count', 1);
});

test('hides unpublished draft courses from public listing and regular users', function () {
    Course::create([
        'instructor_id' => $this->organizer->id,
        'title' => 'Secret Unpublished Course',
        'slug' => 'secret-unpublished-course',
        'category' => CourseCategoryEnum::CODING,
        'level' => CourseLevelEnum::ADVANCED,
        'status' => CourseStatusEnum::DRAFT,
        'is_published' => false,
    ]);

    // Public request
    $publicRes = $this->getJson('/api/v1/courses');
    $titles = collect($publicRes->json('data.data'))->pluck('title');
    expect($titles)->not->toContain('Secret Unpublished Course');

    // Student request
    $studentRes = $this->actingAs($this->student, 'sanctum')
        ->getJson('/api/v1/courses');
    $studentTitles = collect($studentRes->json('data.data'))->pluck('title');
    expect($studentTitles)->not->toContain('Secret Unpublished Course');

    // Admin request should see it
    $adminRes = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/courses');
    $adminTitles = collect($adminRes->json('data.data'))->pluck('title');
    expect($adminTitles)->toContain('Secret Unpublished Course');
});
