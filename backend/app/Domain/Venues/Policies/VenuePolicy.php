<?php

namespace App\Domain\Venues\Policies;

use App\Domain\Venues\Models\Venue;
use App\Models\User;

class VenuePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Venue $venue): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['organizer', 'super_admin']) || $user->can('manage-venues');
    }

    public function update(User $user, Venue $venue): bool
    {
        return $venue->isStaff($user, 'manager') || $user->hasRole('super_admin');
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $venue->owner_id === $user->id || $user->hasRole('super_admin');
    }

    public function manageStaff(User $user, Venue $venue): bool
    {
        return $venue->isStaff($user, 'manager') || $user->hasRole('super_admin');
    }

    public function viewAnalytics(User $user, Venue $venue): bool
    {
        return $venue->isStaff($user, 'manager') || $user->hasRole('super_admin');
    }
}
