<?php

use App\Domain\Administration\Services\PlatformAnalyticsService;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('platform analytics counts venues using the verified column', function () {
    expect(Schema::hasColumn('venues', 'verified'))->toBeTrue()
        ->and(Schema::hasColumn('venues', 'is_verified'))->toBeFalse();

    $owner = User::factory()->create();

    Venue::create([
        'owner_id' => $owner->id,
        'name' => 'Verified Arena',
        'slug' => 'verified-arena-analytics',
        'city' => 'Addis Ababa',
        'address' => 'Bole',
        'capacity' => 50,
        'verified' => true,
    ]);

    Venue::create([
        'owner_id' => $owner->id,
        'name' => 'Unverified Cafe',
        'slug' => 'unverified-cafe-analytics',
        'city' => 'Addis Ababa',
        'address' => 'Piassa',
        'capacity' => 20,
        'verified' => false,
    ]);

    $metrics = app(PlatformAnalyticsService::class)->getExecutiveMetrics();

    expect($metrics['venues']['verified_venues'])->toBe(1)
        ->and($metrics['venues']['total_venues'])->toBe(2);
});
