<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id
            || ($ticket->event && $ticket->event->organizer_id === $user->id)
            || ($ticket->venue && $ticket->venue->isStaff($user))
            || $user->hasRole('super-admin');
    }

    public function transfer(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id || $user->hasRole('super-admin');
    }

    public function revoke(User $user, Ticket $ticket): bool
    {
        return ($ticket->event && $ticket->event->organizer_id === $user->id)
            || ($ticket->venue && $ticket->venue->isStaff($user, 'manager'))
            || $user->hasRole('super-admin');
    }
}
