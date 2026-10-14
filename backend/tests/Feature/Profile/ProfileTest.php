<?php

use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use Database\Seeders\RoleSeeder;

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
