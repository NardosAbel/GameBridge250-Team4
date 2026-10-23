<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Chat\Actions\GetOrCreateChatChannelAction;
use App\Domain\Chat\Actions\SendMessageAction;
use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\ChatMessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $teamIds = TeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('team_id')
            ->merge(Team::query()->where('leader_id', $user->id)->pluck('id'))
            ->unique()
            ->values();

        $communityIds = CommunityMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('community_id');

        $channels = ChatChannel::query()
            ->where(function ($query) use ($teamIds, $communityIds) {
                if ($teamIds->isNotEmpty()) {
                    $query->orWhereIn('team_id', $teamIds);
                }
                if ($communityIds->isNotEmpty()) {
                    $query->orWhereIn('community_id', $communityIds);
                }
            })
            ->when($teamIds->isEmpty() && $communityIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $channels->map(fn (ChatChannel $channel) => $this->channelPayload($channel)),
        ]);
    }

    public function communityChannels(Community $community, GetOrCreateChatChannelAction $action): JsonResponse
    {
        Gate::authorize('view', $community);

        $member = CommunityMember::query()
            ->where('community_id', $community->id)
            ->where('user_id', request()->user()->id)
            ->where('status', 'active')
            ->exists();

        if (! $member && ! request()->user()->hasAnyRole(['super_admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Join this community to read its channels.',
            ], 403);
        }

        $channels = ChatChannel::query()
            ->where('community_id', $community->id)
            ->orderBy('name')
            ->get();

        if ($channels->isEmpty()) {
            $channels = collect([$action->forCommunity($community)]);
        }

        return response()->json([
            'data' => $channels->map(fn (ChatChannel $channel) => $this->channelPayload($channel)),
        ]);
    }

    public function storeCommunityChannel(Request $request, Community $community, GetOrCreateChatChannelAction $action): JsonResponse
    {
        Gate::authorize('update', $community);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
        ]);

        $channel = $action->namedCommunityChannel($community, $validated['name']);

        return response()->json([
            'message' => 'Channel ready.',
            'data' => $this->channelPayload($channel),
        ], 201);
    }

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

    /**
     * @return array<string, mixed>
     */
    private function channelPayload(ChatChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'uuid' => $channel->uuid,
            'name' => $channel->name,
            'type' => $channel->type,
            'team_id' => $channel->team_id,
            'community_id' => $channel->community_id,
        ];
    }
}
