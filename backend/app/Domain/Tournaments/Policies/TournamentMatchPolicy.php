<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;

class TournamentMatchPolicy
{
    public function view(?User $user, TournamentMatch $match): bool
    {
        return true;
    }

    /**
     * Determine whether the user can see restricted room ID and password.
     */
    public function viewRoom(User $user, TournamentMatch $match): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $tournament = $match->tournament;
        if ($tournament->organizer_id === $user->id) {
            return true;
        }

        if ($tournament->staff()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($match->referee_id === $user->id) {
            return true;
        }

        // Check if user is participant 1 or 2
        return $this->isParticipant($user, $match);
    }

    public function checkIn(User $user, TournamentMatch $match): bool
    {
        return $this->isParticipant($user, $match);
    }

    public function submitResult(User $user, TournamentMatch $match): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $tournament = $match->tournament;
        if ($tournament->staff()->where('user_id', $user->id)->whereIn('role', ['admin', 'referee', 'scorekeeper'])->exists()) {
            return true;
        }

        if ($match->referee_id === $user->id) {
            return true;
        }

        return $this->isParticipant($user, $match);
    }

    public function overrideResult(User $user, TournamentMatch $match): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $tournament = $match->tournament;

        return $tournament->organizer_id === $user->id
            || $tournament->staff()->where('user_id', $user->id)->whereIn('role', ['admin', 'co_organizer'])->exists();
    }

    public function dispute(User $user, TournamentMatch $match): bool
    {
        return $this->isParticipant($user, $match);
    }

    protected function isParticipant(User $user, TournamentMatch $match): bool
    {
        if ($match->participant1_type === 'team') {
            $p1Team = $match->participant1_entity;
            if ($p1Team && $p1Team->members()->where('user_id', $user->id)->exists()) {
                return true;
            }
        } elseif ($match->participant1_id === $user->id) {
            return true;
        }

        if ($match->participant2_type === 'team') {
            $p2Team = $match->participant2_entity;
            if ($p2Team && $p2Team->members()->where('user_id', $user->id)->exists()) {
                return true;
            }
        } elseif ($match->participant2_id === $user->id) {
            return true;
        }

        return false;
    }
}
