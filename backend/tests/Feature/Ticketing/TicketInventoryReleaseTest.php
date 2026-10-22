<?php

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Services\RefundService;
use App\Domain\Ticketing\Actions\ReleaseTicketInventoryAction;
use App\Domain\Ticketing\Models\PromoCode;
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
    $this->buyer = User::factory()->create();

    $this->venue = Venue::create([
        'owner_id' => $this->organizer->id,
        'name' => 'Release Arena',
        'slug' => 'release-arena',
        'city' => 'Addis Ababa',
        'address' => 'Bole',
        'capacity' => 80,
        'verified' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $this->venue->id,
        'organizer_id' => $this->organizer->id,
        'title' => 'Inventory Release Cup',
        'slug' => 'inventory-release-cup',
        'start_time' => now()->addDays(3),
        'end_time' => now()->addDays(3)->addHours(4),
        'capacity' => 40,
        'status' => 'published',
    ]);

    $this->ticketType = TicketType::create([
        'event_id' => $this->event->id,
        'name' => 'GA',
        'category' => 'general_admission',
        'price' => 100.00,
        'currency' => 'ETB',
        'quantity_total' => 20,
        'quantity_sold' => 2,
        'min_per_order' => 1,
        'max_per_order' => 5,
        'is_active' => true,
    ]);

    $this->promo = PromoCode::create([
        'event_id' => $this->event->id,
        'code' => 'RELEASE10',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'uses_count' => 1,
        'is_active' => true,
    ]);

    $this->order = TicketOrder::create([
        'user_id' => $this->buyer->id,
        'event_id' => $this->event->id,
        'subtotal' => 200.00,
        'discount_amount' => 10.00,
        'service_fee' => 0.00,
        'total_amount' => 190.00,
        'currency' => 'ETB',
        'status' => 'pending',
        'promo_code_id' => $this->promo->id,
        'notes' => 'Please hold seats [inventory_released] near the aisle',
    ]);

    TicketOrderItem::create([
        'ticket_order_id' => $this->order->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 100.00,
        'quantity' => 2,
        'subtotal' => 200.00,
    ]);
});

test('buyer notes containing the legacy release marker still release inventory once', function () {
    $action = app(ReleaseTicketInventoryAction::class);

    $action->execute($this->order);
    $action->execute($this->order->fresh());

    $this->ticketType->refresh();
    $this->order->refresh();
    $this->promo->refresh();

    expect($this->ticketType->quantity_sold)->toBe(0)
        ->and($this->promo->uses_count)->toBe(0)
        ->and($this->order->inventory_released_at)->not->toBeNull()
        ->and($this->order->notes)->toBe('Please hold seats [inventory_released] near the aisle');
});

test('a release that already committed its timestamp does not decrement again', function () {
    $this->order->update(['inventory_released_at' => now()]);

    app(ReleaseTicketInventoryAction::class)->execute($this->order->fresh());

    expect($this->ticketType->fresh()->quantity_sold)->toBe(2)
        ->and($this->promo->fresh()->uses_count)->toBe(1);
});

test('a failed payment webhook releases reserved inventory even when notes contain the marker', function () {
    Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-REL-FAIL-1',
        'amount' => 190.00,
        'currency' => 'ETB',
        'status' => 'pending',
    ]);

    $payload = paymentWebhookPayload('telebirr', [
        'reference' => 'TB-REL-FAIL-1',
        'status' => 'failed',
        'amount' => 190.00,
        'currency' => 'ETB',
    ]);

    $this->postJson('/api/v1/payments/webhook/telebirr', $payload)
        ->assertOk()
        ->assertJsonPath('status', 'processed');

    $this->postJson('/api/v1/payments/webhook/telebirr', $payload)
        ->assertOk()
        ->assertJsonPath('status', 'duplicate');

    expect($this->ticketType->fresh()->quantity_sold)->toBe(0)
        ->and($this->order->fresh()->status)->toBe('failed')
        ->and($this->order->fresh()->inventory_released_at)->not->toBeNull()
        ->and($this->promo->fresh()->uses_count)->toBe(0);
});

test('a successful payment does not release inventory', function () {
    Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-REL-PAID-1',
        'amount' => 190.00,
        'currency' => 'ETB',
        'status' => 'pending',
    ]);

    $this->postJson('/api/v1/payments/webhook/telebirr', paymentWebhookPayload('telebirr', [
        'reference' => 'TB-REL-PAID-1',
        'status' => 'completed',
        'amount' => 190.00,
        'currency' => 'ETB',
    ]))->assertOk();

    expect($this->ticketType->fresh()->quantity_sold)->toBe(2)
        ->and($this->order->fresh()->status)->toBe('completed')
        ->and($this->order->fresh()->inventory_released_at)->toBeNull();
});

test('a full refund releases inventory once and a repeated release does not', function () {
    $this->order->update(['status' => 'paid']);
    $this->ticketType->update(['quantity_sold' => 2]);

    Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-REL-REFUND-1',
        'amount' => 190.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    $refund = app(RefundService::class)->requestRefund(
        $this->order->fresh(),
        $this->buyer,
        190.00,
        'Event plans changed.',
    );

    app(RefundService::class)->processRefund($refund, $this->organizer, true);
    app(ReleaseTicketInventoryAction::class)->execute($this->order->fresh());

    expect($this->ticketType->fresh()->quantity_sold)->toBe(0)
        ->and($this->order->fresh()->status)->toBe('refunded')
        ->and($this->order->fresh()->inventory_released_at)->not->toBeNull()
        ->and($this->promo->fresh()->uses_count)->toBe(0);
});

test('a partial refund keeps the reserved inventory', function () {
    $this->order->update(['status' => 'paid']);

    Payment::create([
        'ticket_order_id' => $this->order->id,
        'user_id' => $this->buyer->id,
        'provider' => 'telebirr',
        'provider_reference' => 'TB-REL-PARTIAL-1',
        'amount' => 190.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    $refund = app(RefundService::class)->requestRefund(
        $this->order->fresh(),
        $this->buyer,
        50.00,
        'One seat was unused.',
    );

    app(RefundService::class)->processRefund($refund, $this->organizer, true);

    expect($this->ticketType->fresh()->quantity_sold)->toBe(2)
        ->and($this->order->fresh()->status)->toBe('partially_refunded')
        ->and($this->order->fresh()->inventory_released_at)->toBeNull()
        ->and($this->promo->fresh()->uses_count)->toBe(1);
});

test('cancelling an order still returns inventory when the buyer forged the old marker', function () {
    $this->order->update(['status' => 'cancelled']);

    $action = app(ReleaseTicketInventoryAction::class);
    $action->execute($this->order->fresh());
    $action->execute($this->order->fresh());

    expect($this->ticketType->fresh()->quantity_sold)->toBe(0)
        ->and($this->order->fresh()->inventory_released_at)->not->toBeNull()
        ->and($this->order->fresh()->notes)->toContain('[inventory_released]');
});
