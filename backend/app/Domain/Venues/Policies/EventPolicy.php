<?php

namespace App\Domain\Venues\Policies;

use App\Domain\Venues\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['organizer', 'super-admin']) || $user->can('create-tournaments');
    }

    public function update(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id
            || ($event->venue && $event->venue->isStaff($user, 'manager'))
            || $user->hasRole('super-admin');
    }

    public function delete(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id || $user->hasRole('super-admin');
    }
}
