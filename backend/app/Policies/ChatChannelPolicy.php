<?php

namespace App\Policies;

use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;

class ChatChannelPolicy
{
    public function view(User $user, ChatChannel $channel): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($channel->type === 'team' && $channel->team_id) {
            return TeamMember::where('team_id', $channel->team_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        if ($channel->type === 'community' && $channel->community_id) {
            return CommunityMember::where('community_id', $channel->community_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function sendMessage(User $user, ChatChannel $channel): bool
    {
        return $this->view($user, $channel) && $user->isActive();
    }
}
