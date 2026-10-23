<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\Community;
use App\Domain\Teams\Models\Team;

class GetOrCreateChatChannelAction
{
    public function forTeam(Team $team): ChatChannel
    {
        return ChatChannel::firstOrCreate(
            [
                'type' => 'team',
                'team_id' => $team->id,
            ],
            [
                'name' => "{$team->name} Team Chat",
            ]
        );
    }

    public function forCommunity(Community $community): ChatChannel
    {
        return ChatChannel::firstOrCreate(
            [
                'type' => 'community',
                'community_id' => $community->id,
            ],
            [
                'name' => "{$community->name} General",
            ]
        );
    }

    public function namedCommunityChannel(Community $community, string $name): ChatChannel
    {
        $name = trim($name);

        return ChatChannel::firstOrCreate(
            [
                'type' => 'community',
                'community_id' => $community->id,
                'name' => $name,
            ]
        );
    }
}
