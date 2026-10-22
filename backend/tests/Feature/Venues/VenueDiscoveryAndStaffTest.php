<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Models\VenueStaff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->assignRole(RoleEnum::ORGANIZER->value);

    // Addis Ababa (lat: 9.02, lon: 38.74)
    $this->addisVenue = Venue::create([
        'owner_id' => $this->owner->id,
        'name' => 'Nexus Arena Addis',
        'slug' => 'nexus-arena-addis',
        'city' => 'Addis Ababa',
        'address' => 'Bole Medhanialem',
        'latitude' => 9.0222,
        'longitude' => 38.7468,
        'capacity' => 120,
        'facilities' => ['fiber_internet', 'rtx_gaming_pcs', 'streaming_studio'],
        'verified' => true,
    ]);

    // Hawassa (lat: 7.05, lon: 38.47)
    $this->hawassaVenue = Venue::create([
        'owner_id' => $this->owner->id,
        'name' => 'Lakeside LAN Hub',
        'slug' => 'lakeside-lan-hub',
        'city' => 'Hawassa',
        'address' => 'Lake View Road',
        'latitude' => 7.0500,
        'longitude' => 38.4700,
        'capacity' => 40,
        'facilities' => ['fiber_internet', 'ps5_consoles'],
        'verified' => true,
    ]);
});

test('attendees can discover venues filtered by city and facilities', function () {
    // City filter
    $resCity = $this->getJson('/api/v1/venues?city=Addis Ababa');
    $resCity->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nexus Arena Addis');

    // Facilities filter
    $resFac = $this->getJson('/api/v1/venues?facilities=streaming_studio');
    $resFac->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nexus Arena Addis');
});

test('venue discovery calculates haversine distance when coordinates are supplied', function () {
    // Search close to Addis Ababa
    $response = $this->getJson('/api/v1/venues?latitude=9.0200&longitude=38.7400&radius_km=30');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Nexus Arena Addis')
        ->and($data[0]['distance_km'])->toBeLessThan(5); // Within a few km of coordinates
});

test('venue owner can assign staff member with scanner-only role', function () {
    $staffUser = User::factory()->create();

    $response = $this->actingAs($this->owner, 'sanctum')->postJson("/api/v1/venues/{$this->addisVenue->id}/staff", [
        'user_id' => $staffUser->id,
        'role' => 'scanner',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Venue staff role 'scanner' assigned successfully.",
        ]);

    expect(VenueStaff::where('venue_id', $this->addisVenue->id)->where('user_id', $staffUser->id)->where('role', 'scanner')->exists())->toBeTrue();
});

test('attendee can submit venue review and average rating updates', function () {
    $reviewer = User::factory()->create();

    $response = $this->actingAs($reviewer, 'sanctum')->postJson("/api/v1/venues/{$this->addisVenue->id}/reviews", [
        'rating' => 5,
        'comment' => 'Fastest fiber internet in Bole, zero packet loss during tournament matches!',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Review submitted successfully.',
        ]);

    $this->addisVenue->refresh();
    expect($this->addisVenue->average_rating)->toBe(5.0)
        ->and($this->addisVenue->reviews_count)->toBe(1);
});

test('unauthorized user cannot access venue manager analytics', function () {
    $randomUser = User::factory()->create();

    $response = $this->actingAs($randomUser, 'sanctum')->getJson("/api/v1/venues/{$this->addisVenue->id}/analytics");

    $response->assertStatus(403);
});
