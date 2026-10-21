<?php

namespace App\Domain\Tournaments\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies legacy tournament settings.ranking_ledger rows into tournament_ranking_awards.
 *
 * Safe to run more than once: uses insertOrIgnore on the unique (tournament_id, ranking_id)
 * pair and skips malformed, empty, or orphan ranking ids.
 */
final class RankingLedgerBackfill
{
    public function run(): int
    {
        if (! Schema::hasTable('tournament_ranking_awards') || ! Schema::hasTable('tournaments')) {
            return 0;
        }

        $inserted = 0;
        $now = now();

        DB::table('tournaments')
            ->select(['id', 'settings'])
            ->orderBy('id')
            ->chunkById(100, function ($tournaments) use ($now, &$inserted): void {
                foreach ($tournaments as $tournament) {
                    $inserted += $this->backfillTournament((int) $tournament->id, $tournament->settings, $now);
                }
            });

        return $inserted;
    }

    /**
     * @param  mixed  $rawSettings  JSON string or already-decoded array from the database.
     */
    public function backfillTournament(int $tournamentId, mixed $rawSettings, mixed $now = null): int
    {
        $settings = $this->decodeSettings($rawSettings);
        if ($settings === null) {
            return 0;
        }

        $ledger = $settings['ranking_ledger'] ?? null;
        if (! is_array($ledger) || $ledger === []) {
            return 0;
        }

        $now = $now ?? now();
        $inserted = 0;

        foreach ($ledger as $rankingId => $points) {
            $rankingId = (int) $rankingId;
            $points = (int) $points;

            if ($rankingId <= 0 || $points <= 0) {
                continue;
            }

            if (! DB::table('rankings')->where('id', $rankingId)->exists()) {
                continue;
            }

            $wasInserted = DB::table('tournament_ranking_awards')->insertOrIgnore([
                'tournament_id' => $tournamentId,
                'ranking_id' => $rankingId,
                'points' => $points,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $inserted += (int) $wasInserted;
        }

        return $inserted;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeSettings(mixed $rawSettings): ?array
    {
        if (is_array($rawSettings)) {
            return $rawSettings;
        }

        if (! is_string($rawSettings) || $rawSettings === '') {
            return null;
        }

        $decoded = json_decode($rawSettings, true);

        return is_array($decoded) ? $decoded : null;
    }
}
