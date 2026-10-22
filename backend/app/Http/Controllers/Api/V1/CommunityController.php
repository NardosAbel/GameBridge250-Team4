<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Communities\Actions\ApproveCommunityMemberAction;
use App\Domain\Communities\Actions\CreateCommunityAction;
use App\Domain\Communities\Actions\JoinCommunityAction;
use App\Domain\Communities\Actions\UpdateCommunityAction;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCommunityRequest;
use App\Http\Requests\Community\UpdateCommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Community::query()
            ->with(['creator.profile'])
            ->withCount('activeMembers');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $communities = $query->latest()->paginate(15);

        return response()->json([
            'data' => CommunityResource::collection($communities),
            'meta' => [
                'current_page' => $communities->currentPage(),
                'last_page' => $communities->lastPage(),
                'total' => $communities->total(),
            ],
        ]);
    }

    public function store(StoreCommunityRequest $request, CreateCommunityAction $action): JsonResponse
    {
        Gate::authorize('create', Community::class);

        $community = $action->execute(
            user: $request->user(),
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Community created successfully.',
            'data' => new CommunityResource($community),
        ], 201);
    }

    public function show(Community $community): JsonResponse
    {
        Gate::authorize('view', $community);

        $community->load(['creator.profile', 'activeMembers.user.profile']);

        return response()->json([
            'data' => new CommunityResource($community),
        ]);
    }

    public function update(UpdateCommunityRequest $request, Community $community, UpdateCommunityAction $action): JsonResponse
    {
        Gate::authorize('update', $community);

        $updated = $action->execute($community, $request->validated());

        return response()->json([
            'message' => 'Community updated successfully.',
            'data' => new CommunityResource($updated),
        ]);
    }

    public function join(Request $request, Community $community, JoinCommunityAction $action): JsonResponse
    {
        $member = $action->execute($request->user(), $community);

        return response()->json([
            'message' => $member->status === 'active' ? 'Joined community successfully.' : 'Join request submitted for review.',
            'data' => $member,
        ], 201);
    }

    public function approveMember(Request $request, CommunityMember $member, ApproveCommunityMemberAction $action): JsonResponse
    {
        Gate::authorize('manageMembers', $member->community);

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
        ]);

        $resolved = $action->execute(
            actor: $request->user(),
            member: $member,
            approved: (bool) $validated['approved']
        );

        return response()->json([
            'message' => $validated['approved'] ? 'Member approved successfully.' : 'Member request declined.',
            'data' => $resolved,
        ]);
    }

    public function posts(Request $request, Community $community): JsonResponse
    {
        Gate::authorize('view', $community);

        $posts = $community->posts()
            ->with(['user.profile', 'media', 'game'])
            ->withCount(['comments', 'reactions', 'shares'])
            ->orderByDesc('pinned')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }
}
