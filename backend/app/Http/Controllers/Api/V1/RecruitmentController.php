<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Recruitment\Actions\CloseRecruitmentPostAction;
use App\Domain\Recruitment\Actions\CreateRecruitmentPostAction;
use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreRecruitmentPostRequest;
use App\Http\Resources\RecruitmentPostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RecruitmentPost::query()
            ->with(['user.profile', 'team', 'game'])
            ->where('status', 'open');

        foreach (['type', 'game_id', 'rank_tier', 'role', 'platform', 'age_group'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->query('location')}%");
        }

        if ($request->filled('university')) {
            $query->where('university', 'like', "%{$request->query('university')}%");
        }

        $posts = $query->latest()->paginate(15);

        return response()->json([
            'data' => RecruitmentPostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(StoreRecruitmentPostRequest $request, CreateRecruitmentPostAction $action): JsonResponse
    {
        $post = $action->execute(
            user: $request->user(),
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Recruitment listing published successfully.',
            'data' => new RecruitmentPostResource($post->load(['user.profile', 'team', 'game'])),
        ], 201);
    }

    public function close(Request $request, RecruitmentPost $post, CloseRecruitmentPostAction $action): JsonResponse
    {
        $closed = $action->execute($request->user(), $post);

        return response()->json([
            'message' => 'Recruitment listing closed successfully.',
            'data' => new RecruitmentPostResource($closed),
        ]);
    }
}
