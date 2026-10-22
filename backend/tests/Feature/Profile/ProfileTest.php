<?php

use App\Domain\Profiles\Models\Profile;
use App\Domain\Social\Models\Follow;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can update gamer tag and bio', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/profile', [
            'gamer_tag' => 'AlphaStriker',
            'bio' => 'Top ranked player in Addis Ababa University',
            'university' => 'Addis Ababa University',
            'school_id_number' => 'UGR/9999/14',
            'is_public' => true,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'gamer_tag' => 'AlphaStriker',
                'university' => 'Addis Ababa University',
            ],
        ]);

    $profile = Profile::where('user_id', $user->id)->first();
    expect($profile->gamer_tag)->toBe('AlphaStriker')
        ->and($profile->school_id_number)->toBe('UGR/9999/14');
});

test('anyone can view public profile by gamer tag', function () {
    $user = User::factory()->create();
    Profile::create([
        'user_id' => $user->id,
        'gamer_tag' => 'PublicStar',
        'university' => 'Jimma University',
        'is_public' => true,
    ]);

    $response = $this->getJson('/api/v1/profiles/public/PublicStar');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'gamer_tag' => 'PublicStar',
                'university' => 'Jimma University',
            ],
        ]);
});

test('private profile cannot be accessed publicly', function () {
    $user = User::factory()->create();
    Profile::create([
        'user_id' => $user->id,
        'gamer_tag' => 'PrivateSniper',
        'is_public' => false,
    ]);

    $response = $this->getJson('/api/v1/profiles/public/PrivateSniper');

    $response->assertStatus(404);
});

test('a public profile hides the school id number from other users', function () {
    $owner = User::factory()->create();
    Profile::create([
        'user_id' => $owner->id,
        'gamer_tag' => 'CampusAce',
        'school_id_number' => 'UGR/1111/14',
        'is_public' => true,
        'privacy_settings' => ['visibility' => 'public'],
    ]);

    $stranger = User::factory()->create();

    $this->actingAs($stranger, 'sanctum')
        ->getJson('/api/v1/profiles/public/CampusAce')
        ->assertOk()
        ->assertJsonPath('data.school_id_number', null);
});

test('a followers-only profile is hidden from users who do not follow the player', function () {
    $owner = User::factory()->create();
    Profile::create([
        'user_id' => $owner->id,
        'gamer_tag' => 'FollowOnly',
        'is_public' => true,
        'privacy_settings' => ['visibility' => 'followers'],
    ]);

    $this->getJson('/api/v1/profiles/public/FollowOnly')->assertNotFound();
});

test('a followers-only profile is visible to an authenticated follower', function () {
    $owner = User::factory()->create();
    Profile::create([
        'user_id' => $owner->id,
        'gamer_tag' => 'FollowVisible',
        'is_public' => true,
        'privacy_settings' => ['visibility' => 'followers'],
    ]);

    $follower = User::factory()->create();
    Follow::create([
        'follower_id' => $follower->id,
        'followable_type' => $owner->getMorphClass(),
        'followable_id' => $owner->id,
    ]);

    $this->actingAs($follower, 'sanctum')
        ->getJson('/api/v1/profiles/public/FollowVisible')
        ->assertOk()
        ->assertJsonPath('data.gamer_tag', 'FollowVisible');
});

test('followers visibility works even when the legacy is_public flag is false', function () {
    $owner = User::factory()->create();
    Profile::create([
        'user_id' => $owner->id,
        'gamer_tag' => 'FollowLegacyFlag',
        'is_public' => false,
        'privacy_settings' => ['visibility' => 'followers'],
    ]);

    $follower = User::factory()->create();
    Follow::create([
        'follower_id' => $follower->id,
        'followable_type' => $owner->getMorphClass(),
        'followable_id' => $owner->id,
    ]);

    $this->getJson('/api/v1/profiles/public/FollowLegacyFlag')->assertNotFound();

    $this->actingAs($follower, 'sanctum')
        ->getJson('/api/v1/profiles/public/FollowLegacyFlag')
        ->assertOk();
});

test('an owner can view their own private profile by gamer tag', function () {
    $owner = User::factory()->create();
    Profile::create([
        'user_id' => $owner->id,
        'gamer_tag' => 'OwnerOnly',
        'is_public' => false,
        'privacy_settings' => ['visibility' => 'private'],
    ]);

    $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/profiles/public/OwnerOnly')
        ->assertOk()
        ->assertJsonPath('data.gamer_tag', 'OwnerOnly');
});

test('profile policy view matches public followers private and owner rules', function () {
    $owner = User::factory()->create();
    $public = Profile::create([
        'user_id' => $owner->id,
        'gamer_tag' => 'PolicyPublic',
        'is_public' => true,
        'privacy_settings' => ['visibility' => 'public'],
    ]);

    $privateOwner = User::factory()->create();
    $private = Profile::create([
        'user_id' => $privateOwner->id,
        'gamer_tag' => 'PolicyPrivate',
        'is_public' => false,
        'privacy_settings' => ['visibility' => 'private'],
    ]);

    $followersOwner = User::factory()->create();
    $followers = Profile::create([
        'user_id' => $followersOwner->id,
        'gamer_tag' => 'PolicyFollowers',
        'is_public' => true,
        'privacy_settings' => ['visibility' => 'followers'],
    ]);

    $stranger = User::factory()->create();
    $follower = User::factory()->create();
    Follow::create([
        'follower_id' => $follower->id,
        'followable_type' => $followersOwner->getMorphClass(),
        'followable_id' => $followersOwner->id,
    ]);

    expect(Gate::forUser(null)->allows('view', $public))->toBeTrue()
        ->and(Gate::forUser($stranger)->denies('view', $private))->toBeTrue()
        ->and(Gate::forUser($privateOwner)->allows('view', $private))->toBeTrue()
        ->and(Gate::forUser($stranger)->denies('view', $followers))->toBeTrue()
        ->and(Gate::forUser($follower)->allows('view', $followers))->toBeTrue();
});
