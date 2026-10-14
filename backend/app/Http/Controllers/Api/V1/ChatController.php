<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Chat\Actions\GetOrCreateChatChannelAction;
use App\Domain\Chat\Actions\SendMessageAction;
use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\Community;
use App\Domain\Teams\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\ChatMessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    public function teamChannel(Team $team, GetOrCreateChatChannelAction $action): JsonResponse
    {
        Gate::authorize('view', $team);

        $channel = $action->forTeam($team);

        return response()->json([
            'data' => [
                'id' => $channel->id,
                'uuid' => $channel->uuid,
                'name' => $channel->name,
                'type' => $channel->type,
                'team_id' => $channel->team_id,
            ],
        ]);
    }

    public function communityChannel(Community $community, GetOrCreateChatChannelAction $action): JsonResponse
    {
        Gate::authorize('view', $community);

        $channel = $action->forCommunity($community);

        return response()->json([
            'data' => [
                'id' => $channel->id,
                'uuid' => $channel->uuid,
                'name' => $channel->name,
                'type' => $channel->type,
                'community_id' => $channel->community_id,
            ],
        ]);
    }

    public function messages(Request $request, ChatChannel $channel): JsonResponse
    {
        Gate::authorize('view', $channel);

        $messages = $channel->messages()
            ->with(['user.profile'])
            ->latest()
            ->paginate(30);

        return response()->json([
            'data' => ChatMessageResource::collection(array_reverse($messages->items())),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function sendMessage(SendMessageRequest $request, ChatChannel $channel, SendMessageAction $action): JsonResponse
    {
        Gate::authorize('sendMessage', $channel);

        $message = $action->execute(
            user: $request->user(),
            channel: $channel,
            message: $request->validated('message'),
            attachmentUrl: $request->validated('attachment_url')
        );

        return response()->json([
            'message' => 'Message sent.',
            'data' => new ChatMessageResource($message),
        ], 201);
    }
}
