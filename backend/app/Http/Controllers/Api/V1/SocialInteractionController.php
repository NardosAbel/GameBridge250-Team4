<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Communities\Models\Community;
use App\Domain\Social\Actions\SharePostAction;
use App\Domain\Social\Actions\ToggleBlockUserAction;
use App\Domain\Social\Actions\ToggleBookmarkAction;
use App\Domain\Social\Actions\ToggleFollowAction;
use App\Domain\Social\Actions\ToggleMuteUserAction;
use App\Domain\Social\Actions\ToggleReactionAction;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Post;
use App\Domain\Teams\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\ReactionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SocialInteractionController extends Controller
{
    public function toggleReaction(ReactionRequest $request, string $type, int $id, ToggleReactionAction $action): JsonResponse
    {
        $model = match ($type) {
            'post' => Post::findOrFail($id),
            'comment' => Comment::findOrFail($id),
            default => throw new InvalidArgumentException('Unsupported reactable type.'),
        };

        $result = $action->execute(
            user: $request->user(),
            reactable: $model,
            type: $request->validated('type')
        );

        return response()->json($result);
    }

    public function toggleFollow(Request $request, string $type, int $id, ToggleFollowAction $action): JsonResponse
    {
        $model = match ($type) {
            'user' => User::findOrFail($id),
            'team' => Team::findOrFail($id),
            'community' => Community::findOrFail($id),
            default => throw new InvalidArgumentException('Unsupported followable type.'),
        };

        $result = $action->execute(
            follower: $request->user(),
            followable: $model
        );

        return response()->json($result);
    }

    public function share(Request $request, Post $post, SharePostAction $action): JsonResponse
    {
        $share = $action->execute(
            user: $request->user(),
            post: $post,
            comment: $request->input('comment')
        );

        return response()->json([
            'message' => 'Post shared successfully.',
            'data' => $share,
        ], 201);
    }

    public function toggleBookmark(Request $request, string $type, int $id, ToggleBookmarkAction $action): JsonResponse
    {
        $model = match ($type) {
            'post' => Post::findOrFail($id),
            default => throw new InvalidArgumentException('Unsupported bookmarkable type.'),
        };

        $result = $action->execute(
            user: $request->user(),
            bookmarkable: $model
        );

        return response()->json($result);
    }

    public function toggleBlock(Request $request, User $user, ToggleBlockUserAction $action): JsonResponse
    {
        $result = $action->execute(
            user: $request->user(),
            targetUser: $user
        );

        return response()->json($result);
    }

    public function toggleMute(Request $request, User $user, ToggleMuteUserAction $action): JsonResponse
    {
        $result = $action->execute(
            user: $request->user(),
            targetUser: $user
        );

        return response()->json($result);
    }
}
