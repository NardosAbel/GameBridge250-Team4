<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class MatchCheckInAction
{
    /**
     * Mark participant checked in and ready up for a match.
     */
    public function execute(User $actor, TournamentMatch $match): TournamentMatch
    {
        $slot = $this->resolveParticipantSlot($actor, $match);

        if ($slot === null) {
            throw new AuthorizationException('You are not a participant in this match.');
        }

        if ($match->status === 'completed') {
            throw new InvalidArgumentException('This match has already been completed.');
        }

        if ($slot === 1) {
            $match->update([
                'participant1_checked_in' => true,
                'participant1_ready' => true,
            ]);
        } else {
            $match->update([
                'participant2_checked_in' => true,
                'participant2_ready' => true,
            ]);
        }

        $match->refresh();

        // If both participants are ready, transition match to live!
        if ($match->participant1_ready && $match->participant2_ready && $match->status === 'ready') {
            $match->update(['status' => 'live']);
        }

        AuditLogger::log(
            action: 'match.ready_up',
            user: $actor,
            entityType: 'match',
            entityId: $match->id,
            metadata: [
                'slot' => $slot,
                'match_status' => $match->status,
            ]
        );

        return $match;
    }

    protected function resolveParticipantSlot(User $actor, TournamentMatch $match): ?int
    {
        if ($match->participant1_type === 'team') {
            $p1Team = $match->participant1_entity;
            if ($p1Team && $p1Team->members()->where('user_id', $actor->id)->exists()) {
                return 1;
            }
        } elseif ($match->participant1_id === $actor->id) {
            return 1;
        }

        if ($match->participant2_type === 'team') {
            $p2Team = $match->participant2_entity;
            if ($p2Team && $p2Team->members()->where('user_id', $actor->id)->exists()) {
                return 2;
            }
        } elseif ($match->participant2_id === $actor->id) {
            return 2;
        }

        return null;
    }
}
