<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateMatchRoomAction
{
    public function execute(User $actor, TournamentMatch $match, array $data): TournamentMatch
    {
        $tournament = $match->tournament;
        $isStaff = $tournament->organizer_id === $actor->id
            || $tournament->staff()->where('user_id', $actor->id)->exists()
            || $match->referee_id === $actor->id
            || $actor->hasRole('super-admin');

        if (! $isStaff) {
            throw new AuthorizationException('Only tournament staff or referees can update game room credentials.');
        }

        $match->update([
            'room_id' => $data['room_id'] ?? $match->room_id,
            'room_password' => $data['room_password'] ?? $match->room_password,
            'room_server' => $data['room_server'] ?? $match->room_server,
            'room_instructions' => $data['room_instructions'] ?? $match->room_instructions,
        ]);

        AuditLogger::log(
            action: 'match.room_updated',
            user: $actor,
            entityType: 'match',
            entityId: $match->id,
            metadata: [
                'room_id' => $match->room_id,
                'room_server' => $match->room_server,
            ]
        );

        return $match;
    }
}
