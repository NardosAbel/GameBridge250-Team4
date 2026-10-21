<?php

namespace App\Domain\Ticketing\Services;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Venues\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TicketReportAuthorizer
{
    public function authorize(User $user, ?int $eventId, ?int $tournamentId): void
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return;
        }

        if ($eventId === null && $tournamentId === null) {
            throw new AuthorizationException('Choose an event or tournament before viewing ticket reports.');
        }

        if ($eventId !== null && $this->canViewEvent($user, $eventId)) {
            return;
        }

        if ($tournamentId !== null && $this->canViewTournament($user, $tournamentId)) {
            return;
        }

        throw new AuthorizationException('You cannot view ticket reports for this event.');
    }

    private function canViewEvent(User $user, int $eventId): bool
    {
        $event = Event::query()->with('venue')->find($eventId);
        if (! $event) {
            return false;
        }

        if ($event->organizer_id === $user->id) {
            return true;
        }

        return $event->venue !== null && (
            $event->venue->owner_id === $user->id
            || $event->venue->isStaff($user, 'manager')
            || $event->venue->isStaff($user, 'scanner')
        );
    }

    private function canViewTournament(User $user, int $tournamentId): bool
    {
        $tournament = Tournament::query()->find($tournamentId);

        return $tournament !== null && $tournament->organizer_id === $user->id;
    }
}
