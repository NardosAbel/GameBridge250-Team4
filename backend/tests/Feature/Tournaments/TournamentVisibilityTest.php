<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Models\TournamentStaff;
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
        'name' => 'EA FC',
        'slug' => 'ea-fc-visibility',
        'category' => 'Sports',
        'is_active' => true,
    ]);

    $action = app(CreateTournamentAction::class);

    $this->draft = $action->execute($this->organizer, [
        'title' => 'Hidden Draft Cup',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
    ]);

    $this->open = $action->execute($this->organizer, [
        'title' => 'Bole Campus Qualifier',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
    ]);
    $this->open->update(['status' => 'registration_open']);

    $this->privateCup = $action->execute($this->organizer, [
        'title' => 'Invite Only Finals',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'visibility' => 'private',
    ]);
    $this->privateCup->update(['status' => 'registration_open']);
});

test('guests can discover published tournaments and not drafts or private events', function () {
    $response = $this->getJson('/api/v1/tournaments');

    $response->assertOk()
        ->assertJsonFragment(['title' => 'Bole Campus Qualifier'])
        ->assertJsonMissing(['title' => 'Hidden Draft Cup'])
        ->assertJsonMissing(['title' => 'Invite Only Finals']);
});

test('guests cannot open a draft or private tournament', function () {
    $this->getJson("/api/v1/tournaments/{$this->draft->id}")->assertForbidden();
    $this->getJson("/api/v1/tournaments/{$this->draft->id}/participants")->assertForbidden();
    $this->getJson("/api/v1/tournaments/{$this->privateCup->id}")->assertForbidden();
    $this->getJson("/api/v1/tournaments/{$this->open->id}")->assertOk()
        ->assertJsonPath('data.tournament.title', 'Bole Campus Qualifier');
});

test('the organizer can see and open their own draft', function () {
    $this->actingAs($this->organizer, 'sanctum')
        ->getJson('/api/v1/tournaments')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Hidden Draft Cup']);

    $this->actingAs($this->organizer, 'sanctum')
        ->getJson("/api/v1/tournaments/{$this->draft->id}")
        ->assertOk()
        ->assertJsonPath('data.tournament.status', 'draft');
});

test('assigned staff can open a draft and other players cannot', function () {
    $referee = User::factory()->create();
    $referee->assignRole('player');
    TournamentStaff::create([
        'tournament_id' => $this->draft->id,
        'user_id' => $referee->id,
        'role' => 'referee',
    ]);

    $stranger = User::factory()->create();
    $stranger->assignRole('player');

    $this->actingAs($referee, 'sanctum')
        ->getJson("/api/v1/tournaments/{$this->draft->id}")
        ->assertOk();

    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/tournaments/{$this->draft->id}")
        ->assertForbidden();
});
