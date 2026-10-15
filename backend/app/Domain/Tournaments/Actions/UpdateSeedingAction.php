<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateSeedingAction
{
    /**
     * Set seeds for tournament registrations.
     * $seedMappings format: [registration_id => seed_integer]
     */
    public function execute(User $actor, Tournament $tournament, array $seedMappings): void
    {
        $isStaff = $tournament->organizer_id === $actor->id
            || $tournament->staff()->where('user_id', $actor->id)->exists()
            || $actor->hasRole('super-admin');

        if (! $isStaff) {
            throw new AuthorizationException('Only tournament organizers or staff can manage seeds.');
        }

        DB::transaction(function () use ($tournament, $seedMappings, $actor) {
            foreach ($seedMappings as $regId => $seed) {
                TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where('id', $regId)
                    ->update(['seed' => $seed]);
            }

            AuditLogger::log(
                action: 'tournament.seeding_updated',
                user: $actor,
                entityType: 'tournament',
                entityId: $tournament->id,
                metadata: [
                    'seeds' => $seedMappings,
                ]
            );
        });
    }
}
