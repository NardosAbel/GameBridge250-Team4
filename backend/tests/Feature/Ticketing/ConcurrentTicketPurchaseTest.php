<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Ticketing\Models\PromoCode;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole(RoleEnum::ORGANIZER->value);

    $this->venue = Venue::create([
        'owner_id' => $this->organizer->id,
        'name' => 'Addis Gaming Arena',
        'slug' => 'addis-gaming-arena',
        'city' => 'Addis Ababa',
        'address' => 'Bole Medhanialem',
        'capacity' => 100,
        'facilities' => ['fiber_internet', 'high_refresh_monitors'],
        'is_verified' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'National Inter-Varsity Cup',
        'slug' => 'national-inter-varsity-cup',
        'event_type' => 'lan_tournament',
        'start_time' => now()->addDays(7),
        'end_time' => now()->addDays(7)->addHours(8),
        'capacity' => 80,
        'status' => 'published',
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Early Bird Player Pass',
        'category' => 'player_pass',
        'price' => 150.00,
        'currency' => 'ETB',
        'quantity_total' => 10,
        'quantity_sold' => 0,
        'min_per_order' => 1,
        'max_per_order' => 4,
        'is_active' => true,
    ]);
});

test('buyer can place ticket order and inventory decrements atomically', function () {
    $buyer = User::factory()->create();

    $response = $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/tickets/checkout', [
        'items' => [
            [
                'ticket_type_id' => $this->ticketType->id,
                'quantity' => 3,
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Ticket order placed successfully.',
        ])
        ->assertJsonPath('data.total_amount', 450);

    $this->ticketType->refresh();
    expect($this->ticketType->remaining_quantity)->toBe(7);
});

test('overselling prevention rejects purchase when requested quantity exceeds inventory', function () {
    $buyer = User::factory()->create();

    // Request 11 tickets when only 10 exist
    $response = $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/tickets/checkout', [
        'items' => [
            [
                'ticket_type_id' => $this->ticketType->id,
                'quantity' => 11,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    $this->ticketType->refresh();
    expect($this->ticketType->remaining_quantity)->toBe(10);
});

test('promo code applies discount properly to ticket order', function () {
    PromoCode::create([
        'code' => 'STUDENT50',
        'discount_type' => 'percentage',
        'discount_value' => 50.00,
        'usage_limit' => 100,
        'used_count' => 0,
        'is_active' => true,
    ]);

    $buyer = User::factory()->create();

    $response = $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/tickets/checkout', [
        'items' => [
            [
                'ticket_type_id' => $this->ticketType->id,
                'quantity' => 2, // 2 * 150 = 300 ETB
            ],
        ],
        'promo_code' => 'STUDENT50',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.subtotal', 300)
        ->assertJsonPath('data.discount_amount', 150)
        ->assertJsonPath('data.total_amount', 150);
});
