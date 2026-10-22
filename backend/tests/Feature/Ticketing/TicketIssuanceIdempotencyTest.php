<?php

use App\Domain\Ticketing\Actions\IssueTicketsAction;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketOrderItem;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->buyer = User::factory()->create();
    $organizer = User::factory()->create();

    $venue = Venue::create([
        'owner_id' => $organizer->id,
        'name' => 'Issuance Arena',
        'slug' => 'issuance-arena',
        'city' => 'Addis Ababa',
        'address' => 'Bole',
        'capacity' => 100,
        'verified' => true,
    ]);

    $this->event = Event::create([
        'venue_id' => $venue->id,
        'organizer_id' => $organizer->id,
        'title' => 'Issuance Cup',
        'slug' => 'issuance-cup',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(4),
        'capacity' => 50,
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

    $this->order = TicketOrder::create([
        'order_number' => 'ORD-ISSUE-1',
        'event_id' => $this->event->id,
        'user_id' => $this->buyer->id,
        'subtotal' => 200.00,
        'total_amount' => 200.00,
        'currency' => 'ETB',
        'status' => 'paid',
    ]);

    $this->item = TicketOrderItem::create([
        'ticket_order_id' => $this->order->id,
        'ticket_type_id' => $this->ticketType->id,
        'unit_price' => 100.00,
        'quantity' => 2,
        'subtotal' => 200.00,
    ]);
});

test('competing sequential issuance attempts return the same tickets without duplicates', function () {
    $action = app(IssueTicketsAction::class);

    $first = $action->execute($this->order);
    $second = $action->execute($this->order->fresh());

    expect($first)->toHaveCount(2)
        ->and($second)->toHaveCount(2)
        ->and(Ticket::where('ticket_order_id', $this->order->id)->count())->toBe(2)
        ->and($first->pluck('id')->sort()->values()->all())
        ->toBe($second->pluck('id')->sort()->values()->all());

    // Inventory was reserved at checkout (quantity_sold), not again at issuance.
    expect($this->ticketType->fresh()->quantity_sold)->toBe(2);
});

test('issuance_slot uniqueness is enforced at the database level', function () {
    $slot = $this->order->id.':'.$this->item->id.':0';

    Ticket::create([
        'ticket_order_id' => $this->order->id,
        'issuance_slot' => $slot,
        'ticket_type_id' => $this->ticketType->id,
        'event_id' => $this->event->id,
        'user_id' => $this->buyer->id,
        'status' => 'active',
    ]);

    // PostgreSQL aborts the wrapping RefreshDatabase transaction on a unique
    // violation unless the failing statement runs inside a SAVEPOINT.
    assertUniqueConstraintRejectsDuplicateIssuanceSlot(
        orderId: $this->order->id,
        slot: $slot,
        ticketTypeId: $this->ticketType->id,
        eventId: $this->event->id,
        userId: $this->buyer->id,
    );

    expect(Ticket::where('issuance_slot', $slot)->count())->toBe(1);
});

test('issuance recovers when a unique slot collision is raised inside the transaction', function () {
    // Pre-seed tickets with the slots IssueTicketsAction would create, simulating
    // another writer that won the unique race after the empty-check window.
    // The action's UniqueConstraintViolationException path must return those rows.
    $existing = collect();
    for ($i = 0; $i < 2; $i++) {
        $existing->push(Ticket::create([
            'ticket_order_id' => $this->order->id,
            'issuance_slot' => $this->order->id.':'.$this->item->id.':'.$i,
            'ticket_type_id' => $this->ticketType->id,
            'event_id' => $this->event->id,
            'user_id' => $this->buyer->id,
            'status' => 'active',
        ]));
    }

    $result = app(IssueTicketsAction::class)->execute($this->order->fresh());

    expect($result)->toHaveCount(2)
        ->and(Ticket::where('ticket_order_id', $this->order->id)->count())->toBe(2)
        ->and($result->pluck('id')->sort()->values()->all())
        ->toBe($existing->pluck('id')->sort()->values()->all());
});

test('issuance_slot uniqueness holds on the active database driver', function () {
    // Local phpunit.xml defaults to sqlite; CI overrides with PostgreSQL.
    // Both must enforce the unique issuance_slot invariant. True multi-connection
    // races are still not simulated here — lockForUpdate + the unique index cover that.
    expect(['sqlite', 'pgsql'])->toContain(DB::getDriverName());

    $slot = $this->order->id.':'.$this->item->id.':9';
    Ticket::create([
        'ticket_order_id' => $this->order->id,
        'issuance_slot' => $slot,
        'ticket_type_id' => $this->ticketType->id,
        'event_id' => $this->event->id,
        'user_id' => $this->buyer->id,
        'status' => 'active',
    ]);

    assertUniqueConstraintRejectsDuplicateIssuanceSlot(
        orderId: $this->order->id,
        slot: $slot,
        ticketTypeId: $this->ticketType->id,
        eventId: $this->event->id,
        userId: $this->buyer->id,
    );

    expect(Ticket::where('issuance_slot', $slot)->count())->toBe(1);
});

/**
 * Insert a duplicate issuance_slot and assert the database rejects it without
 * aborting the outer PHPUnit/RefreshDatabase transaction on PostgreSQL.
 */
function assertUniqueConstraintRejectsDuplicateIssuanceSlot(
    int $orderId,
    string $slot,
    int $ticketTypeId,
    int $eventId,
    int $userId,
): void {
    $connection = DB::connection();
    $usingSavepoint = $connection->getDriverName() === 'pgsql';

    if ($usingSavepoint) {
        $connection->statement('SAVEPOINT issuance_slot_unique_check');
    }

    try {
        Ticket::create([
            'ticket_order_id' => $orderId,
            'issuance_slot' => $slot,
            'ticket_type_id' => $ticketTypeId,
            'event_id' => $eventId,
            'user_id' => $userId,
            'status' => 'active',
        ]);

        if ($usingSavepoint) {
            $connection->statement('ROLLBACK TO SAVEPOINT issuance_slot_unique_check');
        }

        throw new RuntimeException('Expected UniqueConstraintViolationException was not thrown.');
    } catch (UniqueConstraintViolationException) {
        if ($usingSavepoint) {
            $connection->statement('ROLLBACK TO SAVEPOINT issuance_slot_unique_check');
        }
    }
}
