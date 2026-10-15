<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentFraudSignal;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AntiFraudService
{
    /**
     * Check for fraudulent or suspicious registration patterns.
     */
    public function scanRegistration(Tournament $tournament, TournamentRegistration $registration, ?User $user = null): array
    {
        $signals = [];
        $currentIp = Request::ip();

        // 1. IP Collision: check if another registration in this tournament came from the exact same IP
        if ($currentIp && $currentIp !== '127.0.0.1') {
            $ipCollision = TournamentFraudSignal::where('tournament_id', $tournament->id)
                ->where('registration_id', '!=', $registration->id)
                ->whereJsonContains('metadata->ip', $currentIp)
                ->first();

            if ($ipCollision) {
                $signals[] = TournamentFraudSignal::create([
                    'tournament_id' => $tournament->id,
                    'registration_id' => $registration->id,
                    'user_id' => $user?->id,
                    'signal_type' => 'duplicate_ip',
                    'severity' => 'medium',
                    'metadata' => [
                        'ip' => $currentIp,
                        'colliding_registration_id' => $ipCollision->registration_id,
                    ],
                ]);
            }
        }

        $studentId = $user?->profile?->school_id_number ?? $user?->profile?->student_id_number;
        if ($user && $user->profile && ! empty($studentId)) {
            $duplicateStudent = TournamentRegistration::where('tournament_id', $tournament->id)
                ->where('id', '!=', $registration->id)
                ->whereHas('user.profile', function ($q) use ($studentId): void {
                    $q->where('school_id_number', $studentId);
                })
                ->first();

            if ($duplicateStudent) {
                $signals[] = TournamentFraudSignal::create([
                    'tournament_id' => $tournament->id,
                    'registration_id' => $registration->id,
                    'user_id' => $user->id,
                    'signal_type' => 'duplicate_student_id',
                    'severity' => 'high',
                    'metadata' => [
                        'student_id_number' => $studentId,
                        'colliding_registration_id' => $duplicateStudent->id,
                    ],
                ]);
            }
        }

        // 3. Multi-Team Roster Tampering: ensure players on a team roster aren't on multiple rosters in the same tournament
        if ($registration->roster_snapshot && is_array($registration->roster_snapshot)) {
            $rosterUserIds = array_filter(array_column($registration->roster_snapshot, 'user_id'));

            if (! empty($rosterUserIds)) {
                $otherRegistrations = TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where('id', '!=', $registration->id)
                    ->whereNotNull('roster_snapshot')
                    ->get();

                foreach ($otherRegistrations as $otherReg) {
                    $otherUserIds = array_filter(array_column($otherReg->roster_snapshot ?? [], 'user_id'));
                    $intersection = array_intersect($rosterUserIds, $otherUserIds);

                    if (! empty($intersection)) {
                        $signals[] = TournamentFraudSignal::create([
                            'tournament_id' => $tournament->id,
                            'registration_id' => $registration->id,
                            'user_id' => $user?->id,
                            'signal_type' => 'multi_team_roster',
                            'severity' => 'critical',
                            'metadata' => [
                                'conflicting_user_ids' => array_values($intersection),
                                'colliding_registration_id' => $otherReg->id,
                            ],
                        ]);
                        break;
                    }
                }
            }
        }

        // Attach signal summary to registration
        if (! empty($signals)) {
            $flags = array_map(fn ($s) => [
                'id' => $s->id,
                'signal_type' => $s->signal_type,
                'severity' => $s->severity,
            ], $signals);

            $registration->update(['anti_fraud_flags' => $flags]);
        }

        return $signals;
    }
}
