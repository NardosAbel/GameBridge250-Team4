<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentWebhook;
use App\Domain\Ticketing\Models\Ticket;
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
        'name' => 'Adama LAN Center',
        'slug' => 'adama-lan-center',
        'city' => 'Adama',
        'address' => 'Main Campus Road',
        'capacity' => 60,
        'is_verified' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'Adama Esports Championship',
        'slug' => 'adama-esports-championship',
        'event_type' => 'lan_tournament',
        'start_time' => now()->addDays(5),
        'end_time' => now()->addDays(5)->addHours(6),
        'capacity' => 50,
        'status' => 'published',
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'General Admission',
        'category' => 'general_admission',
        'price' => 100.00,
        'currency' => 'ETB',
        'quantity_total' => 20,
        'quantity_sold' => 0,
        'min_per_order' => 1,
        'max_per_order' => 2,
        'is_active' => true,
    ]);

    $this->buyer = User::factory()->create();

    $this->order = TicketOrder::create([
        'order_number' => 'ORD-TEST-12345678',
        'user_id' => $this->buyer->id,
        'subtotal' => 200.00,
        'discount_amount' => 0.00,
        'service_fee' => 0.00,
        'total_amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'pending',
    ]);

    TicketOrderItem::create([
        'ticket_order_id' => $this->order->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 100.00,
        'quantity' => 2,
        'subtotal' => 200.00,
    ]);
});

test('buyer can initiate payment with telebirr gateway', function () {
    $response = $this->actingAs($this->buyer, 'sanctum')->postJson('/api/v1/payments/initiate', [
        'order_id' => $this->order->id,
        'provider' => 'telebirr',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Payment initiated.',
        ])
        ->assertJsonPath('data.provider', 'telebirr')
        ->assertJsonPath('data.status', 'pending');

    expect(Payment::where('ticket_order_id', $this->order->id)->exists())->toBeTrue();
});

test('payment webhook successfully verifies, completes order and auto-issues signed tickets', function () {
    $payment = Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-REF-998877',
        'amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'pending',
    ]);

    // Send valid webhook
    $response = $this->postJson('/api/v1/payments/webhook/telebirr', [
        'reference' => 'TB-REF-998877',
        'status' => 'completed',
        'amount' => 200.00,
        'signature' => hash_hmac('sha256', 'TB-REF-998877', config('app.key')),
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'status' => 'processed',
        ]);

    $payment->refresh();
    expect($payment->status)->toBe('paid');

    $this->order->refresh();
    expect($this->order->status)->toBe('paid');

    // Tickets are auto-issued (2 tickets for order)
    $tickets = Ticket::where('ticket_order_id', $this->order->id)->get();
    expect($tickets)->toHaveCount(2)
        ->and($tickets[0]->status)->toBe('active')
        ->and($tickets[0]->qr_payload)->not->toBeNull()
        ->and($tickets[0]->qr_signature)->not->toBeNull();
});

test('duplicate webhook is acknowledged idempotently without double-issuing tickets', function () {
    $payment = Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-DUP-112233',
        'amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'pending',
    ]);

    $payload = [
        'reference' => 'TB-DUP-112233',
        'status' => 'completed',
        'amount' => 200.00,
        'signature' => hash_hmac('sha256', 'TB-DUP-112233', config('app.key')),
    ];

    // First webhook call
    $first = $this->postJson('/api/v1/payments/webhook/telebirr', $payload);
    $first->assertStatus(200)->assertJsonPath('status', 'processed');

    // Duplicate webhook call with exact same reference
    $second = $this->postJson('/api/v1/payments/webhook/telebirr', $payload);
    $second->assertStatus(200)->assertJsonPath('status', 'duplicate');

    // Verify tickets were only created once
    $tickets = Ticket::where('ticket_order_id', $this->order->id)->get();
    expect($tickets)->toHaveCount(2);

    // Duplicate webhook record is logged
    expect(PaymentWebhook::where('status', 'duplicate')->count())->toBe(1);
});

test('payment webhook fails when signature is invalid', function () {
    $payment = Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'chapa',
        'provider_reference' => 'CH-INV-445566',
        'amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'pending',
    ]);

    $response = $this->postJson('/api/v1/payments/webhook/chapa', [
        'reference' => 'CH-INV-445566',
        'status' => 'completed',
        'amount' => 200.00,
        'signature' => 'invalid-tampered-signature',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'status' => 'failed',
        ]);

    $payment->refresh();
    expect($payment->status)->toBe('pending');
});
