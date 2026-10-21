<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\Season;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRankingAward;
use App\Domain\Tournaments\Support\RankingLedgerBackfill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('organizer');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');

    $this->season = Season::create([
        'name' => '2026 Season',
        'slug' => '2026-season',
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'is_current' => true,
    ]);

    $this->game = Game::create([
        'name' => 'FC 25',
        'slug' => 'fc-25-backfill',
        'category' => 'Sports',
        'is_active' => true,
    ]);
});

test('legacy ranking_ledger is backfilled without duplicate award rows', function () {
    $player = User::factory()->create();
    $ranking = Ranking::create([
        'season_id' => $this->season->id,
        'game_id' => $this->game->id,
        'user_id' => $player->id,
        'points' => 100,
        'rating' => 1150,
        'rank' => 1,
    ]);

    $tournament = Tournament::create([
        'organizer_id' => $this->organizer->id,
        'game_id' => $this->game->id,
        'title' => 'Legacy Ledger Cup',
        'slug' => 'legacy-ledger-cup',
        'format' => 'single_elimination',
        'status' => 'completed',
        'capacity' => 8,
        'starts_at' => now()->subDay(),
        'settings' => [
            'ranking_ledger' => [
                (string) $ranking->id => 100,
            ],
        ],
    ]);

    $backfill = new RankingLedgerBackfill;
    expect($backfill->run())->toBe(1)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->count())->toBe(1)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->value('points'))->toBe(100);

    // Second run must not create duplicates or change points.
    expect($backfill->run())->toBe(0)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->count())->toBe(1);
});

test('malformed empty and orphan ranking_ledger entries are skipped', function () {
    $player = User::factory()->create();
    $ranking = Ranking::create([
        'season_id' => $this->season->id,
        'game_id' => $this->game->id,
        'user_id' => $player->id,
        'points' => 40,
        'rating' => 1060,
        'rank' => 1,
    ]);

    $malformed = Tournament::create([
        'organizer_id' => $this->organizer->id,
        'game_id' => $this->game->id,
        'title' => 'Malformed',
        'slug' => 'malformed-ledger',
        'format' => 'single_elimination',
        'status' => 'completed',
        'capacity' => 4,
        'starts_at' => now()->subDay(),
        'settings' => [],
    ]);
    // Force invalid JSON at the DB layer (Eloquent cast would reject it).
    DB::table('tournaments')->where('id', $malformed->id)->update([
        'settings' => '{not-valid-json',
    ]);

    Tournament::create([
        'organizer_id' => $this->organizer->id,
        'game_id' => $this->game->id,
        'title' => 'Empty Ledger',
        'slug' => 'empty-ledger',
        'format' => 'single_elimination',
        'status' => 'completed',
        'capacity' => 4,
        'starts_at' => now()->subDay(),
        'settings' => ['ranking_ledger' => []],
    ]);

    Tournament::create([
        'organizer_id' => $this->organizer->id,
        'game_id' => $this->game->id,
        'title' => 'Partial Ledger',
        'slug' => 'partial-ledger',
        'format' => 'single_elimination',
        'status' => 'completed',
        'capacity' => 4,
        'starts_at' => now()->subDay(),
        'settings' => [
            'ranking_ledger' => [
                (string) $ranking->id => 40,
                '999999' => 10,
                '0' => 5,
                'abc' => 7,
                (string) ($ranking->id + 1000) => 0,
            ],
        ],
    ]);

    $backfill = new RankingLedgerBackfill;
    expect($backfill->run())->toBe(1)
        ->and(TournamentRankingAward::count())->toBe(1)
        ->and(TournamentRankingAward::first()->ranking_id)->toBe($ranking->id)
        ->and(TournamentRankingAward::first()->points)->toBe(40);
});

test('existing award rows are preserved when the legacy ledger also contains them', function () {
    $player = User::factory()->create();
    $ranking = Ranking::create([
        'season_id' => $this->season->id,
        'game_id' => $this->game->id,
        'user_id' => $player->id,
        'points' => 75,
        'rating' => 1110,
        'rank' => 1,
    ]);

    $tournament = Tournament::create([
        'organizer_id' => $this->organizer->id,
        'game_id' => $this->game->id,
        'title' => 'Already Synced',
        'slug' => 'already-synced',
        'format' => 'single_elimination',
        'status' => 'completed',
        'capacity' => 4,
        'starts_at' => now()->subDay(),
        'settings' => [
            'ranking_ledger' => [
                (string) $ranking->id => 999,
            ],
        ],
    ]);

    TournamentRankingAward::create([
        'tournament_id' => $tournament->id,
        'ranking_id' => $ranking->id,
        'points' => 75,
    ]);

    expect((new RankingLedgerBackfill)->run())->toBe(0)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->value('points'))->toBe(75);
});
