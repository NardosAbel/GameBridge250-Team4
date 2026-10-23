<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('organizer');
    Role::findOrCreate('player');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');

    $this->game = Game::create([
        'name' => 'Journey FC',
        'slug' => 'journey-fc',
        'category' => 'sports',
        'publisher' => 'Test',
        'is_active' => true,
    ]);
});

test('a player registers once, an official confirms the match, and the passport records the place', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Journey Open',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'match_format' => 'bo1',
        'entry_type' => 'free',
        'capacity' => 2,
        'rules' => 'Best of one. The organizer confirms the result.',
        'prize_pool' => 0,
    ]);
    $tournament->update(['status' => 'registration_open']);

    $winner = User::factory()->create(['name' => 'Winner Player']);
    $winner->assignRole('player');
    Profile::create([
        'user_id' => $winner->id,
        'gamer_tag' => 'JourneyWinner',
        'is_public' => true,
        'university' => 'Addis Ababa University',
    ]);

    $opponent = User::factory()->create(['name' => 'Opponent Player']);
    $opponent->assignRole('player');
    Profile::create([
        'user_id' => $opponent->id,
        'gamer_tag' => 'JourneyOpponent',
        'is_public' => true,
    ]);

    $outsider = User::factory()->create();
    $outsider->assignRole('player');

    $this->actingAs($outsider)
        ->get("/tournaments/{$tournament->id}/manage")
        ->assertForbidden();

    $this->actingAs($this->organizer)
        ->get("/tournaments/{$tournament->id}/manage")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Tournaments/Manage'));

    $this->actingAs($outsider)
        ->get("/tournaments/{$tournament->id}/prize-pool")
        ->assertForbidden();

    $this->actingAs($winner, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertCreated();

    $this->actingAs($winner, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertStatus(422);

    expect(TournamentRegistration::where('tournament_id', $tournament->id)->where('user_id', $winner->id)->count())->toBe(1);
    expect(TournamentRegistration::where('user_id', $winner->id)->value('status'))->toBe('pending');

    $this->actingAs($opponent, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertCreated();

    $this->actingAs($winner, 'sanctum')
        ->getJson("/api/v1/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertJsonPath('data.tournament.viewer_registration.status', 'pending');

    $winnerRegistration = TournamentRegistration::where('user_id', $winner->id)->first();
    $opponentRegistration = TournamentRegistration::where('user_id', $opponent->id)->first();

    $this->actingAs($outsider, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$winnerRegistration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertForbidden();

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$winnerRegistration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertOk();

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$opponentRegistration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertOk();

    $this->actingAs($winner, 'sanctum')
        ->getJson("/api/v1/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertJsonPath('data.tournament.viewer_registration.status', 'approved')
        ->assertJsonPath('data.tournament.entry_type', 'free')
        ->assertJsonPath('data.tournament.rules', 'Best of one. The organizer confirms the result.');

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/bracket")
        ->assertOk();

    $match = $tournament->matches()->first();
    expect($match)->not->toBeNull();

    $this->actingAs($winner, 'sanctum')
        ->postJson("/api/v1/matches/{$match->id}/result", [
            'participant1_score' => 2,
            'participant2_score' => 0,
        ])
        ->assertOk();

    $this->actingAs($outsider, 'sanctum')
        ->postJson("/api/v1/matches/{$match->id}/confirm")
        ->assertForbidden();

    $this->actingAs($outsider, 'sanctum')
        ->postJson("/api/v1/matches/{$match->id}/override", [
            'participant1_score' => 0,
            'participant2_score' => 2,
        ])
        ->assertForbidden();

    expect($match->fresh()->status)->not->toBe('completed');

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/matches/{$match->id}/confirm")
        ->assertOk();

    expect($match->fresh()->status)->toBe('completed')
        ->and($match->fresh()->winner_id)->toBe($winner->id)
        ->and($tournament->fresh()->status)->toBe('completed');

    $winnerRegistration = TournamentRegistration::where('tournament_id', $tournament->id)
        ->where('user_id', $winner->id)
        ->first();
    expect($winnerRegistration->placement)->toBe(1);

    $this->get('/players/JourneyWinner')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Profile/Passport')
            ->where('passport.results.0.placement', 1)
            ->where('passport.verified_result_count', 1));

    expect(Ranking::where('user_id', $winner->id)->where('game_id', $this->game->id)->exists())->toBeTrue();

    $private = User::factory()->create();
    Profile::create([
        'user_id' => $private->id,
        'gamer_tag' => 'JourneyHidden',
        'is_public' => false,
        'privacy_settings' => ['visibility' => 'private'],
    ]);

    $this->get('/players/JourneyHidden')->assertNotFound();
});

test('an ineligible player sees the restriction and cannot register', function () {
    $tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Hawassa Only',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'match_format' => 'bo1',
        'entry_type' => 'free',
        'capacity' => 8,
        'eligibility_rules' => [
            'allowed_universities' => ['Hawassa University'],
        ],
    ]);
    $tournament->update(['status' => 'registration_open']);

    $player = User::factory()->create();
    $player->assignRole('player');
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'WrongCampus',
        'is_public' => true,
        'university' => 'Addis Ababa University',
    ]);

    $this->actingAs($player, 'sanctum')
        ->getJson("/api/v1/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertJsonPath('data.tournament.viewer_eligibility', 'This tournament is restricted to students from: Hawassa University');

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertStatus(422);

    expect(TournamentRegistration::where('tournament_id', $tournament->id)->count())->toBe(0);
});
