<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Teams\Models\Team;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('the landing page counts stored records and hides drafts', function () {
    Role::findOrCreate('organizer');

    $organizer = User::factory()->create();
    $organizer->assignRole('organizer');

    $player = User::factory()->create();
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'LandingAce',
        'university' => 'Addis Ababa University',
    ]);

    $game = Game::create([
        'name' => 'Campus FC',
        'slug' => 'campus-fc-landing',
        'category' => 'Sports',
        'publisher' => 'Campus',
        'is_active' => true,
    ]);

    Team::create([
        'name' => 'Bole Wolves',
        'tag' => 'BW',
        'slug' => 'bole-wolves',
        'leader_id' => $organizer->id,
        'game_id' => $game->id,
        'university' => 'Addis Ababa University',
    ]);

    $open = app(CreateTournamentAction::class)->execute($organizer, [
        'title' => 'Landing Open Cup',
        'game_id' => $game->id,
        'format' => 'single_elimination',
    ]);
    $open->update(['status' => 'registration_open']);

    app(CreateTournamentAction::class)->execute($organizer, [
        'title' => 'Landing Draft Cup',
        'game_id' => $game->id,
        'format' => 'single_elimination',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Landing')
            ->where('stats.totalPlayers', 1)
            ->where('stats.totalTeams', 1)
            ->where('stats.activeTournaments', 1)
            ->where('stats.partnerUniversities', 1)
            ->where('games.0.name', 'Campus FC')
            ->has('featuredTournaments', 1)
            ->where('featuredTournaments.0.title', 'Landing Open Cup')
            ->missing('featuredTournaments.1'));
});
