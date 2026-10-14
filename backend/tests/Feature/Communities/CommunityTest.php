<?php

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('user can create a community and automatically becomes admin', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/communities', [
        'name' => 'AAU 4 Kilo Dota 2 Guild',
        'description' => 'Official student gaming hub for Addis Ababa University 4 Kilo campus',
        'type' => 'school',
        'visibility' => 'public',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'AAU 4 Kilo Dota 2 Guild');

    $communityId = $response->json('data.id');

    $this->assertDatabaseHas('community_members', [
        'community_id' => $communityId,
        'user_id' => $user->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
});

test('user joins public community directly and joins approval community as pending', function () {
    $admin = User::factory()->create();
    $student = User::factory()->create();

    // 1. Public community
    $pubComm = Community::create([
        'name' => 'Ethiopian Fighting Game Community',
        'type' => 'game',
        'visibility' => 'public',
        'creator_id' => $admin->id,
    ]);

    Sanctum::actingAs($student);
    $resPub = $this->postJson("/api/v1/communities/{$pubComm->id}/join");
    $resPub->assertCreated()
        ->assertJsonPath('data.status', 'active');

    // 2. Approval community
    $appComm = Community::create([
        'name' => 'AASTU Verified Varsity Club',
        'type' => 'school',
        'visibility' => 'approval',
        'creator_id' => $admin->id,
    ]);

    $resApp = $this->postJson("/api/v1/communities/{$appComm->id}/join");
    $resApp->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $memberRecordId = $resApp->json('data.id');

    // Admin approves student
    Sanctum::actingAs($admin);
    $approveRes = $this->postJson("/api/v1/community-members/{$memberRecordId}/approve", [
        'approved' => true,
    ]);
    $approveRes->assertOk()
        ->assertJsonPath('data.status', 'active');
});

test('community member can view community posts and unjoined user cannot view private community posts', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $stranger = User::factory()->create();

    $community = Community::create([
        'name' => 'Secret Tactics Club',
        'type' => 'game',
        'visibility' => 'private',
        'creator_id' => $admin->id,
    ]);

    CommunityMember::create([
        'community_id' => $community->id,
        'user_id' => $member->id,
        'role' => 'member',
        'status' => 'active',
    ]);

    Post::create([
        'user_id' => $admin->id,
        'community_id' => $community->id,
        'content' => 'Classified scrim strat for tournament',
        'visibility' => 'community',
    ]);

    // Stranger tries to view private community posts
    Sanctum::actingAs($stranger);
    $this->getJson("/api/v1/communities/{$community->id}/posts")->assertForbidden();

    // Active member views posts
    Sanctum::actingAs($member);
    $res = $this->getJson("/api/v1/communities/{$community->id}/posts");
    $res->assertOk()
        ->assertJsonFragment(['content' => 'Classified scrim strat for tournament']);
});
