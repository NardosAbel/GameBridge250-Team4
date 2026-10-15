<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Ticketing\Actions\IssueTicketsAction;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketOrderItem;
use App\Domain\Ticketing\Models\TicketScan;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Models\VenueStaff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole(RoleEnum::ORGANIZER->value);

    $this->scanner = User::factory()->create();

    $this->venue = Venue::create([
        'owner_id' => $this->organizer->id,
        'name' => 'Bole LAN Arena',
        'slug' => 'bole-lan-arena',
        'city' => 'Addis Ababa',
        'address' => 'Airport Road',
        'capacity' => 100,
        'is_verified' => true,
    ]);

    // Assign scanner staff permissions
    VenueStaff::create([
        'venue_id' => $this->venue->id,
        'user_id' => $this->scanner->id,
        'role' => 'scanner',
        'is_active' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'Inter-Campus Dota 2 Cup',
        'slug' => 'inter-campus-dota-2-cup',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(5),
        'capacity' => 60,
        'status' => 'published',
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'General Admission',
        'category' => 'general_admission',
        'price' => 100.00,
        'currency' => 'ETB',
        'quantity_total' => 50,
        'quantity_sold' => 0,
        'min_per_order' => 1,
        'max_per_order' => 10,
        'is_active' => true,
    ]);

    $this->attendee = User::factory()->create();

    $this->order = TicketOrder::create([
        'order_number' => 'ORD-SCAN-1234',
        'event_id' => $this->event->id,
        'user_id' => $this->attendee->id,
        'subtotal' => 100.00,
        'total_amount' => 100.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    TicketOrderItem::create([
        'ticket_order_id' => $this->order->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 100.00,
        'quantity' => 1,
        'subtotal' => 100.00,
    ]);

    $this->ticket = app(IssueTicketsAction::class)->execute($this->order)->first();
});

test('scanner can validate valid ticket and check in attendee at gate', function () {
    $response = $this->actingAs($this->scanner, 'sanctum')->postJson('/api/v1/tickets/scan', [
        'qr_payload' => $this->ticket->qr_payload,
        'venue_id' => $this->venue->id,
        'event_id' => $this->event->id,
        'device_id' => 'gate-scanner-01',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'status' => 'valid',
            'message' => 'Ticket successfully verified. Entry approved.',
        ]);

    $this->ticket->refresh();
    expect($this->ticket->status)->toBe('checked_in');

    expect(TicketScan::where('ticket_id', $this->ticket->id)->where('status', 'valid')->exists())->toBeTrue();
});

test('duplicate scan of same ticket is rejected with 409 conflict', function () {
    // First scan -> valid
    $this->actingAs($this->scanner, 'sanctum')->postJson('/api/v1/tickets/scan', [
        'qr_payload' => $this->ticket->qr_payload,
        'venue_id' => $this->venue->id,
        'event_id' => $this->event->id,
    ])->assertStatus(200);

    // Second scan -> duplicate rejected
    $duplicate = $this->actingAs($this->scanner, 'sanctum')->postJson('/api/v1/tickets/scan', [
        'qr_payload' => $this->ticket->qr_payload,
        'venue_id' => $this->venue->id,
        'event_id' => $this->event->id,
    ]);

    $duplicate->assertStatus(409)
        ->assertJson([
            'success' => false,
            'status' => 'duplicate',
        ]);

    expect(TicketScan::where('ticket_id', $this->ticket->id)->where('status', 'duplicate')->exists())->toBeTrue();
});

test('offline scans batch synchronization reconciles queued entries correctly', function () {
    // Create second ticket
    $order2 = TicketOrder::create([
        'order_number' => 'ORD-SCAN-5678',
        'user_id' => User::factory()->create()->id,
        'subtotal' => 100.00,
        'total_amount' => 100.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);
    TicketOrderItem::create([
        'ticket_order_id' => $order2->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 100.00,
        'quantity' => 1,
        'subtotal' => 100.00,
    ]);
    $ticket2 = app(IssueTicketsAction::class)->execute($order2)->first();

    $queuedScans = [
        [
            'qr_payload' => $this->ticket->qr_payload,
            'venue_id' => $this->venue->id,
            'event_id' => $this->event->id,
            'device_id' => 'offline-tablet-01',
            'scanned_at' => now()->subMinutes(10)->toISOString(),
        ],
        [
            'qr_payload' => $this->ticket->qr_payload, // Duplicate of first
            'venue_id' => $this->venue->id,
            'event_id' => $this->event->id,
            'device_id' => 'offline-tablet-01',
            'scanned_at' => now()->subMinutes(8)->toISOString(),
        ],
        [
            'qr_payload' => $ticket2->qr_payload, // Valid second ticket
            'venue_id' => $this->venue->id,
            'event_id' => $this->event->id,
            'device_id' => 'offline-tablet-01',
            'scanned_at' => now()->subMinutes(5)->toISOString(),
        ],
        [
            'qr_payload' => json_encode(['ref' => 'TIC-FAKE-999', 'sig' => 'fake-sig']), // Invalid ticket
            'venue_id' => $this->venue->id,
            'event_id' => $this->event->id,
            'device_id' => 'offline-tablet-01',
            'scanned_at' => now()->subMinutes(2)->toISOString(),
        ],
    ];

    $response = $this->actingAs($this->scanner, 'sanctum')->postJson('/api/v1/tickets/sync-scans', [
        'scans' => $queuedScans,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.synced', 2)
        ->assertJsonPath('data.duplicates', 1)
        ->assertJsonPath('data.invalid', 1);

    $this->ticket->refresh();
    $ticket2->refresh();
    expect($this->ticket->status)->toBe('checked_in')
        ->and($ticket2->status)->toBe('checked_in');
});

test('scanner rejects ticket if presented at wrong event', function () {
    $otherEvent = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'Other Unrelated Event',
        'slug' => 'other-unrelated-event',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(4),
        'capacity' => 20,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->scanner, 'sanctum')->postJson('/api/v1/tickets/scan', [
        'qr_payload' => $this->ticket->qr_payload,
        'venue_id' => $this->venue->id,
        'event_id' => $otherEvent->id, // Wrong event!
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'status' => 'wrong_event',
        ]);
});
