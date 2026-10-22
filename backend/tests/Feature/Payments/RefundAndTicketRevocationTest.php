<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Domain\Ticketing\Actions\IssueTicketsAction;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketOrderItem;
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
        'name' => 'Apex Arena Mekelle',
        'slug' => 'apex-arena-mekelle',
        'city' => 'Mekelle',
        'address' => 'Castle Area',
        'capacity' => 80,
        'verified' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'Tigray Regional Esports Championship',
        'slug' => 'tigray-regional-esports-championship',
        'start_time' => now()->addDays(4),
        'end_time' => now()->addDays(4)->addHours(6),
        'capacity' => 60,
        'status' => 'published',
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'Player VIP',
        'category' => 'vip',
        'price' => 200.00,
        'currency' => 'ETB',
        'quantity_total' => 20,
        'quantity_sold' => 0,
        'min_per_order' => 1,
        'max_per_order' => 5,
        'is_active' => true,
    ]);

    $this->buyer = User::factory()->create();

    $this->order = TicketOrder::create([
        'order_number' => 'ORD-REF-1234',
        'event_id' => $this->event->id,
        'user_id' => $this->buyer->id,
        'subtotal' => 200.00,
        'total_amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    TicketOrderItem::create([
        'ticket_order_id' => $this->order->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 200.00,
        'quantity' => 1,
        'subtotal' => 200.00,
    ]);

    Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-PAID-999',
        'amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    $this->ticket = app(IssueTicketsAction::class)->execute($this->order)->first();
});

test('attendee can submit refund request and organizer approval revokes ticket', function () {
    // 1. Buyer requests refund
    $requestResponse = $this->actingAs($this->buyer, 'sanctum')->postJson("/api/v1/payments/orders/{$this->order->id}/refund", [
        'amount' => 200.00,
        'reason' => 'Schedule conflict with university exam.',
        'reason_category' => 'attendee_unavailable',
    ]);

    $requestResponse->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Refund request submitted for organizer review.',
        ]);

    $refund = Refund::where('ticket_order_id', $this->order->id)->first();
    expect($refund->status)->toBe('pending');

    // 2. Organizer approves refund
    $processResponse = $this->actingAs($this->organizer, 'sanctum')->postJson("/api/v1/payments/refunds/{$refund->id}/process", [
        'approve' => true,
    ]);

    $processResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Refund approved and tickets revoked.',
        ]);

    $refund->refresh();
    expect($refund->status)->toBe('approved');

    $this->ticket->refresh();
    expect($this->ticket->status)->toBe('refunded');

    $this->order->refresh();
    expect($this->order->status)->toBe('refunded');
});

test('approval does not revoke tickets when the provider refuses the refund', function () {
    $this->actingAs($this->buyer, 'sanctum')->postJson("/api/v1/payments/orders/{$this->order->id}/refund", [
        'amount' => 200.00,
        'reason' => 'Provider settlement must be confirmed first.',
        'reason_category' => 'attendee_unavailable',
    ])->assertCreated();

    $refund = Refund::where('ticket_order_id', $this->order->id)->first();
    $ticketStatus = $this->ticket->status;

    config(['services.payments.sandbox' => false]);

    $this->actingAs($this->organizer, 'sanctum')
        ->postJson("/api/v1/payments/refunds/{$refund->id}/process", [
            'approve' => true,
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($refund->fresh()->status)->toBe('pending')
        ->and($this->ticket->fresh()->status)->toBe($ticketStatus)
        ->and($this->order->fresh()->status)->toBe('paid');
});

test('gate scanner rejects revoked ticket with 422 status', function () {
    $this->ticket->update(['status' => 'revoked']);

    $response = $this->actingAs($this->organizer, 'sanctum')->postJson('/api/v1/tickets/scan', [
        'qr_payload' => $this->ticket->qr_payload,
        'venue_id' => $this->venue->id,
        'event_id' => $this->event->id,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'status' => 'revoked',
        ]);
});

test('organizer payout calculates transparent 5 percent platform fee', function () {
    $response = $this->actingAs($this->organizer, 'sanctum')->postJson('/api/v1/payments/payouts', [
        'gross_amount' => 1000.00,
        'provider' => 'cbe',
        'account_number' => '1000123456789',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Payout request submitted with transparent platform fee deduction.',
        ])
        ->assertJsonPath('data.gross_amount', 1000)
        ->assertJsonPath('data.platform_fee', 50) // 5% of 1000
        ->assertJsonPath('data.amount', 950);
});
