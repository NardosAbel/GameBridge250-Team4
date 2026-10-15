<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Actions\SubmitQuizAttemptAction;
use App\Domain\Education\Models\Quiz;
use App\Domain\Education\Models\QuizAttempt;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function show(Request $request, int|string $id): JsonResponse
    {
        $quiz = is_numeric($id)
            ? Quiz::findOrFail($id)
            : Quiz::where('uuid', $id)->firstOrFail();

        $user = $request->user('sanctum');
        Gate::authorize('view', $quiz);

        // Sanitize questions to prevent leaking correct answers to the frontend
        $safeQuestions = collect($quiz->questions ?? [])->map(function ($q, $index) {
            return [
                'id' => $q['id'] ?? (string) $index,
                'question' => $q['question'] ?? '',
                'question_am' => $q['question_am'] ?? null,
                'options' => $q['options'] ?? [],
                'options_am' => $q['options_am'] ?? [],
            ];
        });

        $attemptsCount = $user
            ? QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id)->count()
            : 0;

        $hasPassed = $user
            ? QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id)->where('passed', true)->exists()
            : false;

        $bestScore = $user
            ? QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id)->max('score')
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $quiz->id,
                'uuid' => $quiz->uuid,
                'course_id' => $quiz->course_id,
                'lesson_id' => $quiz->lesson_id,
                'title' => $quiz->title,
                'title_am' => $quiz->title_am,
                'description' => $quiz->description,
                'description_am' => $quiz->description_am,
                'passing_score' => $quiz->passing_score,
                'max_retries' => $quiz->max_retries,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'questions' => $safeQuestions,
                'user_stats' => [
                    'attempts_count' => $attemptsCount,
                    'has_passed' => $hasPassed,
                    'best_score' => $bestScore,
                ],
            ],
        ]);
    }

    public function submit(Request $request, int|string $id, SubmitQuizAttemptAction $action): JsonResponse
    {
        $quiz = is_numeric($id)
            ? Quiz::findOrFail($id)
            : Quiz::where('uuid', $id)->firstOrFail();

        $user = $request->user();
        Gate::authorize('attempt', $quiz);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $result = $action->execute($user, $quiz, $validated['answers']);

        return response()->json([
            'success' => true,
            'message' => $result['passed'] ? 'Congratulations! You passed the quiz.' : 'You did not meet the passing score. You may retry if attempts remain.',
            'data' => $result,
        ]);
    }
}
