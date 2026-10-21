<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Tournaments\Models\Bracket;
use App\Domain\Tournaments\Models\BracketNode;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BracketGenerationService
{
    /**
     * Generate complete bracket structure for a tournament.
     */
    public function generate(Tournament $tournament): Bracket
    {
        return DB::transaction(function () use ($tournament) {
            // Delete existing brackets/matches if any
            foreach ($tournament->brackets as $b) {
                TournamentMatch::where('tournament_id', $tournament->id)->delete();
                $b->nodes()->delete();
                $b->delete();
            }

            // Fetch approved participants sorted by seed (null seeds at the end)
            $participants = $tournament->approvedRegistrations()
                ->orderByRaw('CASE WHEN seed IS NULL THEN 1 ELSE 0 END, seed ASC, id ASC')
                ->get();

            if ($participants->count() < 2) {
                throw new InvalidArgumentException('At least 2 approved participants are required to generate brackets.');
            }

            return match ($tournament->format) {
                'double_elimination' => $this->generateDoubleElimination($tournament, $participants),
                'round_robin' => $this->generateRoundRobin($tournament, $participants),
                'swiss' => $this->generateSwiss($tournament, $participants),
                'group_knockout' => $this->generateGroupKnockout($tournament, $participants),
                default => $this->generateSingleElimination($tournament, $participants),
            };
        });
    }

    /**
     * Generate Single Elimination Bracket with Seeded Byes and Auto-Advancement.
     */
    public function generateSingleElimination(Tournament $tournament, Collection $participants): Bracket
    {
        $bracket = Bracket::create([
            'tournament_id' => $tournament->id,
            'name' => 'Main Bracket',
            'stage' => 'playoffs',
            'type' => 'single_elimination',
            'status' => 'active',
        ]);

        $n = $participants->count();
        $powerOfTwo = 1;
        while ($powerOfTwo < $n) {
            $powerOfTwo *= 2;
        }

        $totalRounds = (int) log($powerOfTwo, 2);
        $nodesByRound = [];

        // 1. Create all BracketNode slots from round 1 up to finals
        $matchesInRound = $powerOfTwo / 2;
        for ($r = 1; $r <= $totalRounds; $r++) {
            $nodesByRound[$r] = [];
            for ($o = 1; $o <= $matchesInRound; $o++) {
                $node = BracketNode::create([
                    'bracket_id' => $bracket->id,
                    'round' => $r,
                    'node_order' => $o,
                ]);
                $nodesByRound[$r][$o] = $node;
            }
            $matchesInRound /= 2;
        }

        // 2. Link each node to its parent next_node_id
        for ($r = 1; $r < $totalRounds; $r++) {
            foreach ($nodesByRound[$r] as $order => $node) {
                $nextOrder = (int) ceil($order / 2);
                if (isset($nodesByRound[$r + 1][$nextOrder])) {
                    $node->update(['next_node_id' => $nodesByRound[$r + 1][$nextOrder]->id]);
                }
            }
        }

        // 3. Generate standard tournament seed seeding order
        $seedingOrder = $this->getSeedingOrder($powerOfTwo);

        // Map seeds to participant IDs or null (for BYEs)
        $seededSlots = [];
        for ($i = 0; $i < $powerOfTwo; $i++) {
            $seedNum = $seedingOrder[$i];
            $seededSlots[$i] = $participants->get($seedNum - 1); // null if bye
        }

        // 4. Create Round 1 Matches
        $round1Matches = $powerOfTwo / 2;
        $participantType = $tournament->entry_type === 'team' || $participants->first()->team_id ? 'team' : 'user';

        for ($i = 0; $i < $round1Matches; $i++) {
            $node = $nodesByRound[1][$i + 1];
            $p1 = $seededSlots[$i * 2];
            $p2 = $seededSlots[$i * 2 + 1];

            $isBye = ($p1 === null || $p2 === null);
            $winnerId = null;
            $status = 'pending';
            $score1 = 0;
            $score2 = 0;

            if ($isBye) {
                // If one participant is BYE, the other participant automatically wins!
                $status = 'completed';
                $node->update(['is_bye' => true]);
                if ($p1 !== null) {
                    $winnerId = $participantType === 'team' ? $p1->team_id : $p1->user_id;
                    $score1 = 1;
                } elseif ($p2 !== null) {
                    $winnerId = $participantType === 'team' ? $p2->team_id : $p2->user_id;
                    $score2 = 1;
                }
            } else {
                $status = 'ready';
            }

            $match = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'bracket_node_id' => $node->id,
                'match_format' => $tournament->match_format ?? 'bo1',
                'round' => 1,
                'match_number' => $i + 1,
                'participant1_id' => $p1 ? ($participantType === 'team' ? $p1->team_id : $p1->user_id) : null,
                'participant2_id' => $p2 ? ($participantType === 'team' ? $p2->team_id : $p2->user_id) : null,
                'participant1_type' => $participantType,
                'participant2_type' => $participantType,
                'winner_id' => $winnerId,
                'status' => $status,
                'participant1_score' => $score1,
                'participant2_score' => $score2,
                'is_bye' => $isBye,
                'scheduled_at' => $tournament->starts_at,
                'completed_at' => $isBye ? now() : null,
            ]);

            // If it was a BYE, advance the winner to round 2 immediately!
            if ($isBye && $winnerId && $node->next_node_id) {
                $this->advanceWinnerToNode($node->next_node_id, $winnerId, $participantType, $node->node_order % 2 !== 0 ? 1 : 2);
            }
        }

        // 5. Create empty Match shells for rounds 2 through Finals
        for ($r = 2; $r <= $totalRounds; $r++) {
            foreach ($nodesByRound[$r] as $order => $node) {
                TournamentMatch::firstOrCreate([
                    'tournament_id' => $tournament->id,
                    'bracket_node_id' => $node->id,
                ], [
                    'match_format' => $tournament->match_format ?? 'bo1',
                    'round' => $r,
                    'match_number' => $order,
                    'participant1_type' => $participantType,
                    'participant2_type' => $participantType,
                    'status' => 'pending',
                ]);
            }
        }

        return $bracket;
    }

    /**
     * Generate Double Elimination Bracket.
     */
    public function generateDoubleElimination(Tournament $tournament, Collection $participants): Bracket
    {
        // 1. Generate Upper Bracket
        $bracket = $this->generateSingleElimination($tournament, $participants);
        $bracket->update(['type' => 'double_elimination']);

        $participantType = $participants->first()->team_id ? 'team' : 'user';

        // 2. Create Lower Bracket and Grand Finals
        $upperFinalNode = $bracket->nodes()->where('round', $bracket->nodes()->max('round'))->first();

        // Create Lower Bracket Final Node
        $lowerFinalNode = BracketNode::create([
            'bracket_id' => $bracket->id,
            'round' => 99, // Distinct lower marker
            'node_order' => 1,
        ]);

        // Create Grand Finals Node
        $grandFinalNode = BracketNode::create([
            'bracket_id' => $bracket->id,
            'round' => 100, // Grand final
            'node_order' => 1,
        ]);

        // Upper-bracket losers drop into a lower bracket before the lower final.
        $upperRounds = $bracket->nodes()->where('round', '<', $upperFinalNode->round)->get()->groupBy('round');
        foreach ($upperRounds as $round => $nodes) {
            $ordered = $nodes->sortBy('node_order')->values();
            for ($i = 0; $i < $ordered->count(); $i += 2) {
                $lowerNode = BracketNode::create([
                    'bracket_id' => $bracket->id,
                    'round' => 50 + (int) $round,
                    'node_order' => (int) ($i / 2) + 1,
                    'next_node_id' => $lowerFinalNode->id,
                ]);
                $ordered[$i]->update(['loser_next_node_id' => $lowerNode->id]);
                if (isset($ordered[$i + 1])) {
                    $ordered[$i + 1]->update(['loser_next_node_id' => $lowerNode->id]);
                }
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'bracket_node_id' => $lowerNode->id,
                    'match_format' => $tournament->match_format ?? 'bo3',
                    'round' => 50 + (int) $round,
                    'match_number' => (int) ($i / 2) + 1,
                    'participant1_type' => $participantType,
                    'participant2_type' => $participantType,
                    'status' => 'pending',
                ]);
            }
        }

        // Odd node order fills grand-final slot 1 (upper winner). Even fills slot 2 (lower winner).
        $lowerFinalNode->node_order = 2;
        $lowerFinalNode->save();

        // Link upper final to grand final
        $upperFinalNode->update(['next_node_id' => $grandFinalNode->id, 'loser_next_node_id' => $lowerFinalNode->id]);
        $lowerFinalNode->update(['next_node_id' => $grandFinalNode->id]);

        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'bracket_node_id' => $lowerFinalNode->id,
            'match_format' => $tournament->match_format ?? 'bo3',
            'round' => 99,
            'match_number' => 1,
            'participant1_type' => $participantType,
            'participant2_type' => $participantType,
            'status' => 'pending',
        ]);

        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'bracket_node_id' => $grandFinalNode->id,
            'match_format' => 'bo5',
            'round' => 100,
            'match_number' => 1,
            'participant1_type' => $participantType,
            'participant2_type' => $participantType,
            'status' => 'pending',
        ]);

        return $bracket;
    }

    /**
     * Generate Round Robin Tournament (All-play-all schedule).
     */
    public function generateRoundRobin(Tournament $tournament, Collection $participants): Bracket
    {
        $bracket = Bracket::create([
            'tournament_id' => $tournament->id,
            'name' => 'Round Robin Stage',
            'stage' => 'group_stage',
            'type' => 'round_robin',
            'status' => 'active',
        ]);

        $list = $participants->values()->all();
        $participantType = $participants->first()->team_id ? 'team' : 'user';

        // If odd, add a dummy BYE
        $hasBye = false;
        if (count($list) % 2 !== 0) {
            $list[] = null;
            $hasBye = true;
        }

        $numParticipants = count($list);
        $totalRounds = $numParticipants - 1;
        $matchesPerRound = $numParticipants / 2;

        $matchCounter = 1;

        // Round robin pairing rotation (circle method)
        for ($round = 1; $round <= $totalRounds; $round++) {
            for ($i = 0; $i < $matchesPerRound; $i++) {
                $p1 = $list[$i];
                $p2 = $list[$numParticipants - 1 - $i];

                if ($p1 === null || $p2 === null) {
                    continue; // Skip the bye match in round-robin schedule
                }

                $node = BracketNode::create([
                    'bracket_id' => $bracket->id,
                    'round' => $round,
                    'node_order' => $i + 1,
                ]);

                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'bracket_node_id' => $node->id,
                    'match_format' => $tournament->match_format ?? 'bo1',
                    'round' => $round,
                    'match_number' => $matchCounter++,
                    'participant1_id' => $participantType === 'team' ? $p1->team_id : $p1->user_id,
                    'participant2_id' => $participantType === 'team' ? $p2->team_id : $p2->user_id,
                    'participant1_type' => $participantType,
                    'participant2_type' => $participantType,
                    'status' => $round === 1 ? 'ready' : 'pending',
                    'scheduled_at' => $tournament->starts_at ? $tournament->starts_at->copy()->addHours($round - 1) : null,
                ]);
            }

            // Rotate array keeping first element fixed
            $first = array_shift($list);
            $last = array_pop($list);
            array_unshift($list, $last);
            array_unshift($list, $first);
        }

        return $bracket;
    }

    /**
     * Generate Swiss System Bracket (Round 1 generated, subsequent rounds generated dynamically based on records).
     */
    public function generateSwiss(Tournament $tournament, Collection $participants): Bracket
    {
        $bracket = Bracket::create([
            'tournament_id' => $tournament->id,
            'name' => 'Swiss System Stage',
            'stage' => 'group_stage',
            'type' => 'swiss',
            'status' => 'active',
            'settings' => [
                'total_rounds' => (int) ceil(log($participants->count(), 2)),
                'current_round' => 1,
            ],
        ]);

        $participantType = $participants->first()->team_id ? 'team' : 'user';
        $sorted = $participants->values();
        $half = (int) ceil($sorted->count() / 2);

        // Round 1 pairings: top half vs bottom half
        $matchNumber = 1;
        for ($i = 0; $i < $half; $i++) {
            $p1 = $sorted->get($i);
            $p2 = $sorted->get($i + $half);

            $node = BracketNode::create([
                'bracket_id' => $bracket->id,
                'round' => 1,
                'node_order' => $i + 1,
            ]);

            TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'bracket_node_id' => $node->id,
                'match_format' => $tournament->match_format ?? 'bo1',
                'round' => 1,
                'match_number' => $matchNumber++,
                'participant1_id' => $p1 ? ($participantType === 'team' ? $p1->team_id : $p1->user_id) : null,
                'participant2_id' => $p2 ? ($participantType === 'team' ? $p2->team_id : $p2->user_id) : null,
                'participant1_type' => $participantType,
                'participant2_type' => $participantType,
                'status' => $p1 && $p2 ? 'ready' : 'completed',
                'winner_id' => (! $p2 && $p1) ? ($participantType === 'team' ? $p1->team_id : $p1->user_id) : null,
                'is_bye' => ! $p2,
                'scheduled_at' => $tournament->starts_at,
            ]);
        }

        return $bracket;
    }

    /**
     * Generate Group Stage Plus Knockout Bracket.
     */
    public function generateGroupKnockout(Tournament $tournament, Collection $participants): Bracket
    {
        $bracket = Bracket::create([
            'tournament_id' => $tournament->id,
            'name' => 'Group Stage',
            'stage' => 'group_stage',
            'type' => 'group_knockout',
            'status' => 'active',
            'settings' => [
                'groups' => 2,
                'advance_per_group' => 2,
            ],
        ]);

        $participantType = $participants->first()->team_id ? 'team' : 'user';
        $groups = [[], []];

        // Snake seed participants across 2 groups
        foreach ($participants->values() as $idx => $p) {
            $groupIndex = ($idx % 2 === 0) ? 0 : 1;
            $groups[$groupIndex][] = $p;
        }

        // Generate round-robin matches within each group
        $matchNumber = 1;
        foreach ($groups as $gIdx => $groupList) {
            $groupNum = $gIdx + 1;
            $count = count($groupList);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $p1 = $groupList[$i];
                    $p2 = $groupList[$j];

                    $node = BracketNode::create([
                        'bracket_id' => $bracket->id,
                        'group_number' => $groupNum,
                        'round' => 1,
                        'node_order' => $matchNumber,
                    ]);

                    TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'bracket_node_id' => $node->id,
                        'match_format' => $tournament->match_format ?? 'bo1',
                        'round' => 1,
                        'match_number' => $matchNumber++,
                        'participant1_id' => $participantType === 'team' ? $p1->team_id : $p1->user_id,
                        'participant2_id' => $participantType === 'team' ? $p2->team_id : $p2->user_id,
                        'participant1_type' => $participantType,
                        'participant2_type' => $participantType,
                        'status' => 'ready',
                        'scheduled_at' => $tournament->starts_at,
                    ]);
                }
            }
        }

        // Create playoffs bracket shell (Semifinals & Final)
        $playoffBracket = Bracket::create([
            'tournament_id' => $tournament->id,
            'name' => 'Knockout Playoffs',
            'stage' => 'playoffs',
            'type' => 'single_elimination',
            'status' => 'pending',
        ]);

        $semi1 = BracketNode::create(['bracket_id' => $playoffBracket->id, 'round' => 1, 'node_order' => 1]);
        $semi2 = BracketNode::create(['bracket_id' => $playoffBracket->id, 'round' => 1, 'node_order' => 2]);
        $final = BracketNode::create(['bracket_id' => $playoffBracket->id, 'round' => 2, 'node_order' => 1]);

        $semi1->update(['next_node_id' => $final->id]);
        $semi2->update(['next_node_id' => $final->id]);

        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'bracket_node_id' => $semi1->id,
            'match_format' => 'bo3',
            'round' => 1,
            'match_number' => 1,
            'participant1_type' => $participantType,
            'participant2_type' => $participantType,
            'status' => 'pending',
        ]);

        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'bracket_node_id' => $semi2->id,
            'match_format' => 'bo3',
            'round' => 1,
            'match_number' => 2,
            'participant1_type' => $participantType,
            'participant2_type' => $participantType,
            'status' => 'pending',
        ]);

        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'bracket_node_id' => $final->id,
            'match_format' => 'bo5',
            'round' => 2,
            'match_number' => 1,
            'participant1_type' => $participantType,
            'participant2_type' => $participantType,
            'status' => 'pending',
        ]);

        return $bracket;
    }

    /**
     * Advance a winner directly to the specified target node slot.
     */
    protected function advanceWinnerToNode(int $nodeId, int $winnerId, string $participantType, int $slot = 1): void
    {
        $match = TournamentMatch::where('bracket_node_id', $nodeId)->first();
        if (! $match) {
            $node = BracketNode::find($nodeId);
            if ($node) {
                $match = TournamentMatch::create([
                    'tournament_id' => $node->bracket->tournament_id,
                    'bracket_node_id' => $node->id,
                    'match_format' => 'bo1',
                    'round' => $node->round,
                    'match_number' => $node->node_order,
                    'participant1_type' => $participantType,
                    'participant2_type' => $participantType,
                    'status' => 'pending',
                ]);
            }
        }

        if ($match) {
            if ($slot === 1 || ! $match->participant1_id) {
                $match->update(['participant1_id' => $winnerId, 'participant1_type' => $participantType]);
            } else {
                $match->update(['participant2_id' => $winnerId, 'participant2_type' => $participantType]);
            }

            // If both participants are now populated and neither is null, mark match ready!
            if ($match->participant1_id && $match->participant2_id) {
                $match->update(['status' => 'ready']);
            }
        }
    }

    /**
     * Compute standard tournament seed pairings (e.g. 1v8, 4v5, 2v7, 3v6).
     */
    public function getSeedingOrder(int $size): array
    {
        if ($size <= 2) {
            return [1, 2];
        }

        $rounds = (int) log($size, 2) - 1;
        $order = [1, 2];

        for ($i = 0; $i < $rounds; $i++) {
            $nextOrder = [];
            $sum = (count($order) * 2) + 1;
            foreach ($order as $seed) {
                $nextOrder[] = $seed;
                $nextOrder[] = $sum - $seed;
            }
            $order = $nextOrder;
        }

        return $order;
    }
}
