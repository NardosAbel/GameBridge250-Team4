<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Tournament $tournament): bool
    {
        if ($tournament->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin') || $tournament->organizer_id === $user->id) {
            return true;
        }

        if ($tournament->staff()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $tournament->registrations()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['organizer', 'super_admin']) || $user->can('tournaments.create');
    }

    public function update(User $user, Tournament $tournament): bool
    {
        if ($user->hasRole('super_admin') || $tournament->organizer_id === $user->id) {
            return true;
        }

        return $tournament->staff()
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'co_organizer'])
            ->exists();
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->hasRole('super_admin') || $tournament->organizer_id === $user->id;
    }

    public function generateBracket(User $user, Tournament $tournament): bool
    {
        return $this->update($user, $tournament);
    }

    public function assignStaff(User $user, Tournament $tournament): bool
    {
        return $user->hasRole('super_admin') || $tournament->organizer_id === $user->id;
    }
}
