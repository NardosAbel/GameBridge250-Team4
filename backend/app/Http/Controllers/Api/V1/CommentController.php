<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Social\Actions\AddCommentAction;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Post;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StoreCommentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Post $post): JsonResponse
    {
        Gate::authorize('view', $post);

        $comments = $post->comments()
            ->with(['user.profile', 'replies.user.profile'])
            ->whereNull('parent_id')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $comments->items(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request, Post $post, AddCommentAction $action): JsonResponse
    {
        Gate::authorize('view', $post);

        $comment = $action->execute(
            user: $request->user(),
            post: $post,
            content: $request->validated('content'),
            parentId: $request->validated('parent_id')
        );

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $comment,
        ], 201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
