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

class ConfirmMatchResultAction
{
    public function __construct(
        protected MatchAdvancementService $advancementService
    ) {}

    public function execute(User $actor, TournamentMatch $match, ?MatchResult $result = null): TournamentMatch
    {
        $result = $result ?? $match->latestResult;
        if (! $result) {
            throw new InvalidArgumentException('No submitted match result found to confirm.');
        }

        if ($match->status === 'disputed' || $result->status === 'disputed' || $match->disputes()->whereIn('status', ['open', 'under_review'])->exists()) {
            throw new InvalidArgumentException('A disputed match cannot be confirmed until the dispute is resolved.');
        }

        $tournament = $match->tournament;
        $isReferee = $tournament->staff()->where('user_id', $actor->id)->whereIn('role', ['admin', 'referee', 'scorekeeper'])->exists()
            || $match->referee_id === $actor->id
            || $actor->hasRole('super_admin');

        $isOpponent = $this->isOpponent($actor, $match, $result);

        if (! $isReferee && ! $isOpponent) {
            throw new AuthorizationException('Only the opponent or a tournament official can confirm this result.');
        }

        return DB::transaction(function () use ($actor, $match, $result) {
            $score1 = $result->participant1_score;
            $score2 = $result->participant2_score;

            $winnerId = null;
            if ($score1 > $score2) {
                $winnerId = $match->participant1_id;
            } elseif ($score2 > $score1) {
                $winnerId = $match->participant2_id;
            }

            $result->update([
                'status' => 'confirmed',
                'confirmed_by_id' => $actor->id,
            ]);

            $match->update([
                'participant1_score' => $score1,
                'participant2_score' => $score2,
                'winner_id' => $winnerId,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            AuditLogger::log(
                action: 'match.result_confirmed',
                user: $actor,
                entityType: 'match',
                entityId: $match->id,
                metadata: [
                    'result_id' => $result->id,
                    'winner_id' => $winnerId,
                ]
            );

            // Advance winner in bracket
            $this->advancementService->advance($match);

            return $match;
        });
    }

    protected function isOpponent(User $actor, TournamentMatch $match, MatchResult $result): bool
    {
        // The submitter cannot confirm their own result (must be the other participant or staff)
        if ($result->submitted_by_id === $actor->id) {
            return false;
        }

        if ($match->participant1_type === 'team') {
            $p1Team = $match->participant1_entity;
            $p2Team = $match->participant2_entity;
            $submitterOnP1 = $p1Team && $p1Team->members()->where('user_id', $result->submitted_by_id)->exists();
            $submitterOnP2 = $p2Team && $p2Team->members()->where('user_id', $result->submitted_by_id)->exists();
            $opposing = null;

            if ($submitterOnP1 && ! $submitterOnP2) {
                $opposing = $p2Team;
            } elseif ($submitterOnP2 && ! $submitterOnP1) {
                $opposing = $p1Team;
            }

            if (! $opposing) {
                return false;
            }

            return $opposing->members()
                ->where('user_id', $actor->id)
                ->whereIn('role', ['owner', 'captain', 'manager'])
                ->exists();
        }

        if ((int) $result->submitted_by_id === (int) $match->participant1_id) {
            return (int) $actor->id === (int) $match->participant2_id;
        }

        if ((int) $result->submitted_by_id === (int) $match->participant2_id) {
            return (int) $actor->id === (int) $match->participant1_id;
        }

        return false;
    }
}
