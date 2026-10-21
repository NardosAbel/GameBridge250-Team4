<?php

namespace App\Domain\Communities\Actions;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateCommunityAction
{
    public function execute(User $user, array $data): Community
    {
        if ($user->isBanned() || $user->isSuspended() || $user->isMuted()) {
            throw new InvalidArgumentException('Suspended or banned users cannot create communities.');
        }

        return DB::transaction(function () use ($user, $data) {
            $community = Community::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'interest', // school, game, city, venue, interest, creator
                'visibility' => $data['visibility'] ?? 'public', // public, private, approval
                'creator_id' => $user->id,
                'avatar_url' => $data['avatar_url'] ?? null,
                'banner_url' => $data['banner_url'] ?? null,
            ]);

            CommunityMember::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $community->load(['creator', 'activeMembers.user.profile']);
        });
    }
}
