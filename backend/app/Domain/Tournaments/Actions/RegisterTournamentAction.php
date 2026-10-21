<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Teams\Models\Team;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Domain\Tournaments\Services\AntiFraudService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegisterTournamentAction
{
    public function __construct(
        protected AntiFraudService $antiFraudService
    ) {}

    public function execute(User $user, Tournament $tournament, array $data = []): TournamentRegistration
    {
        return DB::transaction(function () use ($user, $tournament, $data) {
            // Lock tournament row to prevent concurrent capacity overshoot
            $tournament = Tournament::where('id', $tournament->id)->lockForUpdate()->first();

            // 1. Check if tournament registration is open
            if ($tournament->status !== 'registration_open' && ! $user->hasRole(['organizer', 'super_admin'])) {
                throw new InvalidArgumentException('Tournament registration is currently closed.');
            }

            // 2. Capacity limit check
            $approvedCount = $tournament->registrations()->whereIn('status', ['pending', 'approved'])->count();
            if ($approvedCount >= $tournament->capacity) {
                throw new InvalidArgumentException('Tournament capacity has already been reached.');
            }

            $isTeam = ! empty($data['team_id']);
            $team = null;

            if ($isTeam) {
                $team = Team::findOrFail($data['team_id']);

                // Verify user is team captain or owner
                $member = $team->members()->where('user_id', $user->id)->first();
                if (! $member || ! in_array($member->role, ['owner', 'captain', 'manager'], true)) {
                    throw new AuthorizationException('Only team captains, managers, or owners can register the team.');
                }

                // Check duplicate team registration
                $existing = TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where('team_id', $team->id)
                    ->exists();

                if ($existing) {
                    throw new InvalidArgumentException('This team is already registered for this tournament.');
                }
            } else {
                // Individual registration
                $existing = TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($existing) {
                    throw new InvalidArgumentException('You are already registered for this tournament.');
                }
            }

            // 3. Entry Type Requirements
            if ($tournament->entry_type === 'invitation') {
                $inviteCode = $data['invitation_code'] ?? null;
                $validCodes = $tournament->settings['invitation_codes'] ?? [];

                if (! in_array($inviteCode, $validCodes, true) && ! $user->hasRole(['organizer', 'super_admin'])) {
                    throw new AuthorizationException('A valid invitation code is required for this tournament.');
                }
            }

            // 4. Eligibility Rules Enforcement
            $this->validateEligibility($user, $tournament, $team);

            // 5. Build Roster Snapshot if Team
            $rosterSnapshot = null;
            if ($team) {
                $rosterMembers = $team->members()->with('user.profile')->get();
                $rosterSnapshot = $rosterMembers->map(fn ($m) => [
                    'user_id' => $m->user_id,
                    'name' => $m->user->name,
                    'handle' => $m->user->profile?->handle,
                    'gamer_tag' => $m->user->profile?->gamer_tag,
                    'role' => $m->role,
                    'is_starter' => $m->is_starter,
                ])->all();
            }

            // Initial status: Free tournaments auto-approved or pending organizer approval
            $status = ($tournament->entry_type === 'free' && ($tournament->settings['auto_approve'] ?? true))
                ? 'approved'
                : 'pending';

            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'user_id' => $isTeam ? null : $user->id,
                'team_id' => $isTeam ? $team->id : null,
                'status' => $status,
                'roster_snapshot' => $rosterSnapshot,
                'qualification_score' => $data['qualification_score'] ?? null,
                'invitation_code' => $data['invitation_code'] ?? null,
            ]);

            // 6. Run Anti-Fraud Scanner
            $this->antiFraudService->scanRegistration($tournament, $registration, $user);

            AuditLogger::log(
                action: 'tournament.registered',
                user: $user,
                entityType: 'tournament_registration',
                entityId: $registration->id,
                metadata: [
                    'tournament_id' => $tournament->id,
                    'team_id' => $team?->id,
                    'status' => $status,
                ]
            );

            return $registration;
        });
    }

    /**
     * Validate participant attributes against tournament eligibility criteria.
     */
    protected function validateEligibility(User $user, Tournament $tournament, ?Team $team = null): void
    {
        $rules = $tournament->eligibility_rules ?? [];
        if (empty($rules)) {
            return;
        }

        $profile = $user->profile;

        // University Restriction
        if (! empty($rules['allowed_universities']) && is_array($rules['allowed_universities'])) {
            $userUni = $team ? $team->university : $profile?->university;
            if (! in_array($userUni, $rules['allowed_universities'], true)) {
                throw new InvalidArgumentException('This tournament is restricted to students from: '.implode(', ', $rules['allowed_universities']));
            }
        }

        // Region Restriction
        if (! empty($rules['allowed_regions']) && is_array($rules['allowed_regions'])) {
            $userRegion = $team ? $team->city : $profile?->location;
            if ($userRegion && ! in_array($userRegion, $rules['allowed_regions'], true)) {
                throw new InvalidArgumentException('This tournament is restricted to participants from: '.implode(', ', $rules['allowed_regions']));
            }
        }

        // Student Verification
        if (! empty($rules['require_student_id']) && $rules['require_student_id'] === true) {
            if (! $profile || empty($profile->student_id_number)) {
                throw new InvalidArgumentException('A verified student ID is required to register for this collegiate tournament.');
            }
        }

        // Platform Check
        if (! empty($rules['allowed_platforms']) && is_array($rules['allowed_platforms'])) {
            $userPlatforms = $profile?->platforms ?? [];
            if (! empty($userPlatforms) && empty(array_intersect($userPlatforms, $rules['allowed_platforms']))) {
                throw new InvalidArgumentException('Supported platforms for this tournament are: '.implode(', ', $rules['allowed_platforms']));
            }
        }
    }
}
