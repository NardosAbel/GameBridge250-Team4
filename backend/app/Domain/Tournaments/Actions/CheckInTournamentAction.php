<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class CheckInTournamentAction
{
    public function execute(User $actor, TournamentRegistration $registration): TournamentRegistration
    {
        $tournament = $registration->tournament;

        // Verify authorization: participant user or team captain or staff
        $isUser = $registration->user_id === $actor->id;
        $isTeamCaptain = false;
        if ($registration->team) {
            $member = $registration->team->members()->where('user_id', $actor->id)->first();
            $isTeamCaptain = $member && in_array($member->role, ['owner', 'captain', 'manager'], true);
        }

        $isStaff = $tournament->staff()->where('user_id', $actor->id)->exists() || $actor->hasRole('super_admin');

        if (! $isUser && ! $isTeamCaptain && ! $isStaff) {
            throw new AuthorizationException('You are not authorized to check in for this participant.');
        }

        if (! $tournament->isCheckInOpen() && ! $isStaff) {
            throw new InvalidArgumentException('Tournament check-in is not currently open.');
        }

        $registration->update([
            'checked_in' => true,
            'checked_in_at' => now(),
        ]);

        AuditLogger::log(
            action: 'tournament.checked_in',
            user: $actor,
            entityType: 'tournament_registration',
            entityId: $registration->id,
            metadata: [
                'registration_id' => $registration->id,
                'tournament_id' => $tournament->id,
            ]
        );

        return $registration;
    }
}
