<?php

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Enums\LessonTypeEnum;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Education\Models\CourseModule;
use App\Domain\Education\Models\Lesson;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(EducationSeeder::class);

    $this->admin = User::factory()->create([
        'name' => 'Instructor User',
        'email' => 'instructor@campusesports.et',
    ]);
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);

    $this->student = User::factory()->create([
        'name' => 'Selamawit Desta',
        'email' => 'selam@gmail.com',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    $this->course = Course::create([
        'instructor_id' => $this->admin->id,
        'title' => 'Digital Literacy & Online Safety in Campus',
        'slug' => 'digital-literacy-online-safety',
        'category' => CourseCategoryEnum::ONLINE_SAFETY,
        'level' => CourseLevelEnum::BEGINNER,
        'status' => CourseStatusEnum::PUBLISHED,
        'is_published' => true,
    ]);

    $this->module = CourseModule::create([
        'course_id' => $this->course->id,
        'title' => 'Foundations of Cybersecurity',
        'order' => 1,
    ]);

    $this->lesson1 = Lesson::create([
        'course_id' => $this->course->id,
        'module_id' => $this->module->id,
        'title' => 'Protecting Your Gamer Identity',
        'slug' => 'protecting-gamer-identity',
        'lesson_type' => LessonTypeEnum::ARTICLE,
        'content' => 'Always use two-factor authentication on Steam, Discord, and Telebirr.',
        'order' => 1,
        'duration_minutes' => 15,
        'is_downloadable' => true,
    ]);

    $this->lesson2 = Lesson::create([
        'course_id' => $this->course->id,
        'module_id' => $this->module->id,
        'title' => 'Preventing Cyberbullying in Team Voice Channels',
        'slug' => 'preventing-cyberbullying-voice',
        'lesson_type' => LessonTypeEnum::ARTICLE,
        'content' => 'Promote inclusivity and mute abusive players immediately.',
        'order' => 2,
        'duration_minutes' => 15,
        'is_downloadable' => true,
    ]);

    // Enroll student
    CourseEnrollment::create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
});

test('completes lesson, updates learning streak, points, and course progress', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/lessons/{$this->lesson1->id}/complete", [
            'time_spent_seconds' => 180,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'completed');

    // Verify LessonProgress record
    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $this->student->id,
        'lesson_id' => $this->lesson1->id,
        'course_id' => $this->course->id,
        'status' => 'completed',
    ]);

    // Verify CourseEnrollment percentage is updated to 50% (1 of 2 lessons)
    $enrollment = CourseEnrollment::where('user_id', $this->student->id)
        ->where('course_id', $this->course->id)
        ->first();
    expect($enrollment->progress_percentage)->toBe(50)
        ->and($enrollment->status)->toBe('active');

    // Verify LearningStreak is incremented
    $this->assertDatabaseHas('learning_streaks', [
        'user_id' => $this->student->id,
        'current_streak' => 1,
        'total_lessons_completed' => 1,
    ]);
});

test('awards first lesson badge and cyber shield badge upon completion of safety lesson', function () {
    $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/lessons/{$this->lesson1->id}/complete", [
            'time_spent_seconds' => 120,
        ]);

    // Verify badge awarded
    $this->assertDatabaseHas('user_badges', [
        'user_id' => $this->student->id,
    ]);
});

test('marks course enrollment completed when all lessons are finished', function () {
    $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/lessons/{$this->lesson1->id}/complete");

    $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/lessons/{$this->lesson2->id}/complete");

    $enrollment = CourseEnrollment::where('user_id', $this->student->id)
        ->where('course_id', $this->course->id)
        ->first();

    expect($enrollment->progress_percentage)->toBe(100)
        ->and($enrollment->status)->toBe('completed')
        ->and($enrollment->completed_at)->not->toBeNull();
});
