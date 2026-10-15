<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;

class TicketScanPolicy
{
    public function scan(User $user, ?Venue $venue = null, ?Event $event = null): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($venue && $venue->isStaff($user)) {
            return true;
        }

        if ($event && ($event->organizer_id === $user->id || ($event->venue && $event->venue->isStaff($user)))) {
            return true;
        }

        return $user->hasRole(['organizer', 'referee']);
    }
}
