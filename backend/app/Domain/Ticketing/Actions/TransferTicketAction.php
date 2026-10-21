<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Services\QrTokenService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferTicketAction
{
    public function __construct(
        protected QrTokenService $qrTokenService,
        protected NotificationService $notificationService
    ) {}

    public function execute(User $currentUser, Ticket $ticket, User $recipient): Ticket
    {
        if ($ticket->user_id !== $currentUser->id && ! $currentUser->hasRole('super_admin')) {
            throw new AuthorizationException('You can only transfer tickets you own.');
        }

        if ($ticket->status !== 'active') {
            throw new InvalidArgumentException("Ticket cannot be transferred because its status is '{$ticket->status}'.");
        }

        if ($recipient->id === $currentUser->id) {
            throw new InvalidArgumentException('Cannot transfer a ticket to yourself.');
        }

        return DB::transaction(function () use ($currentUser, $ticket, $recipient) {
            // 1. Mark current ticket as transferred
            $history = $ticket->transfer_history ?? [];
            $history[] = [
                'from_user_id' => $currentUser->id,
                'to_user_id' => $recipient->id,
                'transferred_at' => now()->toIso8601String(),
            ];

            $ticket->update([
                'status' => 'transferred',
                'transferred_to_user_id' => $recipient->id,
                'transfer_history' => $history,
            ]);

            // 2. Issue fresh ticket with brand new unique code & signed QR token for the recipient
            $newTicket = Ticket::create([
                'ticket_order_id' => $ticket->ticket_order_id,
                'ticket_type_id' => $ticket->ticket_type_id,
                'event_id' => $ticket->event_id,
                'tournament_id' => $ticket->tournament_id,
                'venue_id' => $ticket->venue_id,
                'user_id' => $recipient->id,
                'status' => 'active',
                'expires_at' => $ticket->expires_at,
            ]);

            $this->qrTokenService->generatePayload($newTicket);

            AuditLogger::log(
                action: 'ticket.transferred',
                user: $currentUser,
                entityType: 'ticket',
                entityId: $ticket->id,
                metadata: [
                    'recipient_id' => $recipient->id,
                    'new_ticket_id' => $newTicket->id,
                ]
            );

            // Notify recipient
            $this->notificationService->send(
                $recipient,
                new GenericNotification(
                    title: 'Ticket Received!',
                    message: "{$currentUser->name} has transferred a ticket to you. Check your tickets to view your QR pass.",
                    extraData: ['ticket_id' => $newTicket->id]
                )
            );

            return $newTicket;
        });
    }
}
