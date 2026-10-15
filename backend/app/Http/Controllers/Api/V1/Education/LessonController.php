<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Actions\CompleteLessonAction;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Education\Models\Lesson;
use App\Domain\Education\Models\LessonProgress;
use App\Domain\Education\Services\OfflineLessonService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    public function show(Request $request, int|string $id): JsonResponse
    {
        $lesson = is_numeric($id)
            ? Lesson::findOrFail($id)
            : Lesson::where('uuid', $id)->orWhere('slug', $id)->firstOrFail();

        $user = $request->user('sanctum');
        Gate::authorize('view', $lesson);

        // Track last accessed lesson in enrollment
        if ($user) {
            CourseEnrollment::where('user_id', $user->id)
                ->where('course_id', $lesson->course_id)
                ->update(['last_accessed_lesson_id' => $lesson->id]);

            // Ensure progress record exists
            LessonProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'course_id' => $lesson->course_id,
                    'status' => 'started',
                ]
            );
        }

        $progress = $user ? LessonProgress::where('user_id', $user->id)->where('lesson_id', $lesson->id)->first() : null;

        // Next & previous lesson navigation
        $previousLesson = Lesson::where('course_id', $lesson->course_id)
            ->where('order', '<', $lesson->order)
            ->orderByDesc('order')
            ->first(['id', 'uuid', 'title', 'title_am', 'slug']);

        $nextLesson = Lesson::where('course_id', $lesson->course_id)
            ->where('order', '>', $lesson->order)
            ->orderBy('order')
            ->first(['id', 'uuid', 'title', 'title_am', 'slug']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $lesson->id,
                'uuid' => $lesson->uuid,
                'course_id' => $lesson->course_id,
                'module_id' => $lesson->module_id,
                'title' => $lesson->title,
                'title_am' => $lesson->title_am,
                'slug' => $lesson->slug,
                'lesson_type' => $lesson->lesson_type->value,
                'content' => $lesson->content,
                'content_am' => $lesson->content_am,
                'media_url' => $lesson->media_url,
                'video_embed_url' => $lesson->video_embed_url,
                'pdf_url' => $lesson->pdf_url,
                'interactive_payload' => $lesson->interactive_payload,
                'order' => $lesson->order,
                'duration_minutes' => $lesson->duration_minutes,
                'is_preview' => $lesson->is_preview,
                'is_downloadable' => $lesson->is_downloadable,
                'progress' => $progress ? [
                    'status' => $progress->status,
                    'last_position_seconds' => $progress->last_position_seconds,
                    'time_spent_seconds' => $progress->time_spent_seconds,
                    'completed_at' => $progress->completed_at,
                ] : null,
                'navigation' => [
                    'previous' => $previousLesson,
                    'next' => $nextLesson,
                ],
            ],
        ]);
    }

    public function complete(Request $request, int|string $id, CompleteLessonAction $action): JsonResponse
    {
        $lesson = is_numeric($id)
            ? Lesson::findOrFail($id)
            : Lesson::where('uuid', $id)->firstOrFail();

        $user = $request->user();
        Gate::authorize('complete', $lesson);

        $timeSpent = (int) $request->input('time_spent_seconds', 0);
        $progress = $action->execute($user, $lesson, $timeSpent);

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as completed.',
            'data' => $progress,
        ]);
    }

    public function offlinePackage(Request $request, int|string $id, OfflineLessonService $offlineService): JsonResponse
    {
        $lesson = is_numeric($id)
            ? Lesson::findOrFail($id)
            : Lesson::where('uuid', $id)->firstOrFail();

        $user = $request->user();
        Gate::authorize('downloadOffline', $lesson);

        $bundle = $offlineService->packageLessonForOffline($lesson);

        return response()->json([
            'success' => true,
            'message' => 'Offline lesson package generated successfully.',
            'data' => $bundle,
        ]);
    }

    public function syncOffline(Request $request, OfflineLessonService $offlineService): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'lessons' => ['required', 'array'],
            'lessons.*.lesson_id' => ['nullable'],
            'lessons.*.lesson_uuid' => ['nullable', 'string'],
            'lessons.*.time_spent_seconds' => ['nullable', 'integer'],
        ]);

        $result = $offlineService->syncOfflineProgress($user, $validated['lessons']);

        return response()->json([
            'success' => true,
            'message' => "Successfully synchronized {$result['total_synced']} offline lesson(s).",
            'data' => $result,
        ]);
    }
}
