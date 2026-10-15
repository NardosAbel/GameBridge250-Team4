<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Actions\MatchCheckInAction;
use App\Domain\Tournaments\Actions\OpenDisputeAction;
use App\Domain\Tournaments\Actions\ResolveDisputeAction;
use App\Domain\Tournaments\Actions\SubmitMatchResultAction;
use App\Domain\Tournaments\Models\Dispute;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Domain\Tournaments\Models\TournamentStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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
        'name' => 'EA Sports FC 25',
        'slug' => 'fc25',
        'category' => 'Sports',
        'is_active' => true,
    ]);

    $this->tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Addis Ababa FC Cup',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 2,
        'starts_at' => now()->addDays(1),
    ]);

    $this->p1 = User::factory()->create();
    $this->p2 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->p1->id, 'status' => 'approved', 'seed' => 1]);
    TournamentRegistration::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->p2->id, 'status' => 'approved', 'seed' => 2]);

    $this->bracket = app(GenerateBracketAction::class)->execute($this->organizer, $this->tournament);
    $this->match = $this->tournament->matches()->first();

    // Check-in both participants to make match active
    app(MatchCheckInAction::class)->execute($this->p1, $this->match);
    app(MatchCheckInAction::class)->execute($this->p2, $this->match);

    // P1 submits result claiming 2 - 0 win
    app(SubmitMatchResultAction::class)->execute($this->p1, $this->match, [
        'participant1_score' => 2,
        'participant2_score' => 0,
        'winner_id' => $this->p1->id,
    ]);
});

test('match participant can open dispute with evidence', function () {
    $dispute = app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'Opponent used an unauthorized squad composition.',
        'evidence_urls' => ['https://s3.campus-esports.et/evidence/match1_screenshot.png'],
    ]);

    expect($dispute)->toBeInstanceOf(Dispute::class)
        ->and($dispute->status)->toBe('open')
        ->and($dispute->reason)->toBe('Opponent used an unauthorized squad composition.')
        ->and($this->match->fresh()->status)->toBe('disputed')
        ->and($this->match->fresh()->latestResult->status)->toBe('disputed');
});

test('non-participant cannot open dispute on match', function () {
    $randomUser = User::factory()->create();

    expect(fn () => app(OpenDisputeAction::class)->execute($randomUser, $this->match, [
        'reason' => 'Fraud attempt',
    ]))->toThrow(AuthorizationException::class);
});

test('cannot open duplicate dispute on already disputed match', function () {
    app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'First dispute reason',
    ]);

    expect(fn () => app(OpenDisputeAction::class)->execute($this->p1, $this->match, [
        'reason' => 'Second dispute reason',
    ]))->toThrow(InvalidArgumentException::class);
});

test('organizer can resolve dispute by confirming result', function () {
    $dispute = app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'Opponent disconnected during 2nd half',
    ]);

    $resolved = app(ResolveDisputeAction::class)->execute($this->organizer, $dispute, [
        'action_taken' => 'confirm_result',
        'resolution_notes' => 'Review shows disconnect occurred after match was effectively over.',
    ]);

    expect($resolved->status)->toBe('resolved')
        ->and($resolved->action_taken)->toBe('confirm_result')
        ->and($this->match->fresh()->status)->toBe('completed')
        ->and($this->match->fresh()->winner_id)->toBe($this->p1->id);
});

test('appointed referee can resolve dispute by overturning result', function () {
    $referee = User::factory()->create();
    TournamentStaff::create([
        'tournament_id' => $this->tournament->id,
        'user_id' => $referee->id,
        'role' => 'referee',
    ]);

    $dispute = app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'False score submitted',
    ]);

    $resolved = app(ResolveDisputeAction::class)->execute($referee, $dispute, [
        'action_taken' => 'overturn_result',
        'participant1_score' => 1,
        'participant2_score' => 3,
        'winner_id' => $this->p2->id,
        'resolution_notes' => 'Referee validated official match logs. P2 won 3-1.',
    ]);

    expect($resolved->status)->toBe('resolved')
        ->and($this->match->fresh()->status)->toBe('completed')
        ->and($this->match->fresh()->winner_id)->toBe($this->p2->id)
        ->and($this->match->fresh()->participant1_score)->toBe(1)
        ->and($this->match->fresh()->participant2_score)->toBe(3);
});

test('organizer can resolve dispute with rematch', function () {
    $dispute = app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'Server glitch caused player desync',
    ]);

    $resolved = app(ResolveDisputeAction::class)->execute($this->organizer, $dispute, [
        'action_taken' => 'rematch',
        'resolution_notes' => 'Official rematch ordered due to verifiable server issues.',
    ]);

    expect($resolved->status)->toBe('resolved')
        ->and($this->match->fresh()->status)->toBe('ready')
        ->and($this->match->fresh()->participant1_score)->toBe(0)
        ->and($this->match->fresh()->participant2_score)->toBe(0)
        ->and($this->match->fresh()->winner_id)->toBeNull()
        ->and($this->match->fresh()->participant1_ready)->toBeFalse();
});

test('organizer can disqualify cheating participant in dispute', function () {
    $dispute = app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'P1 admitted to smurfing / ringers',
    ]);

    $resolved = app(ResolveDisputeAction::class)->execute($this->organizer, $dispute, [
        'action_taken' => 'disqualify_participant',
        'disqualified_id' => $this->p1->id,
        'resolution_notes' => 'P1 banned for ringer usage.',
    ]);

    expect($resolved->status)->toBe('resolved')
        ->and($this->match->fresh()->status)->toBe('completed')
        ->and($this->match->fresh()->winner_id)->toBe($this->p2->id);
});

test('unauthorized user cannot resolve dispute', function () {
    $stranger = User::factory()->create();

    $dispute = app(OpenDisputeAction::class)->execute($this->p2, $this->match, [
        'reason' => 'Dispute',
    ]);

    expect(fn () => app(ResolveDisputeAction::class)->execute($stranger, $dispute, [
        'action_taken' => 'confirm_result',
    ]))->toThrow(AuthorizationException::class);
});
