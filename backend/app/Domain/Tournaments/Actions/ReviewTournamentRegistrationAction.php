<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ReviewTournamentRegistrationAction
{
    public function __construct(
        protected RegisterTournamentAction $registerTournamentAction
    ) {}

    public function execute(User $actor, TournamentRegistration $registration, string $decision): TournamentRegistration
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Choose approve or reject.');
        }

        return DB::transaction(function () use ($actor, $registration, $decision) {
            $tournament = Tournament::query()->whereKey($registration->tournament_id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $tournament);

            $registration = TournamentRegistration::query()
                ->whereKey($registration->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($registration->status !== 'pending') {
                throw new InvalidArgumentException('Only a pending application can be reviewed.');
            }

            if ($decision === 'approved') {
                $approved = TournamentRegistration::query()
                    ->where('tournament_id', $tournament->id)
                    ->where('status', 'approved')
                    ->lockForUpdate()
                    ->count();

                if ($approved >= $tournament->capacity) {
                    throw new InvalidArgumentException('This competition is already at capacity.');
                }

                $registration->loadMissing(['user', 'team.leader']);
                $applicant = $registration->user ?? $registration->team?->leader;
                if ($applicant instanceof User) {
                    $issue = $this->registerTournamentAction->eligibilityIssue($applicant, $tournament, $registration->team);
                    if ($issue !== null) {
                        throw new InvalidArgumentException($issue);
                    }
                }
            }

            $registration->update(['status' => $decision]);

            AuditLogger::log(
                action: 'tournament.registration_reviewed',
                user: $actor,
                entityType: 'tournament_registration',
                entityId: $registration->id,
                metadata: [
                    'tournament_id' => $tournament->id,
                    'decision' => $decision,
                ]
            );

            return $registration->fresh();
        });
    }
}
