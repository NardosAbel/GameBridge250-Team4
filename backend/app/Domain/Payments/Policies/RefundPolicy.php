<?php

namespace App\Domain\Payments\Policies;

use App\Domain\Payments\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function view(User $user, Refund $refund): bool
    {
        return $refund->requested_by_id === $user->id
            || ($refund->order && $refund->order->event && $refund->order->event->organizer_id === $user->id)
            || $user->hasRole('super-admin');
    }

    public function process(User $user, Refund $refund): bool
    {
        return ($refund->order && $refund->order->event && $refund->order->event->organizer_id === $user->id)
            || ($refund->order && $refund->order->event && $refund->order->event->venue && $refund->order->event->venue->isStaff($user, 'manager'))
            || $user->hasRole('super-admin');
    }
}
