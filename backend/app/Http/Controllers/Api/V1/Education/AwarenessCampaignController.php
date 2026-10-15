<?php

namespace App\Http\Controllers\Api\V1\Education;

use App\Domain\Education\Actions\CreateAwarenessCampaignAction;
use App\Domain\Education\Enums\CourseCategoryEnum;
use App\Domain\Education\Models\AwarenessCampaign;
use App\Domain\Education\Services\CampaignAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AwarenessCampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AwarenessCampaign::with(['creator:id,name', 'linkedCourse:id,uuid,title,title_am', 'linkedQuiz:id,uuid,title,title_am']);

        if ($request->has('category') && ! empty($request->input('category'))) {
            $query->where('category', $request->input('category'));
        }

        $user = $request->user('sanctum');
        if (! $user || ! ($user->hasRole('super_admin') || $user->hasRole('moderator'))) {
            $query->where('status', 'published');
        }

        $campaigns = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    public function store(Request $request, CreateAwarenessCampaignAction $action): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('create', AwarenessCampaign::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(CourseCategoryEnum::values())],
            'description' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'banner_url' => ['nullable', 'string', 'max:500'],
            'content_blocks' => ['nullable', 'array'],
            'shareable_assets' => ['nullable', 'array'],
            'linked_quiz_id' => ['nullable', 'exists:quizzes,id'],
            'linked_course_id' => ['nullable', 'exists:courses,id'],
            'status' => ['nullable', 'in:draft,scheduled,published,completed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'target_institutions' => ['nullable', 'array'],
        ]);

        $campaign = $action->execute($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Awareness campaign created successfully.',
            'data' => $campaign,
        ], 201);
    }

    public function show(int|string $id, CampaignAnalyticsService $analyticsService): JsonResponse
    {
        $campaign = is_numeric($id)
            ? AwarenessCampaign::findOrFail($id)
            : AwarenessCampaign::where('uuid', $id)->orWhere('slug', $id)->firstOrFail();

        Gate::authorize('view', $campaign);

        $analyticsService->recordView($campaign);

        $campaign->load(['creator:id,name', 'linkedCourse', 'linkedQuiz']);

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    public function share(Request $request, int|string $id, CampaignAnalyticsService $analyticsService): JsonResponse
    {
        $campaign = is_numeric($id)
            ? AwarenessCampaign::findOrFail($id)
            : AwarenessCampaign::where('uuid', $id)->firstOrFail();

        $platform = $request->input('platform', 'general');
        $analyticsService->recordShare($campaign, $platform);

        return response()->json([
            'success' => true,
            'message' => 'Share recorded successfully.',
            'data' => [
                'analytics' => $campaign->fresh()->analytics,
            ],
        ]);
    }

    public function analytics(int|string $id): JsonResponse
    {
        $campaign = is_numeric($id)
            ? AwarenessCampaign::findOrFail($id)
            : AwarenessCampaign::where('uuid', $id)->firstOrFail();

        Gate::authorize('update', $campaign);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'analytics' => $campaign->analytics,
            ],
        ]);
    }
}
