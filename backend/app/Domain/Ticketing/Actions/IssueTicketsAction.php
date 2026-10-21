<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Services\QrTokenService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IssueTicketsAction
{
    public function __construct(
        protected QrTokenService $qrTokenService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Issue active tickets with signed QR tokens upon successful payment or approval.
     *
     * @return Collection<int, Ticket>
     */
    public function execute(TicketOrder $order): Collection
    {
        return DB::transaction(function () use ($order) {
            $order = TicketOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $existing = Ticket::where('ticket_order_id', $order->id)->lockForUpdate()->get();
            if ($existing->isNotEmpty()) {
                return $existing;
            }

            $order->loadMissing(['items.ticketType', 'user', 'event']);

            try {
                $createdTickets = $this->createTickets($order);
            } catch (UniqueConstraintViolationException) {
                return Ticket::where('ticket_order_id', $order->id)->orderBy('id')->get();
            }

            AuditLogger::log(
                action: 'tickets.issued',
                user: $order->user,
                entityType: 'ticket_order',
                entityId: $order->id,
                metadata: [
                    'tickets_count' => $createdTickets->count(),
                ]
            );

            if ($order->user) {
                $this->notificationService->send(
                    $order->user,
                    new GenericNotification(
                        title: 'Tickets Ready!',
                        message: "Your {$createdTickets->count()} ticket(s) for order #{$order->uuid} are confirmed and ready for entry.",
                        extraData: ['order_id' => $order->id, 'ticket_count' => $createdTickets->count()]
                    )
                );
            }

            return $createdTickets;
        });
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function createTickets(TicketOrder $order): Collection
    {
        $createdTickets = collect();

        foreach ($order->items as $item) {
            $type = $item->ticketType;

            for ($i = 0; $i < $item->quantity; $i++) {
                $ticket = Ticket::create([
                    'ticket_order_id' => $order->id,
                    'issuance_slot' => $order->id.':'.$item->id.':'.$i,
                    'ticket_type_id' => $type->id,
                    'event_id' => $order->event_id,
                    'tournament_id' => $order->tournament_id,
                    'venue_id' => $order->event?->venue_id,
                    'user_id' => $order->user_id,
                    'status' => 'active',
                    'expires_at' => $order->event?->end_time,
                ]);

                $this->qrTokenService->generatePayload($ticket);
                $createdTickets->push($ticket);
            }
        }

        return $createdTickets;
    }
}
