<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Tournaments\Models\Bracket;
use App\Domain\Tournaments\Models\TournamentMatch;

class StandingsService
{
    /**
     * Calculate standings table for a round robin or group stage bracket.
     */
    public function getBracketStandings(Bracket $bracket, ?int $groupNumber = null): array
    {
        $tournament = $bracket->tournament;
        $matchesQuery = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('status', 'completed')
            ->whereHas('bracketNode', function ($q) use ($bracket, $groupNumber): void {
                $q->where('bracket_id', $bracket->id);
                if ($groupNumber !== null) {
                    $q->where('group_number', $groupNumber);
                }
            });

        $completedMatches = $matchesQuery->get();

        // Get all participants
        $participants = $tournament->approvedRegistrations()->with(['team', 'user'])->get();
        $isTeam = $tournament->entry_type === 'team' || $participants->first()?->team_id;

        $table = [];

        foreach ($participants as $p) {
            $id = $isTeam ? $p->team_id : $p->user_id;
            if (! $id) {
                continue;
            }

            $table[$id] = [
                'participant_id' => $id,
                'participant_name' => $isTeam ? ($p->team?->name ?? 'Team #'.$id) : ($p->user?->name ?? 'User #'.$id),
                'participant_type' => $isTeam ? 'team' : 'user',
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'draws' => 0,
                'rounds_won' => 0,
                'rounds_lost' => 0,
                'round_difference' => 0,
                'points' => 0,
            ];
        }

        foreach ($completedMatches as $m) {
            $p1 = $m->participant1_id;
            $p2 = $m->participant2_id;

            if (! isset($table[$p1]) || ! isset($table[$p2])) {
                continue;
            }

            $table[$p1]['matches_played']++;
            $table[$p2]['matches_played']++;

            $table[$p1]['rounds_won'] += $m->participant1_score;
            $table[$p1]['rounds_lost'] += $m->participant2_score;

            $table[$p2]['rounds_won'] += $m->participant2_score;
            $table[$p2]['rounds_lost'] += $m->participant1_score;

            if ($m->winner_id === $p1) {
                $table[$p1]['wins']++;
                $table[$p1]['points'] += 3;
                $table[$p2]['losses']++;
            } elseif ($m->winner_id === $p2) {
                $table[$p2]['wins']++;
                $table[$p2]['points'] += 3;
                $table[$p1]['losses']++;
            } else {
                // Draw
                $table[$p1]['draws']++;
                $table[$p1]['points'] += 1;
                $table[$p2]['draws']++;
                $table[$p2]['points'] += 1;
            }
        }

        foreach ($table as &$row) {
            $row['round_difference'] = $row['rounds_won'] - $row['rounds_lost'];
        }
        unset($row);

        // Sort by Points (desc), then Round Difference (desc), then Rounds Won (desc)
        usort($table, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }
            if ($a['round_difference'] !== $b['round_difference']) {
                return $b['round_difference'] <=> $a['round_difference'];
            }

            return $b['rounds_won'] <=> $a['rounds_won'];
        });

        // Assign numeric ranks
        $rank = 1;
        foreach ($table as &$entry) {
            $entry['rank'] = $rank++;
        }
        unset($entry);

        return array_values($table);
    }
}
