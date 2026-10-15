<?php

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Models\Course;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);

    $this->student = User::factory()->create([
        'name' => 'Tewodros Kassahun',
        'email' => 'teddy@gmail.com',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->publishedCourse = Course::create([
        'instructor_id' => $this->admin->id,
        'title' => 'Healthy Gaming & Ergonomics for Student Competitors',
        'title_am' => 'ጤናማ ጌሚንግ እና ኤርጎኖሚክስ ለተማሪዎች',
        'slug' => 'healthy-gaming-ergonomics',
        'category' => CourseCategoryEnum::HEALTHY_GAMING,
        'level' => CourseLevelEnum::BEGINNER,
        'status' => CourseStatusEnum::PUBLISHED,
        'is_published' => true,
        'estimated_duration_minutes' => 30,
        'total_points' => 100,
    ]);

    $this->draftCourse = Course::create([
        'instructor_id' => $this->admin->id,
        'title' => 'Advanced AI in Game Development',
        'slug' => 'advanced-ai-game-dev',
        'category' => CourseCategoryEnum::AI_BASICS,
        'level' => CourseLevelEnum::ADVANCED,
        'status' => CourseStatusEnum::DRAFT,
        'is_published' => false,
    ]);
});

test('enrolls an authenticated student in a published course', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/courses/{$this->publishedCourse->id}/enroll");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.course_id', $this->publishedCourse->id)
        ->assertJsonPath('data.user_id', $this->student->id)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('course_enrollments', [
        'user_id' => $this->student->id,
        'course_id' => $this->publishedCourse->id,
        'status' => 'active',
    ]);
});

test('denies enrollment in an unpublished draft course', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/courses/{$this->draftCourse->id}/enroll");

    $response->assertStatus(403);

    $this->assertDatabaseMissing('course_enrollments', [
        'user_id' => $this->student->id,
        'course_id' => $this->draftCourse->id,
    ]);
});

test('lists published courses and filters by category and difficulty level', function () {
    $response = $this->getJson('/api/v1/courses?category=healthy_gaming&level=beginner');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.title', 'Healthy Gaming & Ergonomics for Student Competitors');
});
