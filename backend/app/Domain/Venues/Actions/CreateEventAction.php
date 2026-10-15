<?php

namespace App\Domain\Venues\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;

class CreateEventAction
{
    public function execute(User $organizer, array $data): Event
    {
        $venue = null;
        if (! empty($data['venue_id'])) {
            $venue = Venue::findOrFail($data['venue_id']);
        }

        $event = Event::create([
            'venue_id' => $venue?->id,
            'organizer_id' => $organizer->id,
            'tournament_id' => $data['tournament_id'] ?? null,
            'title' => $data['title'],
            'banner_url' => $data['banner_url'] ?? null,
            'description' => $data['description'] ?? null,
            'event_type' => $data['event_type'] ?? 'tournament',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'recurring_pattern' => $data['recurring_pattern'] ?? null,
            'capacity' => $data['capacity'] ?? ($venue?->capacity ?? 100),
            'status' => $data['status'] ?? 'published',
        ]);

        AuditLogger::log(
            action: 'event.created',
            user: $organizer,
            entityType: 'event',
            entityId: $event->id,
            metadata: [
                'title' => $event->title,
                'venue_id' => $venue?->id,
                'event_type' => $event->event_type,
            ]
        );

        return $event;
    }
}
