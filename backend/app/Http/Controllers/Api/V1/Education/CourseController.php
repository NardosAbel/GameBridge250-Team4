<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Actions\CreateCourseAction;
use App\Domain\Education\Actions\EnrollInCourseAction;
use App\Domain\Education\Actions\PublishCourseAction;
use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\LessonProgress;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Course::with(['instructor', 'modules.lessons', 'lessons'])
            ->withCount(['lessons', 'modules', 'enrollments']);

        if ($request->has('category') && ! empty($request->input('category'))) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('level') && ! empty($request->input('level'))) {
            $query->where('level', $request->input('level'));
        }

        if ($request->has('search') && ! empty($request->input('search'))) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('title_am', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        // Filter published only for non-admins
        $user = $request->user('sanctum');
        if (! $user || ! ($user->hasRole('super_admin') || $user->hasRole('moderator'))) {
            $query->where('is_published', true);
        }

        $courses = $query->latest()->paginate($request->input('per_page', 12));

        // Format for response with user enrollment status
        $data = $courses->through(function ($c) use ($user) {
            $enrollment = $user ? $c->enrollments()->where('user_id', $user->id)->first() : null;

            return [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'title' => $c->title,
                'title_am' => $c->title_am,
                'slug' => $c->slug,
                'category' => $c->category->value,
                'category_label' => $c->category->label(),
                'category_label_am' => $c->category->labelAm(),
                'level' => $c->level->value,
                'description' => $c->description,
                'description_am' => $c->description_am,
                'cover_image' => $c->cover_image,
                'status' => $c->status->value,
                'is_published' => $c->is_published,
                'estimated_duration_minutes' => $c->estimated_duration_minutes,
                'total_points' => $c->total_points,
                'is_offline_available' => $c->is_offline_available,
                'language_support' => $c->language_support,
                'lessons_count' => $c->lessons_count,
                'modules_count' => $c->modules_count,
                'enrollment' => $enrollment ? [
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request, CreateCourseAction $action): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('create', Course::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(CourseCategoryEnum::values())],
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
            'description' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'language_support' => ['nullable', 'array'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:5'],
            'total_points' => ['nullable', 'integer', 'min:10'],
            'is_offline_available' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,scheduled,published,archived'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $course = $action->execute($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'data' => $course,
        ], 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $course = is_numeric($id)
            ? Course::findOrFail($id)
            : Course::where('uuid', $id)->orWhere('slug', $id)->firstOrFail();

        $user = $request->user('sanctum');
        Gate::authorize('view', $course);

        $course->load([
            'instructor:id,name',
            'modules.lessons',
            'lessons',
            'quizzes:id,uuid,course_id,title,title_am,passing_score,max_retries,time_limit_minutes',
        ]);

        $enrollment = $user ? $course->enrollments()->where('user_id', $user->id)->first() : null;

        // Attach user progress to lessons if authenticated
        $lessonProgressMap = [];
        if ($user) {
            $lessonProgressMap = LessonProgress::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->pluck('status', 'lesson_id')
                ->toArray();
        }

        $formattedModules = $course->modules->map(function ($mod) use ($lessonProgressMap) {
            return [
                'id' => $mod->id,
                'uuid' => $mod->uuid,
                'title' => $mod->title,
                'title_am' => $mod->title_am,
                'order' => $mod->order,
                'lessons' => $mod->lessons->map(function ($l) use ($lessonProgressMap) {
                    return [
                        'id' => $l->id,
                        'uuid' => $l->uuid,
                        'title' => $l->title,
                        'title_am' => $l->title_am,
                        'slug' => $l->slug,
                        'lesson_type' => $l->lesson_type->value,
                        'order' => $l->order,
                        'duration_minutes' => $l->duration_minutes,
                        'is_preview' => $l->is_preview,
                        'is_downloadable' => $l->is_downloadable,
                        'is_completed' => ($lessonProgressMap[$l->id] ?? null) === 'completed',
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $course->id,
                'uuid' => $course->uuid,
                'title' => $course->title,
                'title_am' => $course->title_am,
                'slug' => $course->slug,
                'category' => $course->category->value,
                'category_label' => $course->category->label(),
                'category_label_am' => $course->category->labelAm(),
                'level' => $course->level->value,
                'description' => $course->description,
                'description_am' => $course->description_am,
                'cover_image' => $course->cover_image,
                'status' => $course->status->value,
                'is_published' => $course->is_published,
                'language_support' => $course->language_support,
                'estimated_duration_minutes' => $course->estimated_duration_minutes,
                'total_points' => $course->total_points,
                'is_offline_available' => $course->is_offline_available,
                'instructor' => $course->instructor,
                'modules' => $formattedModules,
                'quizzes' => $course->quizzes,
                'enrollment' => $enrollment ? [
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'last_accessed_lesson_id' => $enrollment->last_accessed_lesson_id,
                    'completed_at' => $enrollment->completed_at,
                ] : null,
            ],
        ]);
    }

    public function enroll(Request $request, int|string $id, EnrollInCourseAction $action): JsonResponse
    {
        $course = is_numeric($id)
            ? Course::findOrFail($id)
            : Course::where('uuid', $id)->orWhere('slug', $id)->firstOrFail();

        $user = $request->user();
        Gate::authorize('enroll', $course);

        $enrollment = $action->execute($user, $course);

        return response()->json([
            'success' => true,
            'message' => 'Successfully enrolled in course.',
            'data' => $enrollment,
        ]);
    }

    public function publish(Request $request, int|string $id, PublishCourseAction $action): JsonResponse
    {
        $course = is_numeric($id)
            ? Course::findOrFail($id)
            : Course::where('uuid', $id)->orWhere('slug', $id)->firstOrFail();

        Gate::authorize('update', $course);

        $course = $action->execute($course);

        return response()->json([
            'success' => true,
            'message' => 'Course published successfully.',
            'data' => $course,
        ]);
    }
}
