<?php

use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('team member can access team channel, send chat message, and mention teammate', function () {
    $player1 = User::factory()->create(['name' => 'Abebe']);
    $player2 = User::factory()->create(['name' => 'Chala']);

    Profile::create([
        'user_id' => $player2->id,
        'gamer_tag' => 'ChalaGod',
        'handle' => 'chalagod',
    ]);

    $team = Team::create([
        'name' => 'Gondar Esports',
        'tag' => 'GDR',
        'leader_id' => $player1->id,
    ]);

    TeamMember::create(['team_id' => $team->id, 'user_id' => $player1->id, 'role' => 'owner', 'status' => 'active']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $player2->id, 'role' => 'player', 'status' => 'active']);

    Sanctum::actingAs($player1);

    // Get channel
    $chRes = $this->getJson("/api/v1/chat/teams/{$team->id}");
    $chRes->assertOk();
    $channelId = $chRes->json('data.id');

    // Send message with @mention
    $msgRes = $this->postJson("/api/v1/chat/channels/{$channelId}/messages", [
        'message' => 'Hey @chalagod scrim starts in 10 minutes, be ready!',
    ]);

    $msgRes->assertCreated()
        ->assertJsonPath('data.message', 'Hey @chalagod scrim starts in 10 minutes, be ready!');

    expect($msgRes->json('data.mentions'))->toContain($player2->id);

    $this->assertDatabaseHas('chat_messages', [
        'channel_id' => $channelId,
        'user_id' => $player1->id,
    ]);
});

test('non-team member cannot access or send message to team chat', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $team = Team::create([
        'name' => 'Debre Berhan Wolves',
        'tag' => 'DBW',
        'leader_id' => $owner->id,
    ]);

    TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => 'owner', 'status' => 'active']);

    $channel = ChatChannel::create([
        'type' => 'team',
        'team_id' => $team->id,
        'name' => 'DBW Chat',
    ]);

    Sanctum::actingAs($stranger);

    $this->getJson("/api/v1/chat/channels/{$channel->id}/messages")->assertForbidden();

    $this->postJson("/api/v1/chat/channels/{$channel->id}/messages", [
        'message' => 'I should not be here',
    ])->assertForbidden();
});

test('user can update notification preferences across channels', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Initial preferences
    $getRes = $this->getJson('/api/v1/notifications/preferences');
    $getRes->assertOk()
        ->assertJsonPath('data.in_app', true)
        ->assertJsonPath('data.sms', false);

    // Update preferences (enable SMS for match calls, disable push)
    $updateRes = $this->putJson('/api/v1/notifications/preferences', [
        'in_app' => true,
        'email' => true,
        'sms' => true,
        'push' => false,
    ]);

    $updateRes->assertOk()
        ->assertJsonPath('data.sms', true)
        ->assertJsonPath('data.push', false);

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'sms' => true,
        'push' => false,
    ]);
});
