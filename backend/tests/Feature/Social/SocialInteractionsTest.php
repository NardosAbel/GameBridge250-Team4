<?php

use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('user can react to a post with fire, trophy, or gg and toggle off', function () {
    $user = User::factory()->create();
    $post = Post::create([
        'user_id' => $user->id,
        'content' => 'Epic clutch in yesterday tournament!',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($user);

    // 1. React with fire
    $res1 = $this->postJson("/api/v1/interactions/reaction/post/{$post->id}", ['type' => 'fire']);
    $res1->assertOk()->assertJsonPath('status', 'created');

    $this->assertDatabaseHas('reactions', [
        'user_id' => $user->id,
        'reactable_id' => $post->id,
        'type' => 'fire',
    ]);

    // 2. React again with fire -> toggles off (deleted)
    $res2 = $this->postJson("/api/v1/interactions/reaction/post/{$post->id}", ['type' => 'fire']);
    $res2->assertOk()->assertJsonPath('status', 'removed');

    $this->assertDatabaseMissing('reactions', [
        'user_id' => $user->id,
        'reactable_id' => $post->id,
    ]);
});

test('user can add comment and nested threaded reply', function () {
    $author = User::factory()->create();
    $commenter = User::factory()->create();

    $post = Post::create([
        'user_id' => $author->id,
        'content' => 'Scrims start at 8pm Addis time.',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($commenter);

    // 1. Add top-level comment
    $res1 = $this->postJson("/api/v1/posts/{$post->id}/comments", [
        'content' => 'We will be there on time!',
    ]);

    $res1->assertCreated()
        ->assertJsonPath('data.content', 'We will be there on time!');

    $commentId = $res1->json('data.id');

    // 2. Add reply to comment
    $res2 = $this->postJson("/api/v1/posts/{$post->id}/comments", [
        'content' => 'Make sure your Discord is set up.',
        'parent_id' => $commentId,
    ]);

    $res2->assertCreated()
        ->assertJsonPath('data.parent_id', $commentId);
});

test('user can follow and unfollow players, teams, and communities', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Sanctum::actingAs($userA);

    // Follow User B
    $res1 = $this->postJson("/api/v1/interactions/follow/user/{$userB->id}");
    $res1->assertOk()->assertJsonPath('status', 'followed');

    $this->assertDatabaseHas('follows', [
        'follower_id' => $userA->id,
        'followable_type' => User::class,
        'followable_id' => $userB->id,
    ]);

    // Unfollow
    $res2 = $this->postJson("/api/v1/interactions/follow/user/{$userB->id}");
    $res2->assertOk()->assertJsonPath('status', 'unfollowed');

    $this->assertDatabaseMissing('follows', [
        'follower_id' => $userA->id,
        'followable_id' => $userB->id,
    ]);
});

test('user can share a post and bookmark a post', function () {
    $user = User::factory()->create();
    $post = Post::create([
        'user_id' => $user->id,
        'content' => 'Watch my live gameplay stream!',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($user);

    // Share
    $shareRes = $this->postJson("/api/v1/interactions/share/post/{$post->id}", [
        'comment' => 'Check this out!',
    ]);
    $shareRes->assertCreated();

    $this->assertDatabaseHas('shares', [
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);
    expect($post->fresh()->shares_count)->toBe(1);

    // Bookmark
    $bmRes = $this->postJson("/api/v1/interactions/bookmark/post/{$post->id}");
    $bmRes->assertOk()->assertJsonPath('status', 'bookmarked');

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'bookmarkable_id' => $post->id,
    ]);
});

test('blocking a user severs follows and excludes posts from feed', function () {
    $user = User::factory()->create();
    $blockedGamer = User::factory()->create();

    // Establish follow
    Follow::create([
        'follower_id' => $user->id,
        'followable_type' => User::class,
        'followable_id' => $blockedGamer->id,
    ]);

    Post::create([
        'user_id' => $blockedGamer->id,
        'content' => 'Spam content from toxic user',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($user);

    // Block
    $blockRes = $this->postJson("/api/v1/interactions/block/{$blockedGamer->id}");
    $blockRes->assertOk()->assertJsonPath('status', 'blocked');

    // Follow must be severed
    $this->assertDatabaseMissing('follows', [
        'follower_id' => $user->id,
        'followable_id' => $blockedGamer->id,
    ]);

    // Feed must NOT contain blocked user's post
    $feedRes = $this->getJson('/api/v1/posts?feed=personalized');
    $feedRes->assertOk();

    $feedContents = collect($feedRes->json('data'))->pluck('content');
    expect($feedContents)->not->toContain('Spam content from toxic user');
});

test('muting a user suppresses their posts from personalized feed', function () {
    $user = User::factory()->create();
    $annoyingUser = User::factory()->create();

    Post::create([
        'user_id' => $annoyingUser->id,
        'content' => 'Annoying promotional spam',
        'visibility' => 'public',
    ]);

    Sanctum::actingAs($user);

    // Mute
    $muteRes = $this->postJson("/api/v1/interactions/mute/{$annoyingUser->id}");
    $muteRes->assertOk()->assertJsonPath('status', 'muted');

    // Feed must NOT contain muted user's post
    $feedRes = $this->getJson('/api/v1/posts?feed=personalized');
    $feedRes->assertOk();

    $feedContents = collect($feedRes->json('data'))->pluck('content');
    expect($feedContents)->not->toContain('Annoying promotional spam');
});
