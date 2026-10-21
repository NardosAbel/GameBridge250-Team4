<?php

namespace App\Policies;

use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;

class TeamPolicy
{
    public function view(?User $user, Team $team): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Team $team): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'captain', 'manager'])
            ->where('status', 'active')
            ->exists();
    }

    public function invite(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }

    public function manageRoster(User $user, Team $team): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'captain'])
            ->where('status', 'active')
            ->exists();
    }

    public function transferOwnership(User $user, Team $team): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $team->leader_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->id === $team->leader_id || $user->hasRole('super_admin');
    }
}
