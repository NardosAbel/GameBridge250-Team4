<?php

namespace App\Domain\Communities\Actions;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;
use InvalidArgumentException;

class JoinCommunityAction
{
    public function execute(User $user, Community $community): CommunityMember
    {
        $existing = CommunityMember::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'active') {
                throw new InvalidArgumentException('You are already a member of this community.');
            }
            if ($existing->status === 'pending') {
                throw new InvalidArgumentException('Your join request is already pending approval.');
            }
        }

        $status = $community->visibility === 'public' ? 'active' : 'pending';

        return CommunityMember::updateOrCreate(
            [
                'community_id' => $community->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'member',
                'status' => $status,
                'joined_at' => $status === 'active' ? now() : null,
            ]
        );
    }
}
