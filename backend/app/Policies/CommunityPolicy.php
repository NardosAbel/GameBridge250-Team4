<?php

namespace App\Policies;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;

class CommunityPolicy
{
    public function view(?User $user, Community $community): bool
    {
        if ($community->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->id === $community->creator_id || $user->hasAnyRole(['super_admin', 'moderator'])) {
            return true;
        }

        return CommunityMember::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Community $community): bool
    {
        if ($user->id === $community->creator_id || $user->hasRole('super_admin')) {
            return true;
        }

        return CommunityMember::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'moderator'])
            ->where('status', 'active')
            ->exists();
    }

    public function manageMembers(User $user, Community $community): bool
    {
        return $this->update($user, $community);
    }

    public function delete(User $user, Community $community): bool
    {
        return $user->id === $community->creator_id || $user->hasRole('super_admin');
    }
}
