<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Tournaments\Actions\ConfirmMatchResultAction;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Actions\MatchCheckInAction;
use App\Domain\Tournaments\Actions\OverrideMatchResultAction;
use App\Domain\Tournaments\Actions\SubmitMatchResultAction;
use App\Domain\Tournaments\Models\TournamentRegistration;
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
        'name' => 'Valorant',
        'slug' => 'valorant',
        'category' => 'Tactical Shooter',
        'is_active' => true,
    ]);
});

test('match ready-up activates match when both participants confirm', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Ready Up Cup',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 2,
        'starts_at' => now()->addDays(1),
    ]);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);
    $match = $tournament->matches()->first();

    expect($match->status)->toBe('ready');

    // Participant 1 checks in
    app(MatchCheckInAction::class)->execute($p1, $match);
    expect($match->fresh()->participant1_ready)->toBeTrue()
        ->and($match->fresh()->status)->toBe('ready');

    // Participant 2 checks in -> Match becomes live!
    app(MatchCheckInAction::class)->execute($p2, $match);
    expect($match->fresh()->participant2_ready)->toBeTrue()
        ->and($match->fresh()->status)->toBe('live');
});

test('submitting result requires opponent confirmation to advance winner', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Result Flow Tournament',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 4,
        'starts_at' => now()->addDays(1),
    ]);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $p3 = User::factory()->create();
    $p4 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p3->id, 'status' => 'approved', 'seed' => 3]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p4->id, 'status' => 'approved', 'seed' => 4]);

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    // Get semi-final match 1 (Round 1, Match 1: P1 vs P4)
    $match1 = $tournament->matches()->where('round', 1)->first();
    $submitter = User::find($match1->participant1_id);
    $opponent = User::find($match1->participant2_id);

    // Submitter inputs 13 - 7
    $result = app(SubmitMatchResultAction::class)->execute($submitter, $match1, [
        'participant1_score' => 13,
        'participant2_score' => 7,
        'notes' => 'GGs!',
    ]);

    expect($result->status)->toBe('submitted')
        ->and($match1->fresh()->status)->not->toBe('completed');

    // Opponent cannot confirm if unauthorized, but when opponent confirms:
    app(ConfirmMatchResultAction::class)->execute($opponent, $match1, $result);

    $match1->refresh();
    expect($match1->status)->toBe('completed')
        ->and($match1->winner_id)->toBe($submitter->id);

    // Winner must have advanced to the finals node!
    $finalsMatch = $tournament->matches()->where('round', 2)->first();
    expect($finalsMatch->participant1_id)->toBe($submitter->id);
});

test('organizer override recalculates downstream bracket when overturning winner', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Override Cascading Cup',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 4,
        'starts_at' => now()->addDays(1),
    ]);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $p3 = User::factory()->create();
    $p4 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p3->id, 'status' => 'approved', 'seed' => 3]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p4->id, 'status' => 'approved', 'seed' => 4]);

    app(GenerateBracketAction::class)->execute($this->organizer, $tournament);

    $match1 = $tournament->matches()->where('round', 1)->first();
    $p1Id = $match1->participant1_id;
    $p2Id = $match1->participant2_id;

    // Initially, participant 1 wins
    app(OverrideMatchResultAction::class)->execute($this->organizer, $match1, [
        'participant1_score' => 2,
        'participant2_score' => 0,
        'winner_id' => $p1Id,
        'reason' => 'First match complete',
    ]);

    $finalsMatch = $tournament->matches()->where('round', 2)->first();
    expect($finalsMatch->participant1_id)->toBe($p1Id);

    // Organizer overturns: participant 2 is now the winner!
    app(OverrideMatchResultAction::class)->execute($this->organizer, $match1, [
        'participant1_score' => 0,
        'participant2_score' => 2,
        'winner_id' => $p2Id,
        'reason' => 'Rule violation by participant 1 detected',
    ]);

    // Downstream finals match must have revoked participant 1 and replaced with participant 2!
    $finalsMatch->refresh();
    expect($finalsMatch->participant1_id)->toBe($p2Id);
});

test('tournament completes and awards placements when final match concludes', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Championship Final',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 2,
        'starts_at' => now()->addDays(1),
        'points_distribution' => [1 => 100, 2 => 60],
    ]);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);

    app(GenerateBracketAction::class)->execute($this->organizer, $tournament);
    $finalMatch = $tournament->matches()->first();

    // Organizer finalizes match with P1 as champion
    app(OverrideMatchResultAction::class)->execute($this->organizer, $finalMatch, [
        'participant1_score' => 3,
        'participant2_score' => 1,
        'winner_id' => $p1->id,
        'reason' => 'Grand finals completed',
    ]);

    expect($tournament->fresh()->status)->toBe('completed');

    $reg1 = TournamentRegistration::where('tournament_id', $tournament->id)->where('user_id', $p1->id)->first();
    $reg2 = TournamentRegistration::where('tournament_id', $tournament->id)->where('user_id', $p2->id)->first();

    expect($reg1->placement)->toBe(1)
        ->and($reg1->points_awarded)->toBe(100)
        ->and($reg2->placement)->toBe(2)
        ->and($reg2->points_awarded)->toBe(60);
});
