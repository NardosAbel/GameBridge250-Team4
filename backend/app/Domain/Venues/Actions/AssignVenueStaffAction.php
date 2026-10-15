<?php

namespace App\Domain\Venues\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Models\VenueStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AssignVenueStaffAction
{
    public function execute(User $actor, Venue $venue, int $targetUserId, string $role = 'scanner'): VenueStaff
    {
        if (! $venue->isStaff($actor, 'manager')) {
            throw new AuthorizationException('Only venue managers or owners can assign venue staff.');
        }

        $staff = VenueStaff::updateOrCreate([
            'venue_id' => $venue->id,
            'user_id' => $targetUserId,
        ], [
            'role' => $role,
        ]);

        AuditLogger::log(
            action: 'venue.staff_assigned',
            user: $actor,
            entityType: 'venue',
            entityId: $venue->id,
            metadata: [
                'target_user_id' => $targetUserId,
                'role' => $role,
            ]
        );

        return $staff;
    }
}
