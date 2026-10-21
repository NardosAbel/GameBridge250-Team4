<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Tournaments\Models\BracketNode;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentRegistration;
use Illuminate\Support\Facades\DB;

class MatchAdvancementService
{
    public function __construct(
        protected RankingCalculationService $rankingService,
        protected FormatProgressionService $formatProgressionService,
    ) {}

    /**
     * Advance the winner (and loser in double elim) of a finalized match.
     */
    public function advance(TournamentMatch $match): void
    {
        DB::transaction(function () use ($match) {
            $node = $match->bracketNode;
            if (! $node) {
                return;
            }

            $winnerId = $match->winner_id;
            if (! $winnerId) {
                return;
            }

            $participantType = $match->participant1_type;
            $loserId = ($winnerId === $match->participant1_id) ? $match->participant2_id : $match->participant1_id;

            // 1. Advance Winner to next_node_id
            if ($node->next_node_id) {
                $this->propagateToNextNode($node->next_node_id, $winnerId, $participantType, $node->node_order);
            }

            // 2. In Double Elimination: Drop Loser to loser_next_node_id
            if ($node->loser_next_node_id && $loserId) {
                $this->propagateToNextNode($node->loser_next_node_id, $loserId, $participantType, $node->node_order);
            }

            $this->formatProgressionService->progress($match->tournament->fresh() ?? $match->tournament);

            // 3. Check if all tournament matches are completed
            $this->checkTournamentCompletion($match->tournament->fresh() ?? $match->tournament);
        });
    }

    /**
     * Invalidate downstream matches if an upstream match winner is changed/overruled.
     */
    public function revertDownstream(TournamentMatch $match): void
    {
        DB::transaction(function () use ($match) {
            $node = $match->bracketNode;
            if (! $node) {
                return;
            }

            $loserId = null;
            if ($match->winner_id && (int) $match->winner_id === (int) $match->participant1_id) {
                $loserId = $match->participant2_id;
            } elseif ($match->winner_id && (int) $match->winner_id === (int) $match->participant2_id) {
                $loserId = $match->participant1_id;
            }

            $this->clearPropagatedParticipant($node->next_node_id, $match->winner_id);
            $this->clearPropagatedParticipant($node->loser_next_node_id, $loserId);
        });
    }

    private function clearPropagatedParticipant(?int $nodeId, ?int $participantId): void
    {
        if (! $nodeId || ! $participantId) {
            return;
        }

        $targetMatch = TournamentMatch::where('bracket_node_id', $nodeId)->lockForUpdate()->first();
        if (! $targetMatch) {
            return;
        }

        if ($targetMatch->status === 'completed') {
            $this->revertDownstream($targetMatch);
            $targetMatch->refresh();
        }

        $updates = [];
        if ((int) $targetMatch->participant1_id === (int) $participantId) {
            $updates['participant1_id'] = null;
        }
        if ((int) $targetMatch->participant2_id === (int) $participantId) {
            $updates['participant2_id'] = null;
        }

        if ($updates === [] && $targetMatch->status !== 'completed') {
            return;
        }

        $updates['winner_id'] = null;
        $updates['status'] = 'pending';
        $updates['completed_at'] = null;
        $targetMatch->update($updates);
    }

    /**
     * Propagate a participant into the target node.
     */
    protected function propagateToNextNode(int $targetNodeId, int $participantId, string $type, int $sourceNodeOrder): void
    {
        $targetMatch = TournamentMatch::where('bracket_node_id', $targetNodeId)->first();
        if (! $targetMatch) {
            $targetNode = BracketNode::find($targetNodeId);
            if ($targetNode) {
                $targetMatch = TournamentMatch::create([
                    'tournament_id' => $targetNode->bracket->tournament_id,
                    'bracket_node_id' => $targetNode->id,
                    'match_format' => 'bo1',
                    'round' => $targetNode->round,
                    'match_number' => $targetNode->node_order,
                    'participant1_type' => $type,
                    'participant2_type' => $type,
                    'status' => 'pending',
                ]);
            }
        }

        if ($targetMatch) {
            $preferred = ($sourceNodeOrder % 2 !== 0) ? 'participant1_id' : 'participant2_id';
            $alternate = $preferred === 'participant1_id' ? 'participant2_id' : 'participant1_id';
            $slot = $preferred;
            if ($targetMatch->{$preferred} && (int) $targetMatch->{$preferred} !== (int) $participantId && ! $targetMatch->{$alternate}) {
                $slot = $alternate;
            }

            $targetMatch->update([
                $slot => $participantId,
                str_replace('_id', '_type', $slot) => $type,
            ]);

            // If both participants are present, mark match ready!
            $targetMatch->refresh();
            if ($targetMatch->participant1_id && $targetMatch->participant2_id) {
                $targetMatch->update(['status' => 'ready']);
            }
        }
    }

    /**
     * Check if all matches in tournament are completed, finalize placements & rankings.
     */
    public function checkTournamentCompletion(Tournament $tournament): void
    {
        $hasIncomplete = $tournament->matches()
            ->whereIn('status', ['pending', 'ready', 'live', 'disputed'])
            ->exists();

        if ($hasIncomplete) {
            return;
        }

        // All matches finished -> Complete tournament
        $tournament->update(['status' => 'completed', 'ends_at' => now()]);

        // Find Champion (Winner of final match)
        $finalMatch = $tournament->matches()->orderBy('round', 'desc')->first();
        if ($finalMatch && $finalMatch->winner_id) {
            $isTeam = $finalMatch->participant1_type === 'team';
            $championId = $finalMatch->winner_id;
            $runnerUpId = ($finalMatch->winner_id === $finalMatch->participant1_id)
                ? $finalMatch->participant2_id
                : $finalMatch->participant1_id;

            // Update registration placements
            $pointsDist = $tournament->points_distribution ?? [
                1 => 100,
                2 => 60,
                3 => 40,
                4 => 20,
            ];

            // 1st place
            TournamentRegistration::where('tournament_id', $tournament->id)
                ->where($isTeam ? 'team_id' : 'user_id', $championId)
                ->update(['placement' => 1, 'points_awarded' => $pointsDist[1] ?? 100]);

            // 2nd place
            if ($runnerUpId) {
                TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where($isTeam ? 'team_id' : 'user_id', $runnerUpId)
                    ->update(['placement' => 2, 'points_awarded' => $pointsDist[2] ?? 60]);
            }

            // Distribute points into season rankings
            $this->rankingService->awardTournamentPoints($tournament);
        }
    }
}
