<?php

namespace App\Policies;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Social\Models\Post;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;

class PostPolicy
{
    public function view(?User $user, Post $post): bool
    {
        if ($post->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->id === $post->user_id || $user->hasAnyRole(['admin', 'moderator'])) {
            return true;
        }

        if ($post->community_id) {
            return CommunityMember::where('community_id', $post->community_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        if ($post->team_id) {
            return TeamMember::where('team_id', $post->team_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->hasRole('admin');
    }

    public function delete(User $user, Post $post): bool
    {
        if ($user->id === $post->user_id || $user->hasAnyRole(['admin', 'moderator'])) {
            return true;
        }

        if ($post->community_id) {
            return CommunityMember::where('community_id', $post->community_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'active')
                ->exists();
        }

        if ($post->team_id) {
            return TeamMember::where('team_id', $post->team_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'captain'])
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function pin(User $user, Post $post): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($post->community_id) {
            return CommunityMember::where('community_id', $post->community_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'active')
                ->exists();
        }

        if ($post->team_id) {
            return TeamMember::where('team_id', $post->team_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'captain'])
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }
}
