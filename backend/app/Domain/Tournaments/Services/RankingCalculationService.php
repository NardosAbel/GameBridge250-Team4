<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\RankingSnapshot;
use App\Domain\Tournaments\Models\Season;
use App\Domain\Tournaments\Models\Tournament;
use Illuminate\Support\Facades\DB;

class RankingCalculationService
{
    /**
     * Award tournament placement points to participants in the current season.
     */
    public function awardTournamentPoints(Tournament $tournament): void
    {
        $currentSeason = Season::where('is_current', true)->first();
        $isTeam = $tournament->entry_type === 'team' || $tournament->registrations()->whereNotNull('team_id')->exists();

        $registrations = $tournament->registrations()->where('points_awarded', '>', 0)->get();

        foreach ($registrations as $reg) {
            $points = $reg->points_awarded;
            $entityId = $isTeam ? $reg->team_id : $reg->user_id;

            if (! $entityId) {
                continue;
            }

            $ranking = Ranking::firstOrCreate([
                'season_id' => $currentSeason?->id,
                'game_id' => $tournament->game_id,
                $isTeam ? 'team_id' : 'user_id' => $entityId,
            ], [
                'points' => 0,
                'rating' => 1000,
            ]);

            $ranking->increment('points', $points);
            $ranking->increment('rating', (int) ($points * 1.5));
        }

        // Recalculate ranks for this game and season
        $this->recalculateRanks($tournament->game_id, $currentSeason?->id);
    }

    /**
     * Recalculate sorted numerical ranks for a specific game & season.
     */
    public function recalculateRanks(int $gameId, ?int $seasonId = null): void
    {
        DB::transaction(function () use ($gameId, $seasonId) {
            $query = Ranking::where('game_id', $gameId);
            if ($seasonId) {
                $query->where('season_id', $seasonId);
            }

            $rankings = $query->orderBy('points', 'desc')->orderBy('rating', 'desc')->get();

            $rank = 1;
            foreach ($rankings as $ranking) {
                $ranking->update(['rank' => $rank++]);
            }
        });
    }

    /**
     * Take an immutable snapshot of current leaderboards.
     */
    public function captureSnapshot(int $gameId, ?int $seasonId = null, string $type = 'global'): RankingSnapshot
    {
        $query = Ranking::where('game_id', $gameId)->with(['user', 'team'])->orderBy('rank', 'asc');
        if ($seasonId) {
            $query->where('season_id', $seasonId);
        }

        $top = $query->limit(100)->get()->map(fn ($r) => [
            'rank' => $r->rank,
            'points' => $r->points,
            'rating' => $r->rating,
            'participant_name' => $r->team ? $r->team->name : ($r->user ? $r->user->name : 'Unknown'),
            'participant_type' => $r->team_id ? 'team' : 'user',
            'participant_id' => $r->team_id ?? $r->user_id,
        ])->all();

        return RankingSnapshot::create([
            'season_id' => $seasonId,
            'game_id' => $gameId,
            'snapshot_date' => now()->toDateString(),
            'type' => $type,
            'rankings_data' => $top,
        ]);
    }
}
