<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AssignStaffAction
{
    public function execute(User $actor, Tournament $tournament, int $targetUserId, string $role): TournamentStaff
    {
        $isOrganizer = $tournament->organizer_id === $actor->id || $actor->hasRole('super_admin');

        if (! $isOrganizer) {
            throw new AuthorizationException('Only tournament organizers can assign staff roles.');
        }

        $staff = TournamentStaff::updateOrCreate([
            'tournament_id' => $tournament->id,
            'user_id' => $targetUserId,
        ], [
            'role' => $role,
        ]);

        AuditLogger::log(
            action: 'tournament.staff_assigned',
            user: $actor,
            entityType: 'tournament',
            entityId: $tournament->id,
            metadata: [
                'target_user_id' => $targetUserId,
                'role' => $role,
            ]
        );

        return $staff;
    }
}
