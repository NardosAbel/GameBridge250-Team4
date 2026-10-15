<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'map_link' => $this->map_link,
            'facilities' => $this->facilities ?? [],
            'opening_hours' => $this->opening_hours,
            'contact_details' => $this->contact_details,
            'rules' => $this->rules,
            'capacity' => $this->capacity,
            'hourly_rate' => $this->hourly_rate,
            'pricing_details' => $this->pricing_details,
            'currency' => $this->currency,
            'verified' => $this->verified,
            'is_featured' => $this->is_featured,
            'photos' => $this->photos ?? [],
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews()->count(),
            'distance_km' => $this->distance_km ?? null,
            'owner' => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ] : null,
            'active_events' => EventResource::collection($this->whenLoaded('events')),
        ];
    }
}
