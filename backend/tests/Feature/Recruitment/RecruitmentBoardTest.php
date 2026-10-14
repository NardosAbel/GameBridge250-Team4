<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('player can publish looking for team notice with filters', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $game = Game::first();

    $response = $this->postJson('/api/v1/recruitment', [
        'type' => 'lft',
        'game_id' => $game->id,
        'title' => 'Top Midlaner LFT for AAU Varsity Team',
        'description' => '5000+ MMR, available all evenings, experienced in collegiate scrims.',
        'rank_tier' => 'Divine 3',
        'role' => 'Midlaner',
        'location' => 'Addis Ababa',
        'university' => 'Addis Ababa University',
        'availability' => 'Evenings & Weekends',
        'platform' => 'PC',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Top Midlaner LFT for AAU Varsity Team')
        ->assertJsonPath('data.type', 'lft')
        ->assertJsonPath('data.platform', 'PC');

    $this->assertDatabaseHas('recruitment_posts', [
        'user_id' => $user->id,
        'type' => 'lft',
        'role' => 'Midlaner',
        'status' => 'open',
    ]);
});

test('team captain can post looking for players notice', function () {
    $captain = User::factory()->create();
    Sanctum::actingAs($captain);

    $team = Team::create([
        'name' => 'Hawassa Vipers',
        'tag' => 'HV',
        'leader_id' => $captain->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'role' => 'owner',
        'status' => 'active',
    ]);

    $game = Game::first();

    $response = $this->postJson('/api/v1/recruitment', [
        'type' => 'lfp',
        'team_id' => $team->id,
        'game_id' => $game->id,
        'title' => 'Seeking Hard Support for National Cup',
        'description' => 'Must have good comms and reliable connection.',
        'role' => 'Hard Support',
        'platform' => 'PC',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'lfp')
        ->assertJsonPath('data.team.tag', 'HV');
});

test('recruitment board can be filtered by game, platform, and type', function () {
    $user = User::factory()->create();
    $game = Game::first();

    RecruitmentPost::create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'type' => 'lft',
        'title' => 'Mobile Fragger LFT',
        'description' => 'Top rank player',
        'platform' => 'Mobile',
        'status' => 'open',
    ]);

    RecruitmentPost::create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'type' => 'lft',
        'title' => 'PC Sniper LFT',
        'description' => 'Top sniper',
        'platform' => 'PC',
        'status' => 'open',
    ]);

    $res = $this->getJson('/api/v1/recruitment?platform=Mobile');
    $res->assertOk();

    $titles = collect($res->json('data'))->pluck('title');
    expect($titles)->toContain('Mobile Fragger LFT');
    expect($titles)->not->toContain('PC Sniper LFT');
});

test('author can close recruitment post', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $game = Game::first();

    $post = RecruitmentPost::create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'type' => 'lft',
        'title' => 'LFT Notice to Close',
        'description' => 'Details',
        'status' => 'open',
    ]);

    $res = $this->postJson("/api/v1/recruitment/{$post->id}/close");
    $res->assertOk()
        ->assertJsonPath('data.status', 'closed');

    expect($post->fresh()->status)->toBe('closed');
});
