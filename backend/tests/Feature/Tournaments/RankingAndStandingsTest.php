<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\RankingSnapshot;
use App\Domain\Tournaments\Models\Season;
use App\Domain\Tournaments\Models\TournamentRankingAward;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Domain\Tournaments\Services\RankingCalculationService;
use App\Domain\Tournaments\Services\StandingsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('organizer');
    Role::findOrCreate('player');
    Role::findOrCreate('super_admin');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');

    $this->game = Game::create([
        'name' => 'PUBG Mobile',
        'slug' => 'pubg-mobile',
        'category' => 'Battle Royale',
        'is_active' => true,
    ]);

    $this->season = Season::create([
        'name' => 'Campus Championship Season 1',
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addMonths(2)->toDateString(),
        'is_current' => true,
    ]);
});

test('standings service correctly calculates points and rank for round robin bracket', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Round Robin League',
        'game_id' => $this->game->id,
        'format' => 'round_robin',
        'capacity' => 3,
        'starts_at' => now()->addDays(1),
    ]);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $p3 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p3->id, 'status' => 'approved', 'seed' => 3]);

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);
    $matches = $tournament->matches()->get();

    // Match 1: P1 vs P2 -> P1 wins 2 - 0
    $m1 = $matches->first(fn ($m) => ($m->participant1_id === $p1->id && $m->participant2_id === $p2->id) || ($m->participant1_id === $p2->id && $m->participant2_id === $p1->id));
    if ($m1) {
        $p1Score = ($m1->participant1_id === $p1->id) ? 2 : 0;
        $p2Score = ($m1->participant1_id === $p1->id) ? 0 : 2;
        $m1->update([
            'status' => 'completed',
            'participant1_score' => $p1Score,
            'participant2_score' => $p2Score,
            'winner_id' => $p1->id,
            'completed_at' => now(),
        ]);
    }

    // Match 2: P1 vs P3 -> Draw 1 - 1
    $m2 = $matches->first(fn ($m) => ($m->participant1_id === $p1->id && $m->participant2_id === $p3->id) || ($m->participant1_id === $p3->id && $m->participant2_id === $p1->id));
    if ($m2) {
        $m2->update([
            'status' => 'completed',
            'participant1_score' => 1,
            'participant2_score' => 1,
            'winner_id' => null,
            'completed_at' => now(),
        ]);
    }

    $standings = app(StandingsService::class)->getBracketStandings($bracket);

    expect($standings)->toBeArray()
        ->and(count($standings))->toBe(3);

    $p1Standing = collect($standings)->firstWhere('participant_id', $p1->id);
    expect($p1Standing['points'])->toBe(4) // 3 for win + 1 for draw
        ->and($p1Standing['wins'])->toBe(1)
        ->and($p1Standing['draws'])->toBe(1)
        ->and($p1Standing['rank'])->toBe(1);
});

test('ranking calculation service awards placement points and updates leaderboard', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Season Finale',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 2,
        'starts_at' => now()->addDays(1),
    ]);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();

    // 1st place gets 100 points, 2nd gets 50 points
    TournamentRegistration::create([
        'tournament_id' => $tournament->id,
        'user_id' => $p1->id,
        'status' => 'approved',
        'placement' => 1,
        'points_awarded' => 100,
    ]);

    TournamentRegistration::create([
        'tournament_id' => $tournament->id,
        'user_id' => $p2->id,
        'status' => 'approved',
        'placement' => 2,
        'points_awarded' => 50,
    ]);

    $service = app(RankingCalculationService::class);
    $service->awardTournamentPoints($tournament);

    $p1Ranking = Ranking::where('game_id', $this->game->id)->where('user_id', $p1->id)->first();
    $p2Ranking = Ranking::where('game_id', $this->game->id)->where('user_id', $p2->id)->first();

    expect($p1Ranking)->not->toBeNull()
        ->and($p1Ranking->points)->toBe(100)
        ->and($p1Ranking->rank)->toBe(1)
        ->and($p2Ranking)->not->toBeNull()
        ->and($p2Ranking->points)->toBe(50)
        ->and($p2Ranking->rank)->toBe(2);

    $service->awardTournamentPoints($tournament->fresh());

    expect($p1Ranking->fresh()->points)->toBe(100)
        ->and($p2Ranking->fresh()->points)->toBe(50)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->count())->toBe(2);
});

test('lowering or clearing points_awarded reverses prior ranking awards', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Correction Cup',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 2,
        'starts_at' => now()->addDays(1),
    ]);

    $p1 = User::factory()->create();
    $reg = TournamentRegistration::create([
        'tournament_id' => $tournament->id,
        'user_id' => $p1->id,
        'status' => 'approved',
        'placement' => 1,
        'points_awarded' => 100,
    ]);

    $service = app(RankingCalculationService::class);
    $service->awardTournamentPoints($tournament);

    $ranking = Ranking::where('game_id', $this->game->id)->where('user_id', $p1->id)->first();
    expect($ranking->points)->toBe(100)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->value('points'))->toBe(100);

    $reg->update(['points_awarded' => 40]);
    $service->awardTournamentPoints($tournament->fresh());

    expect($ranking->fresh()->points)->toBe(40)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->value('points'))->toBe(40);

    $reg->update(['points_awarded' => 0]);
    $service->awardTournamentPoints($tournament->fresh());

    expect($ranking->fresh()->points)->toBe(0)
        ->and(TournamentRankingAward::where('tournament_id', $tournament->id)->count())->toBe(0);
});

test('can take immutable ranking snapshot and query through api', function () {
    $p1 = User::factory()->create(['name' => 'Abebe Esports']);
    Ranking::create([
        'season_id' => $this->season->id,
        'game_id' => $this->game->id,
        'user_id' => $p1->id,
        'points' => 300,
        'rating' => 1450,
        'rank' => 1,
    ]);

    $service = app(RankingCalculationService::class);
    $snapshot = $service->captureSnapshot($this->game->id, $this->season->id, 'seasonal');

    expect($snapshot)->toBeInstanceOf(RankingSnapshot::class)
        ->and($snapshot->rankings_data)->toBeArray()
        ->and($snapshot->rankings_data[0]['participant_name'])->toBe('Abebe Esports')
        ->and($snapshot->rankings_data[0]['points'])->toBe(300);

    // API list rankings
    $response = $this->getJson("/api/v1/rankings?game_id={$this->game->id}");
    $response->assertStatus(200)
        ->assertJsonFragment(['points' => 300]);

    // API snapshots
    $snapResponse = $this->getJson("/api/v1/rankings/snapshots?game_id={$this->game->id}");
    $snapResponse->assertStatus(200)
        ->assertJsonFragment(['type' => 'seasonal']);
});
