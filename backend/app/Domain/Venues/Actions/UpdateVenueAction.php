<?php

namespace App\Domain\Venues\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateVenueAction
{
    public function execute(User $actor, Venue $venue, array $data): Venue
    {
        if (! $venue->isStaff($actor, 'manager')) {
            throw new AuthorizationException('Only venue owners or managers can update venue settings.');
        }

        $old = $venue->only([
            'name', 'address', 'city', 'capacity', 'hourly_rate', 'verified',
        ]);

        $venue->update(array_filter([
            'name' => $data['name'] ?? $venue->name,
            'description' => $data['description'] ?? $venue->description,
            'address' => $data['address'] ?? $venue->address,
            'city' => $data['city'] ?? $venue->city,
            'latitude' => $data['latitude'] ?? $venue->latitude,
            'longitude' => $data['longitude'] ?? $venue->longitude,
            'map_link' => $data['map_link'] ?? $venue->map_link,
            'facilities' => $data['facilities'] ?? $venue->facilities,
            'opening_hours' => $data['opening_hours'] ?? $venue->opening_hours,
            'contact_details' => $data['contact_details'] ?? $venue->contact_details,
            'rules' => $data['rules'] ?? $venue->rules,
            'capacity' => $data['capacity'] ?? $venue->capacity,
            'hourly_rate' => $data['hourly_rate'] ?? $venue->hourly_rate,
            'pricing_details' => $data['pricing_details'] ?? $venue->pricing_details,
            'currency' => $data['currency'] ?? $venue->currency,
            'photos' => $data['photos'] ?? $venue->photos,
            'is_featured' => isset($data['is_featured']) && $actor->hasRole('super_admin')
                ? $data['is_featured']
                : $venue->is_featured,
            'verified' => isset($data['verified']) && $actor->hasRole('super_admin')
                ? $data['verified']
                : $venue->verified,
        ], fn ($val) => $val !== null));

        AuditLogger::log(
            action: 'venue.updated',
            user: $actor,
            entityType: 'venue',
            entityId: $venue->id,
            oldValues: $old,
            newValues: $venue->only(array_keys($old))
        );

        return $venue;
    }
}
