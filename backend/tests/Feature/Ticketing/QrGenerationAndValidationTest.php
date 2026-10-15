<?php

use App\Domain\Ticketing\Actions\IssueTicketsAction;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketOrderItem;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Services\QrTokenService;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create([
        'name' => 'Biruk Tsegaye',
        'email' => 'biruk@gmail.com',
        'phone' => '+251922334455',
    ]);

    $this->venue = Venue::create([
        'owner_id' => $this->user->id,
        'name' => 'Hawassa Esports Hub',
        'slug' => 'hawassa-esports-hub',
        'city' => 'Hawassa',
        'address' => 'Piazza',
        'capacity' => 50,
        'is_verified' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->user->id,
        'title' => 'Southern Universities LAN',
        'slug' => 'southern-universities-lan',
        'start_time' => now()->addDays(3),
        'end_time' => now()->addDays(3)->addHours(4),
        'capacity' => 40,
        'status' => 'published',
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'VIP Player Pass',
        'category' => 'vip',
        'price' => 250.00,
        'currency' => 'ETB',
        'quantity_total' => 10,
        'quantity_sold' => 0,
        'min_per_order' => 1,
        'max_per_order' => 2,
        'is_active' => true,
    ]);

    $this->order = TicketOrder::create([
        'order_number' => 'ORD-QR-112233',
        'user_id' => $this->user->id,
        'subtotal' => 250.00,
        'discount_amount' => 0.00,
        'service_fee' => 0.00,
        'total_amount' => 250.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    TicketOrderItem::create([
        'ticket_order_id' => $this->order->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 250.00,
        'quantity' => 1,
        'subtotal' => 250.00,
    ]);
});

test('qr payload contains ONLY ticket reference and signature and never personal or payment data', function () {
    $action = app(IssueTicketsAction::class);
    $tickets = $action->execute($this->order);

    expect($tickets)->toHaveCount(1);
    $ticket = $tickets->first();

    $payloadJson = $ticket->qr_payload;
    expect($payloadJson)->toBeJson();

    $decoded = json_decode($payloadJson, true);

    // Strict requirements: payload must have ONLY 'ref' and 'sig' keys
    expect(array_keys($decoded))->toBe(['ref', 'sig']);
    expect($decoded['ref'])->toBe($ticket->uuid);
    expect($decoded['sig'])->toBe($ticket->qr_signature);

    // Ensure NO personal, user or payment data exists anywhere in the QR string
    expect($payloadJson)->not->toContain('Biruk')
        ->and($payloadJson)->not->toContain('biruk@gmail.com')
        ->and($payloadJson)->not->toContain('251922334455')
        ->and($payloadJson)->not->toContain('price')
        ->and($payloadJson)->not->toContain('250');
});

test('qr token service correctly validates genuine ticket signature', function () {
    $service = app(QrTokenService::class);
    $token = $service->generateToken('TIC-SAMPLE-REF-1234');

    expect($service->verifyToken($token['payload'], $token['signature']))->toBeTrue();
    expect($service->extractReference($token['payload']))->toBe('TIC-SAMPLE-REF-1234');
});

test('qr token service rejects tampered signature or invalid payload', function () {
    $service = app(QrTokenService::class);
    $token = $service->generateToken('TIC-SAMPLE-REF-1234');

    // Tampered payload
    $tamperedPayload = json_encode(['ref' => 'TIC-HACKED-REF-9999', 'sig' => $token['signature']]);
    expect($service->verifyToken($tamperedPayload, $token['signature']))->toBeFalse();

    // Tampered signature
    expect($service->verifyToken($token['payload'], 'forged-invalid-sig'))->toBeFalse();
});
