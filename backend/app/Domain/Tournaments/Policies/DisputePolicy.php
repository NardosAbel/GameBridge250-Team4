<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        if ($user->hasRole('super_admin') || $dispute->raised_by_id === $user->id) {
            return true;
        }

        $tournament = $dispute->match->tournament;

        return $tournament->organizer_id === $user->id
            || $tournament->staff()->where('user_id', $user->id)->exists();
    }

    public function resolve(User $user, Dispute $dispute): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $tournament = $dispute->match->tournament;

        return $tournament->organizer_id === $user->id
            || $tournament->staff()->where('user_id', $user->id)->whereIn('role', ['admin', 'co_organizer', 'referee'])->exists()
            || $dispute->match->referee_id === $user->id;
    }
}
