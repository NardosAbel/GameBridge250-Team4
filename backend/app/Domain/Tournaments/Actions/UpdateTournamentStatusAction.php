<?php

namespace App\Domain\Tournaments\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Tournaments\Models\Tournament;
use App\Models\User;
use InvalidArgumentException;

class UpdateTournamentStatusAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $actor, Tournament $tournament, string $newStatus): Tournament
    {
        $allowedTransitions = [
            'draft' => ['published', 'cancelled'],
            'published' => ['registration_open', 'cancelled', 'draft'],
            'registration_open' => ['registration_closed', 'check_in', 'cancelled'],
            'registration_closed' => ['check_in', 'live', 'cancelled'],
            'check_in' => ['live', 'cancelled'],
            'live' => ['completed', 'cancelled'],
            'completed' => ['archived'],
            'cancelled' => ['archived'],
            'archived' => [],
        ];

        $currentStatus = $tournament->status;

        // Allow super admin to override or transition directly
        if (! $actor->hasRole('super-admin') && ! in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            throw new InvalidArgumentException("Cannot transition tournament status from '{$currentStatus}' to '{$newStatus}'.");
        }

        // Additional checks:
        if ($newStatus === 'live' && $tournament->brackets()->count() === 0) {
            throw new InvalidArgumentException('Cannot start tournament without generating brackets first.');
        }

        $old = ['status' => $currentStatus];
        $tournament->update(['status' => $newStatus]);

        AuditLogger::log(
            action: 'tournament.status_updated',
            user: $actor,
            entityType: 'tournament',
            entityId: $tournament->id,
            oldValues: $old,
            newValues: ['status' => $newStatus]
        );

        // Broadcast notifications to all registered participants
        if (in_array($newStatus, ['registration_open', 'check_in', 'live', 'completed', 'cancelled'], true)) {
            $participants = $tournament->approvedRegistrations()->with('user')->get();
            foreach ($participants as $reg) {
                if ($reg->user) {
                    $this->notificationService->send(
                        $reg->user,
                        new GenericNotification(
                            title: "Tournament: {$tournament->title}",
                            message: 'The tournament status has been updated to: '.str_replace('_', ' ', $newStatus),
                            extraData: ['tournament_id' => $tournament->id, 'status' => $newStatus]
                        )
                    );
                }
            }
        }

        return $tournament;
    }
}
