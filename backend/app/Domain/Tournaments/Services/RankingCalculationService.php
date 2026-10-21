<?php

namespace App\Domain\Tournaments\Services;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\RankingSnapshot;
use App\Domain\Tournaments\Models\Season;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRankingAward;
use Illuminate\Support\Facades\DB;

class RankingCalculationService
{
    /**
     * Synchronize tournament placement points onto seasonal rankings.
     *
     * Re-running is safe: the award ledger stores the last applied points per
     * ranking, so lowering or clearing points_awarded reverses the previous delta.
     */
    public function awardTournamentPoints(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament): void {
            $tournament = Tournament::whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            $currentSeason = Season::where('is_current', true)->first();
            $isTeam = $tournament->entry_type === 'team' || $tournament->registrations()->whereNotNull('team_id')->exists();

            /** @var array<int, int> $desired ranking_id => target points for this tournament */
            $desired = [];

            foreach ($tournament->registrations()->get() as $reg) {
                $points = max(0, (int) $reg->points_awarded);
                $entityId = $isTeam ? $reg->team_id : $reg->user_id;

                if (! $entityId) {
                    continue;
                }

                $ranking = Ranking::query()
                    ->where('season_id', $currentSeason?->id)
                    ->where('game_id', $tournament->game_id)
                    ->where($isTeam ? 'team_id' : 'user_id', $entityId)
                    ->first();

                if (! $ranking && $points <= 0) {
                    continue;
                }

                if (! $ranking) {
                    $ranking = Ranking::create([
                        'season_id' => $currentSeason?->id,
                        'game_id' => $tournament->game_id,
                        $isTeam ? 'team_id' : 'user_id' => $entityId,
                        'points' => 0,
                        'rating' => 1000,
                    ]);
                }

                $desired[(int) $ranking->id] = ($desired[(int) $ranking->id] ?? 0) + $points;
            }

            $awards = TournamentRankingAward::query()
                ->where('tournament_id', $tournament->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('ranking_id');

            $corrections = [];

            foreach ($awards as $rankingId => $award) {
                $target = (int) ($desired[(int) $rankingId] ?? 0);
                $previous = (int) $award->points;
                $delta = $target - $previous;

                if ($delta !== 0) {
                    $this->applyDelta((int) $rankingId, $delta);
                    $corrections[] = [
                        'ranking_id' => (int) $rankingId,
                        'from' => $previous,
                        'to' => $target,
                    ];
                }

                if ($target <= 0) {
                    $award->delete();
                } else {
                    $award->update(['points' => $target]);
                }

                unset($desired[(int) $rankingId]);
            }

            foreach ($desired as $rankingId => $points) {
                if ($points <= 0) {
                    continue;
                }

                $this->applyDelta((int) $rankingId, $points);
                TournamentRankingAward::create([
                    'tournament_id' => $tournament->id,
                    'ranking_id' => $rankingId,
                    'points' => $points,
                ]);
                $corrections[] = [
                    'ranking_id' => (int) $rankingId,
                    'from' => 0,
                    'to' => $points,
                ];
            }

            $settings = $tournament->settings ?? [];
            $settings['rankings_settled_at'] = now()->toIso8601String();
            $tournament->update(['settings' => $settings]);

            if ($corrections !== []) {
                AuditLogger::log(
                    action: 'tournament.rankings_synced',
                    user: null,
                    entityType: 'tournament',
                    entityId: $tournament->id,
                    metadata: [
                        'corrections' => $corrections,
                    ]
                );
            }

            $this->recalculateRanks($tournament->game_id, $currentSeason?->id);
        });
    }

    private function applyDelta(int $rankingId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $ranking = Ranking::whereKey($rankingId)->lockForUpdate()->first();
        if (! $ranking) {
            return;
        }

        $ranking->points = max(0, (int) $ranking->points + $delta);
        $ranking->rating = max(0, (int) $ranking->rating + (int) round($delta * 1.5));
        $ranking->save();
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
