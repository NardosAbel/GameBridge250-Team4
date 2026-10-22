<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Models\Ranking;
use App\Domain\Tournaments\Models\Season;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('organizer');
    Role::findOrCreate('player');
});

test('the dashboard lists persisted open tournaments and omits drafts and sample events', function () {
    $organizer = User::factory()->create();
    $organizer->assignRole('organizer');
    $player = User::factory()->create();
    $player->assignRole('player');

    $game = Game::create([
        'name' => 'FC 25',
        'slug' => 'fc-25-dashboard',
        'category' => 'Sports',
        'is_active' => true,
    ]);

    $open = app(CreateTournamentAction::class)->execute($organizer, [
        'title' => 'Bole Campus Qualifier',
        'game_id' => $game->id,
        'format' => 'single_elimination',
        'capacity' => 8,
        'starts_at' => now()->addDays(3),
    ]);
    $open->update(['status' => 'registration_open']);

    $draft = app(CreateTournamentAction::class)->execute($organizer, [
        'title' => 'Hidden Draft Cup',
        'game_id' => $game->id,
        'format' => 'single_elimination',
        'capacity' => 8,
        'starts_at' => now()->addDay(),
    ]);
    expect($draft->status)->toBe('draft');

    $this->actingAs($player)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('recentTournaments', 1)
            ->where('recentTournaments.0.title', 'Bole Campus Qualifier')
            ->where('recentTournaments.0.id', $open->id)
            ->missing('recentTournaments.1'));
});

test('the player profile shows verified placements without inventing a rating', function () {
    $player = User::factory()->unverified()->create([
        'phone' => '0911000000',
        'phone_verified_at' => null,
    ]);
    $player->assignRole('player');
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'CampusAcePassport',
        'is_public' => true,
    ]);

    $organizer = User::factory()->create();
    $organizer->assignRole('organizer');
    $game = Game::create([
        'name' => 'Valorant',
        'slug' => 'valorant-passport',
        'category' => 'FPS',
        'is_active' => true,
    ]);
    $tournament = Tournament::create([
        'organizer_id' => $organizer->id,
        'game_id' => $game->id,
        'title' => 'Passport Cup',
        'slug' => 'passport-cup',
        'format' => 'single_elimination',
        'status' => 'completed',
        'capacity' => 8,
        'starts_at' => now()->subDay(),
    ]);

    TournamentRegistration::create([
        'tournament_id' => $tournament->id,
        'user_id' => $player->id,
        'status' => 'approved',
        'placement' => 1,
        'points_awarded' => 100,
    ]);

    $season = Season::create([
        'name' => 'Passport Season',
        'slug' => 'passport-season',
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'is_current' => true,
    ]);

    Ranking::create([
        'season_id' => $season->id,
        'game_id' => $game->id,
        'user_id' => $player->id,
        'points' => 100,
        'rating' => 1200,
        'rank' => 1,
    ]);

    $this->actingAs($player)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.email_verified', false)
            ->where('user.phone_verified', false)
            ->where('passport.tournament_count', 1)
            ->where('passport.verified_result_count', 1)
            ->where('passport.rating', 1200)
            ->where('passport.results.0.placement', 1)
            ->where('passport.results.0.title', 'Passport Cup'));

    $this->get('/players/CampusAcePassport')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Profile/Passport')
            ->where('passport.verified_result_count', 1));
});

test('a private player passport is not public', function () {
    $player = User::factory()->create();
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'HiddenPassport',
        'is_public' => false,
        'privacy_settings' => ['visibility' => 'private'],
    ]);

    $this->get('/players/HiddenPassport')->assertNotFound();
});
