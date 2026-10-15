<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('organizer');
    Role::findOrCreate('player');
    Role::findOrCreate('super-admin');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');

    $this->game = Game::create([
        'name' => 'PUBG Mobile',
        'slug' => 'pubg-mobile',
        'category' => 'Battle Royale',
        'is_active' => true,
    ]);
});

test('generates single elimination bracket with power-of-two padding and links', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'AAU Single Elim Championship',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'match_format' => 'bo1',
        'capacity' => 8,
        'starts_at' => now()->addDays(2),
    ]);

    // Register 4 players (Power of 2 = 4, 2 rounds)
    for ($i = 1; $i <= 4; $i++) {
        $player = User::factory()->create();
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
            'status' => 'approved',
            'seed' => $i,
        ]);
    }

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    expect($bracket)->not->toBeNull()
        ->and($bracket->type)->toBe('single_elimination')
        ->and($bracket->status)->toBe('active')
        ->and($tournament->fresh()->status)->toBe('live');

    // Total nodes for 4 participants: 2 in round 1, 1 in round 2 (Finals) = 3 nodes
    expect($bracket->nodes)->toHaveCount(3);

    // Verify round 1 nodes point to round 2 finals node
    $round1Nodes = $bracket->nodes()->where('round', 1)->get();
    $finalsNode = $bracket->nodes()->where('round', 2)->first();

    expect($round1Nodes)->toHaveCount(2)
        ->and($round1Nodes[0]->next_node_id)->toBe($finalsNode->id)
        ->and($round1Nodes[1]->next_node_id)->toBe($finalsNode->id);

    // Verify matches were created
    expect($tournament->matches)->toHaveCount(3);
});

test('handles byes automatically in single elimination bracket with odd participants', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Byes Test Tournament',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 8,
        'starts_at' => now()->addDays(2),
    ]);

    // 3 participants in a 4-slot bracket -> 1 BYE
    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $p3 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p3->id, 'status' => 'approved', 'seed' => 3]);

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    // The top seed should get a BYE match in Round 1
    $byeMatch = $tournament->matches()->where('is_bye', true)->first();

    expect($byeMatch)->not->toBeNull()
        ->and($byeMatch->status)->toBe('completed')
        ->and($byeMatch->winner_id)->toBe($p1->id);

    // And top seed should have automatically advanced to the Finals match!
    $finalsMatch = $tournament->matches()->where('round', 2)->first();
    expect($finalsMatch->participant1_id)->toBe($p1->id);
});

test('generates double elimination bracket with upper, lower, and grand finals', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Double Elimination Clash',
        'game_id' => $this->game->id,
        'format' => 'double_elimination',
        'capacity' => 4,
        'starts_at' => now()->addDays(2),
    ]);

    for ($i = 1; $i <= 4; $i++) {
        $player = User::factory()->create();
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
            'status' => 'approved',
            'seed' => $i,
        ]);
    }

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    expect($bracket->type)->toBe('double_elimination');

    // Must have Lower Final node (round 99) and Grand Final node (round 100)
    $lowerFinalNode = $bracket->nodes()->where('round', 99)->first();
    $grandFinalNode = $bracket->nodes()->where('round', 100)->first();

    expect($lowerFinalNode)->not->toBeNull()
        ->and($grandFinalNode)->not->toBeNull()
        ->and($lowerFinalNode->next_node_id)->toBe($grandFinalNode->id);
});

test('generates round robin all-play-all schedule', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Round Robin League',
        'game_id' => $this->game->id,
        'format' => 'round_robin',
        'capacity' => 4,
        'starts_at' => now()->addDays(2),
    ]);

    for ($i = 1; $i <= 4; $i++) {
        $player = User::factory()->create();
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
            'status' => 'approved',
            'seed' => $i,
        ]);
    }

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    expect($bracket->type)->toBe('round_robin');

    // 4 teams in round robin: (4 * 3) / 2 = 6 total matches
    expect($tournament->matches)->toHaveCount(6);

    // Each participant must appear exactly 3 times
    $firstPlayerId = TournamentRegistration::where('tournament_id', $tournament->id)->first()->user_id;
    $matchesWithP1 = $tournament->matches()
        ->where(fn ($q) => $q->where('participant1_id', $firstPlayerId)->orWhere('participant2_id', $firstPlayerId))
        ->count();

    expect($matchesWithP1)->toBe(3);
});

test('generates swiss system round 1 pairings', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Swiss System Open',
        'game_id' => $this->game->id,
        'format' => 'swiss',
        'capacity' => 6,
        'starts_at' => now()->addDays(2),
    ]);

    for ($i = 1; $i <= 6; $i++) {
        $player = User::factory()->create();
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
            'status' => 'approved',
            'seed' => $i,
        ]);
    }

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    expect($bracket->type)->toBe('swiss')
        ->and($bracket->settings['total_rounds'])->toBe(3);

    // 6 participants -> 3 matches in round 1
    expect($tournament->matches()->where('round', 1)->get())->toHaveCount(3);
});

test('generates group stage plus knockout bracket structure', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Groups Plus Knockout World Cup',
        'game_id' => $this->game->id,
        'format' => 'group_knockout',
        'capacity' => 8,
        'starts_at' => now()->addDays(2),
    ]);

    for ($i = 1; $i <= 8; $i++) {
        $player = User::factory()->create();
        TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
            'status' => 'approved',
            'seed' => $i,
        ]);
    }

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    expect($tournament->fresh()->brackets)->toHaveCount(2);

    $groupBracket = $tournament->brackets()->where('stage', 'group_stage')->first();
    $playoffBracket = $tournament->brackets()->where('stage', 'playoffs')->first();

    expect($groupBracket)->not->toBeNull()
        ->and($playoffBracket)->not->toBeNull();

    // Group stage has 2 groups of 4: (4*3)/2 * 2 = 12 group matches
    expect($tournament->matches()->whereHas('bracketNode', fn ($q) => $q->where('bracket_id', $groupBracket->id))->count())->toBe(12);

    // Playoff bracket has semifinals (2) + final (1) = 3 matches
    expect($tournament->matches()->whereHas('bracketNode', fn ($q) => $q->where('bracket_id', $playoffBracket->id))->count())->toBe(3);
});
