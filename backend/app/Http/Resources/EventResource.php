<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'venue_id' => $this->venue_id,
            'organizer_id' => $this->organizer_id,
            'tournament_id' => $this->tournament_id,
            'title' => $this->title,
            'banner_url' => $this->banner_url,
            'description' => $this->description,
            'event_type' => $this->event_type,
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'recurring_pattern' => $this->recurring_pattern,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'venue' => $this->venue ? [
                'id' => $this->venue->id,
                'name' => $this->venue->name,
                'address' => $this->venue->address,
                'city' => $this->venue->city,
            ] : null,
            'organizer' => $this->organizer ? [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
            ] : null,
        ];
    }
}
