<?php

namespace App\Domain\Venues\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Models\VenueStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateVenueAction
{
    public function execute(User $owner, array $data): Venue
    {
        return DB::transaction(function () use ($owner, $data) {
            $venue = Venue::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $owner->id,
                'address' => $data['address'],
                'city' => $data['city'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'map_link' => $data['map_link'] ?? null,
                'facilities' => $data['facilities'] ?? [],
                'opening_hours' => $data['opening_hours'] ?? null,
                'contact_details' => $data['contact_details'] ?? null,
                'rules' => $data['rules'] ?? null,
                'capacity' => $data['capacity'] ?? 20,
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'pricing_details' => $data['pricing_details'] ?? null,
                'currency' => $data['currency'] ?? 'ETB',
                'photos' => $data['photos'] ?? [],
                'verified' => $owner->hasRole('super-admin'), // Auto-verify if created by admin
            ]);

            // Assign owner as Manager staff
            VenueStaff::create([
                'venue_id' => $venue->id,
                'user_id' => $owner->id,
                'role' => 'manager',
            ]);

            AuditLogger::log(
                action: 'venue.created',
                user: $owner,
                entityType: 'venue',
                entityId: $venue->id,
                metadata: [
                    'name' => $venue->name,
                    'city' => $venue->city,
                ]
            );

            return $venue;
        });
    }
}
