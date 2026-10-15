<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Actions\AssignLearningPathAction;
use App\Domain\Education\Actions\CreateLearningPathAction;
use App\Domain\Education\Models\LearningPath;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LearningPathController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LearningPath::with(['courses', 'creator:id,name'])
            ->withCount('courses');

        if ($request->has('university') && ! empty($request->input('university'))) {
            $uni = $request->input('university');
            $query->where(function ($q) use ($uni) {
                $q->where('target_university', $uni)
                    ->orWhereNull('target_university');
            });
        }

        $paths = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $paths,
        ]);
    }

    public function store(Request $request, CreateLearningPathAction $action): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('create', LearningPath::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'banner_url' => ['nullable', 'string', 'max:500'],
            'institution_type' => ['nullable', 'in:university,school,all'],
            'target_university' => ['nullable', 'string', 'max:255'],
            'is_mandatory' => ['nullable', 'boolean'],
            'deadline' => ['nullable', 'date'],
            'courses' => ['nullable', 'array'],
            'courses.*' => ['exists:courses,id'],
        ]);

        $path = $action->execute($user, $validated, $validated['courses'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Learning path created successfully.',
            'data' => $path,
        ], 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $path = is_numeric($id)
            ? LearningPath::findOrFail($id)
            : LearningPath::where('uuid', $id)->orWhere('slug', $id)->firstOrFail();

        Gate::authorize('view', $path);

        $path->load(['courses.lessons', 'creator:id,name']);

        $user = $request->user('sanctum');
        $assignment = null;
        if ($user) {
            $assignment = $path->assignments()->where('user_id', $user->id)->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $path->id,
                'uuid' => $path->uuid,
                'title' => $path->title,
                'title_am' => $path->title_am,
                'slug' => $path->slug,
                'description' => $path->description,
                'description_am' => $path->description_am,
                'banner_url' => $path->banner_url,
                'institution_type' => $path->institution_type,
                'target_university' => $path->target_university,
                'is_mandatory' => $path->is_mandatory,
                'deadline' => $path->deadline,
                'courses' => $path->courses,
                'assignment' => $assignment,
            ],
        ]);
    }

    public function assign(Request $request, int|string $id, AssignLearningPathAction $action): JsonResponse
    {
        $path = is_numeric($id)
            ? LearningPath::findOrFail($id)
            : LearningPath::where('uuid', $id)->firstOrFail();

        $user = $request->user();
        Gate::authorize('assign', $path);

        $validated = $request->validate([
            'university_name' => ['nullable', 'string', 'max:255'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $dueDate = isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : null;

        $assignments = $action->execute(
            $user,
            $path,
            $validated['university_name'] ?? null,
            $validated['user_ids'] ?? [],
            $dueDate
        );

        return response()->json([
            'success' => true,
            'message' => 'Learning path successfully assigned.',
            'data' => [
                'total_assigned' => count($assignments),
                'assignments' => $assignments,
            ],
        ]);
    }
}
