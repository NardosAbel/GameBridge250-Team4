<?php

namespace App\Domain\Venues\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Venues\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateEventAction
{
    public function execute(User $actor, Event $event, array $data): Event
    {
        $canUpdate = $event->organizer_id === $actor->id
            || ($event->venue && $event->venue->isStaff($actor, 'manager'))
            || $actor->hasRole('super-admin');

        if (! $canUpdate) {
            throw new AuthorizationException('You are not authorized to update this event.');
        }

        $old = $event->only(['title', 'status', 'start_time', 'end_time', 'capacity']);

        $event->update(array_filter([
            'title' => $data['title'] ?? $event->title,
            'banner_url' => $data['banner_url'] ?? $event->banner_url,
            'description' => $data['description'] ?? $event->description,
            'event_type' => $data['event_type'] ?? $event->event_type,
            'start_time' => $data['start_time'] ?? $event->start_time,
            'end_time' => $data['end_time'] ?? $event->end_time,
            'recurring_pattern' => $data['recurring_pattern'] ?? $event->recurring_pattern,
            'capacity' => $data['capacity'] ?? $event->capacity,
            'status' => $data['status'] ?? $event->status,
        ], fn ($val) => $val !== null));

        AuditLogger::log(
            action: 'event.updated',
            user: $actor,
            entityType: 'event',
            entityId: $event->id,
            oldValues: $old,
            newValues: $event->only(array_keys($old))
        );

        return $event;
    }
}
