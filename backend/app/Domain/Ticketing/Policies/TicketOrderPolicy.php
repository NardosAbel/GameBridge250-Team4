<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Models\TicketOrder;
use App\Models\User;

class TicketOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TicketOrder $order): bool
    {
        return $order->user_id === $user->id
            || ($order->event && $order->event->organizer_id === $user->id)
            || $user->hasRole('super_admin');
    }

    public function requestRefund(User $user, TicketOrder $order): bool
    {
        return $order->user_id === $user->id || $user->hasRole('super_admin');
    }
}
