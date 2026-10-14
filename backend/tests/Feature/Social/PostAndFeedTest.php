<?php

use App\Domain\Profiles\Models\Game;
use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('authenticated user can create a post with hashtags and location tag', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $game = Game::first();

    $response = $this->postJson('/api/v1/posts', [
        'title' => 'Scouting for Addis Ababa Scrims',
        'content' => 'Looking for top scrim matches this weekend! #esports #ethiopia #dota2',
        'type' => 'text',
        'visibility' => 'public',
        'game_id' => $game->id,
        'location_tag' => 'Addis Ababa University',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Scouting for Addis Ababa Scrims')
        ->assertJsonPath('data.location_tag', 'Addis Ababa University');

    expect($response->json('data.hashtags'))->toContain('esports', 'ethiopia', 'dota2');

    $this->assertDatabaseHas('posts', [
        'user_id' => $user->id,
        'title' => 'Scouting for Addis Ababa Scrims',
        'location_tag' => 'Addis Ababa University',
    ]);
});

test('user can create a poll and vote on it', function () {
    $author = User::factory()->create();
    $voter = User::factory()->create();

    Sanctum::actingAs($author);

    $response = $this->postJson('/api/v1/posts', [
        'title' => 'Favorite Campus Esport Game?',
        'content' => 'Vote for the primary tournament title for AAU games:',
        'type' => 'poll',
        'visibility' => 'public',
        'poll_options' => ['Dota 2', 'PUBG Mobile', 'FC 25'],
    ]);

    $response->assertCreated();
    $postId = $response->json('data.id');

    // Voter casts vote for Option 0 (Dota 2)
    Sanctum::actingAs($voter);

    $voteResponse = $this->postJson("/api/v1/posts/{$postId}/poll/vote", [
        'option_index' => 0,
    ]);

    $voteResponse->assertOk()
        ->assertJsonPath('data.poll_data.options.0.votes', 1);

    // Voter cannot vote twice
    $secondVote = $this->postJson("/api/v1/posts/{$postId}/poll/vote", [
        'option_index' => 1,
    ]);

    $secondVote->assertStatus(500); // InvalidArgumentException caught
});

test('feed returns personalized posts from followed users', function () {
    $user = User::factory()->create();
    $followedGamer = User::factory()->create();
    $unfollowedGamer = User::factory()->create();

    // Follow gamer
    Follow::create([
        'follower_id' => $user->id,
        'followable_type' => User::class,
        'followable_id' => $followedGamer->id,
    ]);

    // Create post by followed gamer
    Post::create([
        'user_id' => $followedGamer->id,
        'content' => 'Followed gamer update',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/posts?feed=personalized');

    $response->assertOk()
        ->assertJsonFragment(['content' => 'Followed gamer update']);
});

test('author can delete their own post but unauthorized user cannot', function () {
    $author = User::factory()->create();
    $stranger = User::factory()->create();

    $post = Post::create([
        'user_id' => $author->id,
        'content' => 'Post to be deleted',
        'visibility' => 'public',
    ]);

    // Stranger tries to delete
    Sanctum::actingAs($stranger);
    $this->deleteJson("/api/v1/posts/{$post->id}")->assertForbidden();

    // Author deletes
    Sanctum::actingAs($author);
    $this->deleteJson("/api/v1/posts/{$post->id}")->assertOk();

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
});
