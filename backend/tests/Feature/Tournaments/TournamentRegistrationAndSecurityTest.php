<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Domain\Tournaments\Actions\GenerateBracketAction;
use App\Domain\Tournaments\Actions\RegisterTournamentAction;
use App\Domain\Tournaments\Actions\UpdateMatchRoomAction;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentFraudSignal;
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
    Role::findOrCreate('super_admin');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');

    $this->game = Game::create([
        'name' => 'Mobile Legends: Bang Bang',
        'slug' => 'mlbb',
        'category' => 'MOBA',
        'is_active' => true,
    ]);

    $this->tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Ethiopian Varsity Invitational',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'capacity' => 2,
        'starts_at' => now()->addDays(2),
        'eligibility_rules' => [
            'allowed_universities' => ['Addis Ababa University', 'HilCoE'],
            'allowed_regions' => ['Addis Ababa'],
            'require_student_id' => true,
            'allowed_platforms' => ['mobile'],
        ],
    ]);

    // Open registrations
    $this->tournament->update(['status' => 'registration_open']);
});

test('registration fails when tournament status is not registration_open', function () {
    $this->tournament->update(['status' => 'registration_closed']);
    $player = User::factory()->create();

    expect(fn () => app(RegisterTournamentAction::class)->execute($player, $this->tournament))
        ->toThrow(InvalidArgumentException::class, 'Tournament registration is currently closed.');
});

test('registration enforces capacity limits', function () {
    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $p3 = User::factory()->create();

    TournamentRegistration::create(['tournament_id' => $this->tournament->id, 'user_id' => $p1->id, 'status' => 'approved']);
    TournamentRegistration::create(['tournament_id' => $this->tournament->id, 'user_id' => $p2->id, 'status' => 'approved']);

    expect(fn () => app(RegisterTournamentAction::class)->execute($p3, $this->tournament))
        ->toThrow(InvalidArgumentException::class, 'Tournament capacity has already been reached.');
});

test('registration enforces university eligibility rule', function () {
    $player = User::factory()->create();
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'Player_Uni',
        'university' => 'Gondar University', // Not in allowed list
        'location' => 'Addis Ababa',
        'student_id_number' => 'UGR/1234/14',
        'platforms' => ['mobile'],
    ]);

    expect(fn () => app(RegisterTournamentAction::class)->execute($player, $this->tournament))
        ->toThrow(InvalidArgumentException::class, 'This tournament is restricted to students from: Addis Ababa University, HilCoE');
});

test('registration enforces verified student id requirement', function () {
    $player = User::factory()->create();
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'Player_NoID',
        'university' => 'Addis Ababa University',
        'location' => 'Addis Ababa',
        'student_id_number' => null, // Missing student ID!
        'platforms' => ['mobile'],
    ]);

    expect(fn () => app(RegisterTournamentAction::class)->execute($player, $this->tournament))
        ->toThrow(InvalidArgumentException::class, 'A verified student ID is required to register for this collegiate tournament.');
});

test('registration enforces platform eligibility rule', function () {
    $player = User::factory()->create();
    Profile::create([
        'user_id' => $player->id,
        'gamer_tag' => 'Player_PC',
        'university' => 'Addis Ababa University',
        'location' => 'Addis Ababa',
        'student_id_number' => 'UGR/9999/14',
        'platforms' => ['pc'], // Not mobile
    ]);

    expect(fn () => app(RegisterTournamentAction::class)->execute($player, $this->tournament))
        ->toThrow(InvalidArgumentException::class, 'Supported platforms for this tournament are: mobile');
});

test('invitation tournament requires valid invite code', function () {
    $inviteTourney = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'VIP Masters',
        'game_id' => $this->game->id,
        'entry_type' => 'invitation',
        'capacity' => 4,
        'settings' => ['invitation_codes' => ['VIP2026', 'CHAMPION']],
    ]);
    $inviteTourney->update(['status' => 'registration_open']);

    $player = User::factory()->create();

    // Invalid code
    expect(fn () => app(RegisterTournamentAction::class)->execute($player, $inviteTourney, ['invitation_code' => 'WRONG']))
        ->toThrow(AuthorizationException::class, 'A valid invitation code is required for this tournament.');

    // Valid code succeeds
    $reg = app(RegisterTournamentAction::class)->execute($player, $inviteTourney, ['invitation_code' => 'VIP2026']);
    expect($reg)->toBeInstanceOf(TournamentRegistration::class);
});

test('anti-fraud service flags duplicate student id across accounts', function () {
    $p1 = User::factory()->create();
    Profile::create([
        'user_id' => $p1->id,
        'gamer_tag' => 'P1_Gamer',
        'university' => 'Addis Ababa University',
        'location' => 'Addis Ababa',
        'student_id_number' => 'UGR/0001/14',
        'platforms' => ['mobile'],
    ]);

    $reg1 = app(RegisterTournamentAction::class)->execute($p1, $this->tournament);
    expect($reg1->anti_fraud_flags)->toBeNull();

    // P2 tries to use the exact same student ID number
    $p2 = User::factory()->create();
    Profile::create([
        'user_id' => $p2->id,
        'gamer_tag' => 'P2_Gamer',
        'university' => 'Addis Ababa University',
        'location' => 'Addis Ababa',
        'student_id_number' => 'UGR/0001/14',
        'platforms' => ['mobile'],
    ]);

    $reg2 = app(RegisterTournamentAction::class)->execute($p2, $this->tournament);

    expect($reg2->fresh()->anti_fraud_flags)->not->toBeNull()
        ->and(TournamentFraudSignal::where('registration_id', $reg2->id)->where('signal_type', 'duplicate_student_id')->exists())->toBeTrue();
});

test('anti-fraud service flags multi-team roster tampering', function () {
    $openTourney = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Team Showdown',
        'game_id' => $this->game->id,
        'entry_type' => 'free',
        'capacity' => 4,
    ]);
    $openTourney->update(['status' => 'registration_open']);

    $starPlayer = User::factory()->create();
    $captain1 = User::factory()->create();
    $captain2 = User::factory()->create();

    $teamA = Team::create(['name' => 'Lions Esports', 'slug' => 'lions', 'tag' => 'LION', 'leader_id' => $captain1->id]);
    TeamMember::create(['team_id' => $teamA->id, 'user_id' => $captain1->id, 'role' => 'captain', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $teamA->id, 'user_id' => $starPlayer->id, 'role' => 'player', 'joined_at' => now()]);

    $teamB = Team::create(['name' => 'Wolves Esports', 'slug' => 'wolves', 'tag' => 'WLV', 'leader_id' => $captain2->id]);
    TeamMember::create(['team_id' => $teamB->id, 'user_id' => $captain2->id, 'role' => 'captain', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $teamB->id, 'user_id' => $starPlayer->id, 'role' => 'player', 'joined_at' => now()]); // Same player!

    $regA = app(RegisterTournamentAction::class)->execute($captain1, $openTourney, ['team_id' => $teamA->id]);
    $regB = app(RegisterTournamentAction::class)->execute($captain2, $openTourney, ['team_id' => $teamB->id]);

    expect($regB->fresh()->anti_fraud_flags)->not->toBeNull()
        ->and(TournamentFraudSignal::where('registration_id', $regB->id)->where('signal_type', 'multi_team_roster')->exists())->toBeTrue();
});

test('non-captain cannot register a team', function () {
    $captain = User::factory()->create();
    $regularPlayer = User::factory()->create();

    $team = Team::create(['name' => 'Hawassa Eagles', 'slug' => 'hawassa-eagles', 'tag' => 'HWE', 'leader_id' => $captain->id]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $regularPlayer->id, 'role' => 'player', 'joined_at' => now()]);

    expect(fn () => app(RegisterTournamentAction::class)->execute($regularPlayer, $this->tournament, ['team_id' => $team->id]))
        ->toThrow(AuthorizationException::class, 'Only team captains, managers, or owners can register the team.');
});

test('non-organizer cannot generate tournament brackets', function () {
    $randomUser = User::factory()->create();

    expect(fn () => app(GenerateBracketAction::class)->execute($randomUser, $this->tournament))
        ->toThrow(AuthorizationException::class);
});

test('restricted match room credentials are only visible to authorized participants and staff', function () {
    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $spectator = User::factory()->create();

    $reg1 = TournamentRegistration::create(['tournament_id' => $this->tournament->id, 'user_id' => $p1->id, 'status' => 'approved', 'seed' => 1]);
    $reg2 = TournamentRegistration::create(['tournament_id' => $this->tournament->id, 'user_id' => $p2->id, 'status' => 'approved', 'seed' => 2]);

    $bracket = app(GenerateBracketAction::class)->execute($this->organizer, $this->tournament);
    $match = $this->tournament->matches()->first();

    // Organizer sets private room credentials
    app(UpdateMatchRoomAction::class)->execute($this->organizer, $match, [
        'room_id' => 'PRIVATE_LOBBY_882',
        'room_password' => 'secret_pw_999',
        'room_server' => 'Africa-East-1',
    ]);

    // Spectator hits API: room credentials must be null
    $this->actingAs($spectator);
    $respSpectator = $this->getJson("/api/v1/matches/{$match->id}");
    $respSpectator->assertStatus(200)
        ->assertJsonPath('data.room_id', null)
        ->assertJsonPath('data.room_password', null);

    // Participant 1 hits API: room credentials must be visible
    $this->actingAs($p1);
    $respP1 = $this->getJson("/api/v1/matches/{$match->id}");
    $respP1->assertStatus(200)
        ->assertJsonPath('data.room_id', 'PRIVATE_LOBBY_882')
        ->assertJsonPath('data.room_password', 'secret_pw_999');

    // Tournament Staff hits API: room credentials must be visible
    $referee = User::factory()->create();
    TournamentStaff::create([
        'tournament_id' => $this->tournament->id,
        'user_id' => $referee->id,
        'role' => 'referee',
    ]);
    $this->actingAs($referee);
    $respRef = $this->getJson("/api/v1/matches/{$match->id}");
    $respRef->assertStatus(200)
        ->assertJsonPath('data.room_id', 'PRIVATE_LOBBY_882');
});
