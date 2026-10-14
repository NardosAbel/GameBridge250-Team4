<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Social\Actions\CreatePostAction;
use App\Domain\Social\Actions\VotePollAction;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Services\FeedService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(Request $request, FeedService $feedService): JsonResponse
    {
        $feedType = $request->query('feed', 'personalized');
        $filters = $request->only(['game_id', 'community_id', 'team_id', 'hashtag', 'per_page']);

        $posts = $feedService->getFeed(
            user: $request->user(),
            type: $feedType,
            filters: $filters
        );

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(StorePostRequest $request, CreatePostAction $action): JsonResponse
    {
        Gate::authorize('create', Post::class);

        $files = $request->file('media', []);
        $post = $action->execute(
            user: $request->user(),
            data: $request->validated(),
            files: is_array($files) ? $files : [$files]
        );

        return response()->json([
            'message' => 'Post created successfully.',
            'data' => new PostResource($post),
        ], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('view', $post);

        $post->load(['user.profile', 'media', 'game', 'community', 'team'])
            ->loadCount(['comments', 'reactions', 'shares']);

        return response()->json([
            'data' => new PostResource($post),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    public function votePoll(Request $request, Post $post, VotePollAction $action): JsonResponse
    {
        $validated = $request->validate([
            'option_index' => ['required', 'integer'],
        ]);

        $updatedPost = $action->execute(
            user: $request->user(),
            post: $post,
            optionIndex: (int) $validated['option_index']
        );

        return response()->json([
            'message' => 'Vote recorded successfully.',
            'data' => new PostResource($updatedPost),
        ]);
    }

    public function pin(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('pin', $post);

        $post->update(['pinned' => ! $post->pinned]);

        return response()->json([
            'message' => $post->pinned ? 'Post pinned successfully.' : 'Post unpinned successfully.',
            'pinned' => $post->pinned,
        ]);
    }
}
