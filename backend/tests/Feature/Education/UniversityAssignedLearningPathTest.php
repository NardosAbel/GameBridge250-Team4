<?php

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\LearningPath;
use App\Domain\Education\Models\LearningPathAssignment;
use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(EducationSeeder::class);

    $this->uniAdmin = User::factory()->create([
        'name' => 'Dr. Eyob Assefa',
        'email' => 'eyob@aau.edu.et',
    ]);
    $this->uniAdmin->assignRole(RoleEnum::SCHOOL_ADMIN->value);

    $this->student = User::factory()->create([
        'name' => 'Yared Negash',
        'email' => 'yared@gmail.com',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    Profile::create([
        'user_id' => $this->student->id,
        'gamer_tag' => 'YaredAAU',
        'university' => 'Addis Ababa University',
        'student_verified' => true,
    ]);

    $this->course = Course::create([
        'instructor_id' => $this->uniAdmin->id,
        'title' => 'Freshman Academic & Esports Balance',
        'slug' => 'freshman-academic-esports-balance',
        'category' => CourseCategoryEnum::HEALTHY_GAMING,
        'level' => CourseLevelEnum::BEGINNER,
        'status' => CourseStatusEnum::PUBLISHED,
        'is_published' => true,
    ]);
});

test('allows university admin to create learning path and assign to university cohort', function () {
    // 1. Create learning path
    $createRes = $this->actingAs($this->uniAdmin, 'sanctum')
        ->postJson('/api/v1/education/learning-paths', [
            'title' => 'AAU Esports Guild Freshman Induction',
            'target_university' => 'Addis Ababa University',
            'institution_type' => 'university',
            'is_mandatory' => true,
            'courses' => [$this->course->id],
        ]);

    $createRes->assertStatus(201)
        ->assertJsonPath('success', true);

    $pathId = $createRes->json('data.id');

    // 2. Assign path to AAU students
    $assignRes = $this->actingAs($this->uniAdmin, 'sanctum')
        ->postJson("/api/v1/education/learning-paths/{$pathId}/assign", [
            'university_name' => 'Addis Ababa University',
            'due_date' => now()->addDays(30)->toIso8601String(),
        ]);

    $assignRes->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_assigned', 1);

    $this->assertDatabaseHas('learning_path_assignments', [
        'learning_path_id' => $pathId,
        'user_id' => $this->student->id,
        'university_name' => 'Addis Ababa University',
    ]);
});

test('shows assigned university learning paths in student dashboard', function () {
    $path = LearningPath::create([
        'created_by_id' => $this->uniAdmin->id,
        'title' => 'Mandatory AAU Cybersecurity Path',
        'slug' => 'mandatory-aau-cybersecurity-path',
        'target_university' => 'Addis Ababa University',
        'is_mandatory' => true,
    ]);

    LearningPathAssignment::create([
        'learning_path_id' => $path->id,
        'user_id' => $this->student->id,
        'assigned_by_id' => $this->uniAdmin->id,
        'university_name' => 'Addis Ababa University',
        'due_date' => now()->addDays(14),
        'status' => 'assigned',
    ]);

    $dashRes = $this->actingAs($this->student, 'sanctum')
        ->getJson('/api/v1/education/dashboard');

    $dashRes->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.assigned_paths')
        ->assertJsonPath('data.assigned_paths.0.learning_path.title', 'Mandatory AAU Cybersecurity Path');
});
