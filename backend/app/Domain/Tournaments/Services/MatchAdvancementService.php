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
        protected RankingCalculationService $rankingService
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

            // 3. Check if all tournament matches are completed
            $this->checkTournamentCompletion($match->tournament);
        });
    }

    /**
     * Invalidate downstream matches if an upstream match winner is changed/overruled.
     */
    public function revertDownstream(TournamentMatch $match): void
    {
        DB::transaction(function () use ($match) {
            $node = $match->bracketNode;
            if (! $node || ! $node->next_node_id) {
                return;
            }

            $targetMatch = TournamentMatch::where('bracket_node_id', $node->next_node_id)->first();
            if ($targetMatch) {
                $previousWinnerId = $match->winner_id;

                if ($targetMatch->participant1_id === $previousWinnerId) {
                    $targetMatch->update(['participant1_id' => null, 'status' => 'pending']);
                } elseif ($targetMatch->participant2_id === $previousWinnerId) {
                    $targetMatch->update(['participant2_id' => null, 'status' => 'pending']);
                }

                // If target match was completed, recursively revert its downstream matches too!
                if ($targetMatch->status === 'completed') {
                    $targetMatch->update(['winner_id' => null, 'status' => 'pending']);
                    $this->revertDownstream($targetMatch);
                }
            }
        });
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
            // Fill slot 1 or slot 2 based on parity of source node order or first available slot
            if ($sourceNodeOrder % 2 !== 0) {
                $targetMatch->update([
                    'participant1_id' => $participantId,
                    'participant1_type' => $type,
                ]);
            } else {
                $targetMatch->update([
                    'participant2_id' => $participantId,
                    'participant2_type' => $type,
                ]);
            }

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
