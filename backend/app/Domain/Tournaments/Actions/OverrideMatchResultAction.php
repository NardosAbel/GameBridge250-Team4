<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\MatchResult;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Services\MatchAdvancementService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OverrideMatchResultAction
{
    public function __construct(
        protected MatchAdvancementService $advancementService
    ) {}

    public function execute(User $actor, TournamentMatch $match, array $data): TournamentMatch
    {
        $tournament = $match->tournament;
        $isOrganizer = $tournament->organizer_id === $actor->id
            || $tournament->staff()->where('user_id', $actor->id)->whereIn('role', ['admin', 'co_organizer', 'referee'])->exists()
            || $match->referee_id === $actor->id
            || $actor->hasRole('super_admin');

        if (! $isOrganizer) {
            throw new AuthorizationException('Only tournament organizers, administrators, or referees can override match results.');
        }

        $score1 = (int) $data['participant1_score'];
        $score2 = (int) $data['participant2_score'];
        $winnerId = (int) $data['winner_id'];

        if ($winnerId !== $match->participant1_id && $winnerId !== $match->participant2_id) {
            throw new InvalidArgumentException('Winner ID must match one of the match participants.');
        }

        return DB::transaction(function () use ($actor, $match, $score1, $score2, $winnerId, $data) {
            $previousWinner = $match->winner_id;

            // If winner changed on an already-completed match, revert downstream nodes first!
            if ($match->status === 'completed' && $previousWinner && $previousWinner !== $winnerId) {
                $this->advancementService->revertDownstream($match);
            }

            // Create override result entry
            $result = MatchResult::create([
                'match_id' => $match->id,
                'submitted_by_id' => $actor->id,
                'confirmed_by_id' => $actor->id,
                'participant1_score' => $score1,
                'participant2_score' => $score2,
                'status' => 'overridden',
                'notes' => $data['reason'] ?? 'Organizer override',
            ]);

            // Resolve any open disputes on this match
            $match->disputes()->whereIn('status', ['open', 'under_review'])->update([
                'status' => 'resolved',
                'resolution_notes' => 'Resolved by organizer override: '.($data['reason'] ?? 'No notes provided'),
                'action_taken' => 'overturn_result',
                'resolved_at' => now(),
            ]);

            $match->update([
                'participant1_score' => $score1,
                'participant2_score' => $score2,
                'winner_id' => $winnerId,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            AuditLogger::log(
                action: 'match.result_overridden',
                user: $actor,
                entityType: 'match',
                entityId: $match->id,
                metadata: [
                    'result_id' => $result->id,
                    'winner_id' => $winnerId,
                    'reason' => $data['reason'] ?? 'Organizer decision',
                ]
            );

            // Advance new winner downstream
            $this->advancementService->advance($match);

            return $match;
        });
    }
}
