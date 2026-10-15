<?php

namespace App\Domain\Education\Actions;

use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\LearningStreak;
use App\Domain\Education\Models\LessonProgress;
use App\Domain\Education\Models\Quiz;
use App\Domain\Education\Models\QuizAttempt;
use App\Domain\Education\Services\CertificateVerificationService;
use App\Domain\Education\Services\LearningProgressService;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitQuizAttemptAction
{
    public function __construct(
        protected LearningProgressService $progressService,
        protected CertificateVerificationService $certificateService
    ) {}

    /**
     * @param  array<string|int, mixed>  $submittedAnswers
     * @return array<string, mixed>
     */
    public function execute(User $user, Quiz $quiz, array $submittedAnswers): array
    {
        // 1. Check max retries
        $previousAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->count();

        if ($quiz->max_retries > 0 && $previousAttempts >= $quiz->max_retries) {
            // Check if already passed
            $hasPassed = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('passed', true)
                ->exists();

            if (! $hasPassed) {
                throw ValidationException::withMessages([
                    'quiz' => ["Maximum attempts ({$quiz->max_retries}) reached for this quiz."],
                ]);
            }
        }

        // 2. Score questions
        $questions = $quiz->questions ?? [];
        $totalQuestions = count($questions);
        $correctCount = 0;
        $feedback = [];

        if ($totalQuestions > 0) {
            foreach ($questions as $index => $q) {
                $qId = $q['id'] ?? (string) $index;
                $correctIndex = $q['correct_index'] ?? 0;
                $userSelection = $submittedAnswers[$qId] ?? $submittedAnswers[$index] ?? null;

                $isCorrect = ((int) $userSelection === (int) $correctIndex);
                if ($isCorrect) {
                    $correctCount++;
                }

                $feedback[] = [
                    'question_id' => $qId,
                    'user_selection' => $userSelection,
                    'correct_index' => $correctIndex,
                    'is_correct' => $isCorrect,
                    'explanation' => $q['explanation'] ?? null,
                    'explanation_am' => $q['explanation_am'] ?? null,
                ];
            }
            $scorePercentage = (int) round(($correctCount / $totalQuestions) * 100);
        } else {
            $scorePercentage = 100;
        }

        $passed = ($scorePercentage >= $quiz->passing_score);

        // 3. Save attempt
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => $scorePercentage,
            'passed' => $passed,
            'answers' => $submittedAnswers,
            'attempt_number' => $previousAttempts + 1,
            'completed_at' => now(),
        ]);

        $certificate = null;

        // 4. If passed, handle lesson completion, streaks, badges, certificates
        if ($passed) {
            if ($quiz->lesson) {
                $this->progressService->recordLessonCompletion($user, $quiz->lesson, 300);
            }

            // Streak points update
            $streak = LearningStreak::firstOrCreate(['user_id' => $user->id]);
            $streak->increment('total_quizzes_passed');
            $this->progressService->updateUserStreak($user, 25); // 25 points for passing quiz

            // Perfect score badge
            if ($scorePercentage === 100) {
                $this->progressService->awardBadgeIfEligible(
                    $user,
                    'quiz_ace',
                    'Quiz Ace',
                    'የፈተና ጀግና',
                    'Scored 100% on a digital awareness quiz',
                    'በዲጂታል ግንዛቤ ፈተና 100% ውጤት አስመዝግበዋል',
                    'check-circle-2'
                );
            }

            // Check if course is completed to issue certificate
            if ($quiz->course) {
                $course = $quiz->course;
                $totalLessons = $course->lessons()->count();
                $completedLessons = LessonProgress::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('status', 'completed')
                    ->count();

                if ($completedLessons >= $totalLessons) {
                    $certificate = $this->certificateService->issueCertificate($user, $course, $scorePercentage);
                }
            }
        }

        return [
            'attempt' => $attempt,
            'score' => $scorePercentage,
            'passed' => $passed,
            'passing_score' => $quiz->passing_score,
            'feedback' => $feedback,
            'certificate' => $certificate,
        ];
    }
}
