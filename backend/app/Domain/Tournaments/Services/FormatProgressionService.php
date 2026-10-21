<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Tournaments\Models\BracketNode;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;

/**
 * Creates the next Swiss round and seeds group-stage knockout matches
 * after the matches that feed them are complete.
 */
class FormatProgressionService
{
    public function progress(Tournament $tournament): void
    {
        if ($tournament->format === 'swiss') {
            $this->nextSwissRound($tournament);
        }

        if ($tournament->format === 'group_knockout') {
            $this->seedKnockout($tournament);
        }
    }

    private function nextSwissRound(Tournament $tournament): void
    {
        $bracket = $tournament->brackets()->where('type', 'swiss')->first();
        if (! $bracket) {
            return;
        }

        $settings = $bracket->settings ?? [];
        $current = (int) ($settings['current_round'] ?? 1);
        $total = (int) ($settings['total_rounds'] ?? 1);

        if ($current >= $total) {
            return;
        }

        $roundMatches = $tournament->matches()->where('round', $current)->get();
        if ($roundMatches->isEmpty() || $roundMatches->contains(fn (TournamentMatch $match) => $match->status !== 'completed')) {
            return;
        }

        $scores = $this->scores($tournament);
        $played = $this->playedPairs($tournament);
        $ordered = collect($scores)->sortByDesc('wins')->values();
        $ids = $ordered->pluck('id')->all();
        $pairs = $this->pairSwiss($ids, $played);
        $nextRound = $current + 1;
        $type = $roundMatches->first()->participant1_type ?? 'user';

        foreach ($pairs as $index => $pair) {
            $node = BracketNode::create([
                'bracket_id' => $bracket->id,
                'round' => $nextRound,
                'node_order' => $index + 1,
            ]);

            TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'bracket_node_id' => $node->id,
                'match_format' => $tournament->match_format ?? 'bo1',
                'round' => $nextRound,
                'match_number' => $index + 1,
                'participant1_id' => $pair[0],
                'participant2_id' => $pair[1],
                'participant1_type' => $type,
                'participant2_type' => $type,
                'status' => $pair[1] ? 'ready' : 'completed',
                'winner_id' => $pair[1] ? null : $pair[0],
                'is_bye' => $pair[1] === null,
                'scheduled_at' => $tournament->starts_at,
            ]);
        }

        $settings['current_round'] = $nextRound;
        $bracket->update(['settings' => $settings]);
    }

    private function seedKnockout(Tournament $tournament): void
    {
        $groupBracket = $tournament->brackets()->where('type', 'group_knockout')->first();
        $playoff = $tournament->brackets()->where('stage', 'playoffs')->first();
        if (! $groupBracket || ! $playoff) {
            return;
        }

        $groupMatches = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->whereHas('bracketNode', fn ($query) => $query->where('bracket_id', $groupBracket->id))
            ->get();

        if ($groupMatches->isEmpty() || $groupMatches->contains(fn (TournamentMatch $match) => $match->status !== 'completed')) {
            return;
        }

        $semis = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->whereHas('bracketNode', fn ($query) => $query->where('bracket_id', $playoff->id)->where('round', 1))
            ->orderBy('match_number')
            ->get();

        if ($semis->count() < 2 || $semis->every(fn (TournamentMatch $match) => $match->participant1_id && $match->participant2_id)) {
            return;
        }

        $groups = [];
        foreach ($groupMatches as $match) {
            $group = (int) ($match->bracketNode?->group_number ?? 0);
            foreach ([$match->participant1_id, $match->participant2_id] as $id) {
                if ($id) {
                    $groups[$group][$id] = $groups[$group][$id] ?? 0;
                }
            }
            if ($match->winner_id) {
                $groups[$group][$match->winner_id] = ($groups[$group][$match->winner_id] ?? 0) + 1;
            }
        }

        ksort($groups);
        $qualified = [];
        foreach ($groups as $scores) {
            arsort($scores);
            $qualified[] = array_keys($scores);
        }

        if (count($qualified) < 2 || count($qualified[0]) < 2 || count($qualified[1]) < 2) {
            return;
        }

        $semis[0]->update([
            'participant1_id' => $qualified[0][0],
            'participant2_id' => $qualified[1][1],
            'status' => 'ready',
        ]);
        $semis[1]->update([
            'participant1_id' => $qualified[1][0],
            'participant2_id' => $qualified[0][1],
            'status' => 'ready',
        ]);
        $playoff->update(['status' => 'active']);
    }

    /**
     * @return list<array{id: int, wins: int}>
     */
    private function scores(Tournament $tournament): array
    {
        $scores = [];
        foreach ($tournament->approvedRegistrations as $registration) {
            $id = $registration->team_id ?: $registration->user_id;
            if ($id) {
                $scores[$id] = ['id' => (int) $id, 'wins' => 0];
            }
        }

        foreach ($tournament->matches()->where('status', 'completed')->get() as $match) {
            if ($match->winner_id && isset($scores[$match->winner_id])) {
                $scores[$match->winner_id]['wins']++;
            }
        }

        return array_values($scores);
    }

    /**
     * @return array<string, true>
     */
    private function playedPairs(Tournament $tournament): array
    {
        $pairs = [];
        foreach ($tournament->matches as $match) {
            if ($match->participant1_id && $match->participant2_id) {
                $ids = [(int) $match->participant1_id, (int) $match->participant2_id];
                sort($ids);
                $pairs[$ids[0].'-'.$ids[1]] = true;
            }
        }

        return $pairs;
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, true>  $played
     * @return list<array{0: int, 1: ?int}>
     */
    private function pairSwiss(array $ids, array $played): array
    {
        $pool = $ids;
        $pairs = [];

        while ($pool !== []) {
            $first = array_shift($pool);
            if ($pool === []) {
                $pairs[] = [$first, null];
                break;
            }

            $partnerIndex = 0;
            foreach ($pool as $index => $candidate) {
                $key = [$first, $candidate];
                sort($key);
                if (! isset($played[$key[0].'-'.$key[1]])) {
                    $partnerIndex = $index;
                    break;
                }
            }

            $partner = $pool[$partnerIndex];
            array_splice($pool, $partnerIndex, 1);
            $pairs[] = [$first, $partner];
        }

        return $pairs;
    }
}
