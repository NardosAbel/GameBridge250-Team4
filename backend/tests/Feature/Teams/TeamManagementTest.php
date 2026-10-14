<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('user can create a team and automatically becomes owner with roster history recorded', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $game = Game::first();

    $response = $this->postJson('/api/v1/teams', [
        'name' => 'AAU Cyber Eagles',
        'tag' => 'ACE',
        'bio' => 'Addis Ababa University premier varsity squad',
        'game_id' => $game->id,
        'university' => 'Addis Ababa University',
        'location' => 'Addis Ababa',
        'recruitment_status' => 'open',
        'achievements' => ['Inter-College Spring 2026 Champion'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'AAU Cyber Eagles')
        ->assertJsonPath('data.tag', 'ACE');

    $teamId = $response->json('data.id');

    // Verify creator is owner
    $this->assertDatabaseHas('team_members', [
        'team_id' => $teamId,
        'user_id' => $user->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    // Verify initial roster history
    $this->assertDatabaseHas('team_roster_history', [
        'team_id' => $teamId,
        'user_id' => $user->id,
        'action' => 'joined',
        'role' => 'owner',
    ]);
});

test('team captain can invite player and player can accept invitation', function () {
    $captain = User::factory()->create();
    $recruit = User::factory()->create();

    Sanctum::actingAs($captain);

    $team = Team::create([
        'name' => 'Jimma Titans',
        'tag' => 'JT',
        'leader_id' => $captain->id,
        'recruitment_status' => 'open',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    // Invite recruit
    $inviteRes = $this->postJson("/api/v1/teams/{$team->id}/invite", [
        'user_id' => $recruit->id,
        'type' => 'invitation',
        'role' => 'player',
        'message' => 'Join our squad for the upcoming championship!',
    ]);

    $inviteRes->assertCreated();
    $invitationId = $inviteRes->json('data.id');

    // Recruit accepts invitation
    Sanctum::actingAs($recruit);
    $acceptRes = $this->postJson("/api/v1/team-invitations/{$invitationId}/respond", [
        'response' => 'accepted',
    ]);

    $acceptRes->assertOk();

    // Verify member added
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $recruit->id,
        'role' => 'player',
        'status' => 'active',
    ]);

    // Verify roster history
    $this->assertDatabaseHas('team_roster_history', [
        'team_id' => $team->id,
        'user_id' => $recruit->id,
        'action' => 'joined',
    ]);
});

test('captain can promote member to coach or substitute', function () {
    $captain = User::factory()->create();
    $player = User::factory()->create();

    $team = Team::create([
        'name' => 'Hawassa Sharks',
        'tag' => 'HWS',
        'leader_id' => $captain->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    $member = TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $player->id,
        'role' => 'player',
        'status' => 'active',
    ]);

    Sanctum::actingAs($captain);

    $res = $this->putJson("/api/v1/team-members/{$member->id}/role", [
        'role' => 'coach',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.role', 'coach');

    $this->assertDatabaseHas('team_roster_history', [
        'team_id' => $team->id,
        'user_id' => $player->id,
        'action' => 'promoted',
        'role' => 'coach',
    ]);
});

test('owner can transfer team ownership', function () {
    $owner = User::factory()->create();
    $newOwner = User::factory()->create();

    $team = Team::create([
        'name' => 'Mekelle Lions',
        'tag' => 'MKL',
        'leader_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $newOwner->id,
        'role' => 'captain',
        'status' => 'active',
    ]);

    Sanctum::actingAs($owner);

    $res = $this->postJson("/api/v1/teams/{$team->id}/transfer-ownership", [
        'new_owner_id' => $newOwner->id,
    ]);

    $res->assertOk();

    expect($team->fresh()->leader_id)->toBe($newOwner->id);

    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $newOwner->id,
        'role' => 'owner',
    ]);

    $this->assertDatabaseHas('team_roster_history', [
        'team_id' => $team->id,
        'user_id' => $newOwner->id,
        'action' => 'transferred',
    ]);
});

test('unauthorized user cannot update team details or kick players', function () {
    $captain = User::factory()->create();
    $stranger = User::factory()->create();
    $player = User::factory()->create();

    $team = Team::create([
        'name' => 'Bahir Dar Hawks',
        'tag' => 'BDH',
        'leader_id' => $captain->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    $member = TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $player->id,
        'role' => 'player',
        'status' => 'active',
    ]);

    Sanctum::actingAs($stranger);

    // Stranger tries to update team
    $this->putJson("/api/v1/teams/{$team->id}", ['name' => 'Hijacked'])->assertForbidden();

    // Stranger tries to kick player
    $this->deleteJson("/api/v1/team-members/{$member->id}")->assertForbidden();
});
