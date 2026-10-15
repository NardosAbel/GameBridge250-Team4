<?php

namespace App\Domain\Education\Services;

use App\Domain\Education\Models\Lesson;
use App\Models\User;

class OfflineLessonService
{
    public function __construct(
        protected LearningProgressService $progressService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function packageLessonForOffline(Lesson $lesson): array
    {
        $quiz = $lesson->quiz;

        return [
            'lesson_uuid' => $lesson->uuid,
            'course_uuid' => $lesson->course->uuid,
            'title' => $lesson->title,
            'title_am' => $lesson->title_am,
            'slug' => $lesson->slug,
            'lesson_type' => $lesson->lesson_type->value,
            'content' => $lesson->content,
            'content_am' => $lesson->content_am,
            'media_url' => $lesson->media_url,
            'pdf_url' => $lesson->pdf_url,
            'interactive_payload' => $lesson->interactive_payload,
            'duration_minutes' => $lesson->duration_minutes,
            'is_downloadable' => $lesson->is_downloadable,
            'quiz' => $quiz ? [
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'title_am' => $quiz->title_am,
                'passing_score' => $quiz->passing_score,
                'questions' => $quiz->questions,
            ] : null,
            'packaged_at' => now()->toIso8601String(),
            'app_version' => '1.0.0-offline',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $syncedItems
     * @return array<string, mixed>
     */
    public function syncOfflineProgress(User $user, array $syncedItems): array
    {
        $syncedCount = 0;
        $results = [];

        foreach ($syncedItems as $item) {
            $lessonId = $item['lesson_id'] ?? null;
            $lessonUuid = $item['lesson_uuid'] ?? null;
            $timeSpent = (int) ($item['time_spent_seconds'] ?? 0);

            $lesson = null;
            if ($lessonId) {
                $lesson = Lesson::find($lessonId);
            } elseif ($lessonUuid) {
                $lesson = Lesson::where('uuid', $lessonUuid)->first();
            }

            if ($lesson) {
                $progress = $this->progressService->recordLessonCompletion($user, $lesson, $timeSpent);
                $syncedCount++;
                $results[] = [
                    'lesson_id' => $lesson->id,
                    'status' => 'synced',
                    'progress_id' => $progress->id,
                ];
            }
        }

        return [
            'total_synced' => $syncedCount,
            'results' => $results,
        ];
    }
}
