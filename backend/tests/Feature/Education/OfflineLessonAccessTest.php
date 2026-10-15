<?php

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Enums\LessonTypeEnum;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Education\Models\Lesson;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(EducationSeeder::class);

    $this->student = User::factory()->create([
        'name' => 'Nahom Girma',
        'email' => 'nahom@gmail.com',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->course = Course::create([
        'instructor_id' => $this->student->id,
        'title' => 'Offline-Capable Cybersecurity Fundamentals',
        'slug' => 'offline-cybersecurity-fundamentals',
        'category' => CourseCategoryEnum::ONLINE_SAFETY,
        'level' => CourseLevelEnum::BEGINNER,
        'status' => CourseStatusEnum::PUBLISHED,
        'is_published' => true,
        'is_offline_available' => true,
    ]);

    $this->lesson = Lesson::create([
        'course_id' => $this->course->id,
        'title' => 'Strong Passwords and Password Managers',
        'slug' => 'strong-passwords-managers',
        'lesson_type' => LessonTypeEnum::ARTICLE,
        'content' => 'Create unique 16-character passphrases for each gaming service.',
        'order' => 1,
        'is_downloadable' => true,
    ]);

    CourseEnrollment::create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
});

test('generates self-contained offline lesson package for low-connectivity environments', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/education/lessons/{$this->lesson->id}/offline-package");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.lesson_uuid', $this->lesson->uuid)
        ->assertJsonPath('data.title', 'Strong Passwords and Password Managers')
        ->assertJsonPath('data.content', 'Create unique 16-character passphrases for each gaming service.')
        ->assertJsonPath('data.is_downloadable', true);
});

test('synchronizes batch of offline completed lessons and updates progress and streaks', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson('/api/v1/education/lessons/sync-offline', [
            'lessons' => [
                [
                    'lesson_id' => $this->lesson->id,
                    'time_spent_seconds' => 300,
                ],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_synced', 1);

    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $this->student->id,
        'lesson_id' => $this->lesson->id,
        'status' => 'completed',
    ]);

    $this->assertDatabaseHas('learning_streaks', [
        'user_id' => $this->student->id,
        'current_streak' => 1,
        'total_lessons_completed' => 1,
    ]);
});
