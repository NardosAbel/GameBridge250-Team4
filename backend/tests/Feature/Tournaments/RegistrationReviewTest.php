<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Actions\RegisterTournamentAction;
use App\Domain\Tournaments\Actions\ReviewTournamentRegistrationAction;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('organizer');
    Role::findOrCreate('player');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');
    $this->game = Game::create([
        'name' => 'Review FC',
        'slug' => 'review-fc',
        'category' => 'sports',
        'publisher' => 'Test',
        'is_active' => true,
    ]);
});

function reviewTournament(array $extra = []): Tournament
{
    $tournament = app(CreateTournamentAction::class)->execute(test()->organizer, array_merge([
        'title' => 'Review Open',
        'game_id' => test()->game->id,
        'format' => 'single_elimination',
        'entry_type' => 'free',
        'capacity' => 2,
    ], $extra));
    $tournament->update(['status' => 'registration_open']);

    return $tournament->fresh();
}

function reviewPlayer(string $tag, array $profile = []): User
{
    $player = User::factory()->create();
    $player->assignRole('player');
    Profile::create(array_merge([
        'user_id' => $player->id,
        'gamer_tag' => $tag,
        'is_public' => true,
    ], $profile));

    return $player;
}

test('an application stays pending until an organizer approves it', function () {
    $tournament = reviewTournament();
    $player = reviewPlayer('PendingAce');
    $stranger = reviewPlayer('StrangerAce');

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertStatus(422);

    expect(TournamentRegistration::where('tournament_id', $tournament->id)->count())->toBe(1);

    $registration = TournamentRegistration::first();

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$registration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertForbidden();

    expect($registration->fresh()->status)->toBe('pending');

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/bracket")
        ->assertStatus(422);

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$registration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    $this->actingAs($player, 'sanctum')
        ->getJson("/api/v1/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertJsonPath('data.tournament.viewer_registration.status', 'approved')
        ->assertJsonPath('data.tournament.participants_count', 1);
});

test('an organizer can reject an application and a rejected entry stays out of the bracket', function () {
    $tournament = reviewTournament();
    $player = reviewPlayer('RejectedAce');

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertCreated();

    $registration = TournamentRegistration::first();

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$registration->id}/review", [
            'decision' => 'rejected',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect(fn () => app(GenerateBracketAction::class)->execute($this->organizer, $tournament->fresh()))
        ->toThrow(InvalidArgumentException::class);
});

test('approval rechecks eligibility and capacity', function () {
    $tournament = reviewTournament([
        'capacity' => 1,
        'eligibility_rules' => ['allowed_universities' => ['Hawassa University']],
    ]);
    $player = reviewPlayer('CampusAce', ['university' => 'Hawassa University']);
    $other = reviewPlayer('OtherAce', ['university' => 'Hawassa University']);

    $this->actingAs($player, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/register")
        ->assertCreated();

    $registration = TournamentRegistration::where('user_id', $player->id)->first();
    $player->profile->update(['university' => 'Addis Ababa University']);

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$registration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertStatus(422);

    expect($registration->fresh()->status)->toBe('pending');

    $player->profile->update(['university' => 'Hawassa University']);
    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/tournaments/{$tournament->id}/registrations/{$registration->id}/review", [
            'decision' => 'approved',
        ])
        ->assertOk();

    TournamentRegistration::create([
        'tournament_id' => $tournament->id,
        'user_id' => $other->id,
        'status' => 'pending',
    ]);
    $second = TournamentRegistration::where('user_id', $other->id)->first();

    expect(fn () => app(ReviewTournamentRegistrationAction::class)->execute($this->organizer, $second, 'approved'))
        ->toThrow(InvalidArgumentException::class, 'This competition is already at capacity.');

    expect($second->fresh()->status)->toBe('pending');
    expect(TournamentRegistration::where('status', 'approved')->count())->toBe(1);
});

test('a team application requires the captain and stays pending', function () {
    $tournament = reviewTournament();
    $captain = reviewPlayer('CaptainAce');
    $player = reviewPlayer('RosterAce');
    $team = Team::create([
        'name' => 'Review Rifles',
        'slug' => 'review-rifles',
        'tag' => 'RVR',
        'leader_id' => $captain->id,
    ]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $player->id, 'role' => 'player', 'joined_at' => now()]);

    expect(fn () => app(RegisterTournamentAction::class)->execute($player, $tournament, ['team_id' => $team->id]))
        ->toThrow(AuthorizationException::class);

    $registration = app(RegisterTournamentAction::class)->execute($captain, $tournament, ['team_id' => $team->id]);

    expect($registration->status)->toBe('pending')
        ->and($registration->team_id)->toBe($team->id);
});

test('explicit auto approval still accepts the entrant immediately', function () {
    $tournament = reviewTournament();
    $tournament->update(['settings' => ['auto_approve' => true]]);
    $player = reviewPlayer('AutoAce');

    $registration = app(RegisterTournamentAction::class)->execute($player, $tournament->fresh());

    expect($registration->status)->toBe('approved');
});

test('an existing approved entrant is left approved', function () {
    $tournament = reviewTournament();
    $player = reviewPlayer('KeptAce');
    $existing = TournamentRegistration::create([
        'tournament_id' => $tournament->id,
        'user_id' => $player->id,
        'status' => 'approved',
    ]);

    expect($existing->fresh()->status)->toBe('approved');
});
