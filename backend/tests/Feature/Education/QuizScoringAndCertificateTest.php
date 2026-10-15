<?php

use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Enums\CourseLevelEnum;
use App\Domain\Education\Enums\CourseStatusEnum;
use App\Domain\Education\Enums\LessonTypeEnum;
use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Education\Models\Lesson;
use App\Domain\Education\Models\Quiz;
use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(EducationSeeder::class);

    $this->student = User::factory()->create([
        'name' => 'Kalkidan Bekele',
        'email' => 'kalkidan@gmail.com',
    ]);
    $this->student->assignRole(RoleEnum::PLAYER->value);

    Profile::create([
        'user_id' => $this->student->id,
        'gamer_tag' => 'KalCyberQueen',
        'university' => 'Addis Ababa Science and Technology University',
        'student_verified' => true,
    ]);

    $this->course = Course::create([
        'instructor_id' => $this->student->id,
        'title' => 'Anti-Phishing & Safe Telebirr Transactions',
        'slug' => 'anti-phishing-safe-telebirr',
        'category' => CourseCategoryEnum::SCAM_AWARENESS,
        'level' => CourseLevelEnum::BEGINNER,
        'status' => CourseStatusEnum::PUBLISHED,
        'is_published' => true,
    ]);

    $this->lesson = Lesson::create([
        'course_id' => $this->course->id,
        'title' => 'Identifying Fraudulent Telebirr Payment Slips',
        'slug' => 'identifying-fraudulent-telebirr-slips',
        'lesson_type' => LessonTypeEnum::ARTICLE,
        'content' => 'Always verify transaction ID in Telebirr app before transferring skins.',
        'order' => 1,
    ]);

    $this->quiz = Quiz::create([
        'course_id' => $this->course->id,
        'lesson_id' => $this->lesson->id,
        'title' => 'Telebirr Fraud Prevention Quiz',
        'passing_score' => 70,
        'max_retries' => 2,
        'questions' => [
            [
                'id' => 'q1',
                'question' => 'What is the safest way to verify a Telebirr payment from an opponent?',
                'options' => [
                    'Accept a screenshot in Telegram',
                    'Check your official Telebirr SMS and transaction history inside the app',
                    'Trust the opponent gamer tag',
                ],
                'correct_index' => 1,
                'explanation' => 'Screenshots can easily be faked with image editing software.',
            ],
            [
                'id' => 'q2',
                'question' => 'Should you ever share your 6-digit Telebirr PIN with a tournament referee?',
                'options' => [
                    'Yes, if they have an official referee title',
                    'No, legitimate referees never ask for your PIN',
                ],
                'correct_index' => 1,
                'explanation' => 'PINs are strictly confidential.',
            ],
        ],
    ]);

    // Enroll
    CourseEnrollment::create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
});

test('sanitizes quiz questions in public show to prevent answer leakage', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/education/quizzes/{$this->quiz->id}");

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.questions.0.question', 'What is the safest way to verify a Telebirr payment from an opponent?')
        ->assertJsonMissing(['correct_index']); // Ensure answers are never exposed
});

test('scores quiz attempt accurately, calculates percentage, and records attempt', function () {
    // Both correct answers: q1 -> 1, q2 -> 1
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/quizzes/{$this->quiz->id}/submit", [
            'answers' => [
                'q1' => 1,
                'q2' => 1,
            ],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.passed', true)
        ->assertJsonPath('data.score', 100);

    $this->assertDatabaseHas('quiz_attempts', [
        'user_id' => $this->student->id,
        'quiz_id' => $this->quiz->id,
        'score' => 100,
        'passed' => true,
    ]);
});

test('enforces max retries limit on repeated failed attempts', function () {
    // Fail attempt 1
    $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/quizzes/{$this->quiz->id}/submit", [
            'answers' => ['q1' => 0, 'q2' => 0],
        ]);

    // Fail attempt 2
    $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/quizzes/{$this->quiz->id}/submit", [
            'answers' => ['q1' => 0, 'q2' => 0],
        ]);

    // Attempt 3 should be rejected because max_retries is 2
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/quizzes/{$this->quiz->id}/submit", [
            'answers' => ['q1' => 1, 'q2' => 1],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quiz']);
});

test('issues cryptographic certificate upon passing and enables public verification', function () {
    // Submit winning quiz attempt
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson("/api/v1/education/quizzes/{$this->quiz->id}/submit", [
            'answers' => ['q1' => 1, 'q2' => 1],
        ]);

    $response->assertStatus(200);

    $certificate = Certificate::where('user_id', $this->student->id)
        ->where('course_id', $this->course->id)
        ->first();

    expect($certificate)->not->toBeNull()
        ->and($certificate->verification_code)->toStartWith('CEE-')
        ->and($certificate->certificate_number)->toStartWith('CEE-CERT-');

    // Public verification endpoint
    $verifyRes = $this->getJson("/api/v1/certificates/verify/{$certificate->verification_code}");

    $verifyRes->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_valid', true)
        ->assertJsonPath('data.certificate.student_name', 'Kalkidan Bekele')
        ->assertJsonPath('data.certificate.university', 'Addis Ababa Science and Technology University')
        ->assertJsonPath('data.certificate.course_title', 'Anti-Phishing & Safe Telebirr Transactions');
});
