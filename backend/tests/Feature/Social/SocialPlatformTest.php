<?php

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Profiles\Models\Game;
use App\Domain\Social\Models\Post;
use App\Domain\Teams\Models\Team;
use App\Domain\Tournaments\Actions\CreateTournamentAction;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('organizer');
    Role::findOrCreate('player');
    Role::findOrCreate('sponsor');

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole('organizer');
    $this->player = User::factory()->create();
    $this->player->assignRole('player');
    $this->sponsor = User::factory()->create();
    $this->sponsor->assignRole('sponsor');

    $this->game = Game::create([
        'name' => 'Social FC',
        'slug' => 'social-fc',
        'category' => 'sports',
        'publisher' => 'Test',
        'is_active' => true,
    ]);

    $this->tournament = app(CreateTournamentAction::class)->execute($this->organizer, [
        'title' => 'Social Open',
        'game_id' => $this->game->id,
        'format' => 'single_elimination',
        'match_format' => 'bo1',
        'entry_type' => 'free',
        'capacity' => 8,
        'rules' => 'Organizer confirms results.',
        'prize_pool' => 0,
    ]);
    $this->tournament->update([
        'status' => 'registration_open',
        'visibility' => 'public',
    ]);
});

test('a player can post, like once, and comment, and a stranger cannot delete it', function () {
    $this->actingAs($this->player, 'sanctum')
        ->postJson('/api/v1/posts', [
            'content' => 'Scrim notes from the lab.',
            'tournament_id' => $this->tournament->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.tournament.title', 'Social Open')
        ->assertJsonPath('data.sponsored', false);

    $post = Post::query()->first();

    $this->actingAs($this->sponsor, 'sanctum')
        ->postJson("/api/v1/interactions/reaction/post/{$post->id}", ['type' => 'like'])
        ->assertOk()
        ->assertJsonPath('status', 'created');

    $this->actingAs($this->sponsor, 'sanctum')
        ->postJson("/api/v1/interactions/reaction/post/{$post->id}", ['type' => 'like'])
        ->assertOk()
        ->assertJsonPath('status', 'removed');

    $this->actingAs($this->sponsor, 'sanctum')
        ->postJson("/api/v1/posts/{$post->id}/comments", ['content' => 'See you there.'])
        ->assertCreated();

    $this->assertDatabaseCount('comments', 1);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $this->player->id,
    ]);

    $this->actingAs($this->sponsor, 'sanctum')
        ->deleteJson("/api/v1/posts/{$post->id}")
        ->assertForbidden();
});

test('only the tournament organizer can publish an announcement and only a sponsor can mark a post sponsored', function () {
    $this->actingAs($this->player, 'sanctum')
        ->postJson('/api/v1/posts', [
            'content' => 'Registration moved.',
            'type' => 'announcement',
            'tournament_id' => $this->tournament->id,
        ])
        ->assertStatus(422);

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson('/api/v1/posts', [
            'content' => 'Registration closes Friday.',
            'type' => 'announcement',
            'tournament_id' => $this->tournament->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'announcement');

    $this->actingAs($this->player, 'sanctum')
        ->postJson('/api/v1/posts', [
            'content' => 'Buy this.',
            'sponsored' => true,
        ])
        ->assertStatus(422);

    $this->actingAs($this->sponsor, 'sanctum')
        ->postJson('/api/v1/posts', [
            'content' => 'Campus cafe is covering the final.',
            'sponsored' => true,
            'tournament_id' => $this->tournament->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.sponsored', true);
});

test('a player cannot post as a team they do not belong to', function () {
    $team = Team::create([
        'name' => 'Closed Squad',
        'tag' => 'CLSD',
        'leader_id' => $this->organizer->id,
        'game_id' => $this->game->id,
        'recruitment_status' => 'closed',
    ]);

    $this->actingAs($this->player, 'sanctum')
        ->postJson('/api/v1/posts', [
            'content' => 'We are in.',
            'team_id' => $team->id,
        ])
        ->assertStatus(422);
});

test('a private community channel is hidden from someone who only knows the url', function () {
    $community = Community::create([
        'name' => 'Private Lab',
        'description' => 'Members only.',
        'type' => 'interest',
        'visibility' => 'private',
        'creator_id' => $this->organizer->id,
    ]);

    CommunityMember::create([
        'community_id' => $community->id,
        'user_id' => $this->organizer->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $channel = $this->actingAs($this->organizer, 'sanctum')
        ->getJson("/api/v1/chat/communities/{$community->id}/channels")
        ->assertOk()
        ->json('data.0.id');

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/chat/channels/{$channel}/messages", ['message' => 'Strategy stays here.'])
        ->assertCreated();

    $this->actingAs($this->player, 'sanctum')
        ->getJson("/api/v1/chat/channels/{$channel}/messages")
        ->assertForbidden();

    $this->actingAs($this->player, 'sanctum')
        ->getJson("/api/v1/chat/communities/{$community->id}/channels")
        ->assertForbidden();
});

test('follow is stored once and a second request unfollows', function () {
    $this->actingAs($this->player, 'sanctum')
        ->postJson("/api/v1/interactions/follow/user/{$this->organizer->id}")
        ->assertOk()
        ->assertJsonPath('is_following', true);

    $this->assertDatabaseCount('follows', 1);

    $this->actingAs($this->player, 'sanctum')
        ->postJson("/api/v1/interactions/follow/user/{$this->organizer->id}")
        ->assertOk()
        ->assertJsonPath('is_following', false);

    $this->assertDatabaseCount('follows', 0);
});
