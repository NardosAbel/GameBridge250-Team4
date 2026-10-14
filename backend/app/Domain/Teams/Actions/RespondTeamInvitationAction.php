<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Teams\Models\TeamInvitation;
use App\Domain\Teams\Models\TeamMember;
use App\Domain\Teams\Models\TeamRosterHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RespondTeamInvitationAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $actor, TeamInvitation $invitation, string $response): TeamInvitation
    {
        if ($invitation->status !== 'pending') {
            throw new InvalidArgumentException('This invitation or request has already been resolved.');
        }

        if (! in_array($response, ['accepted', 'declined'], true)) {
            throw new InvalidArgumentException("Invalid response: {$response}");
        }

        return DB::transaction(function () use ($actor, $invitation, $response) {
            $invitation->status = $response;
            $invitation->save();

            if ($response === 'accepted') {
                // Upsert TeamMember
                TeamMember::updateOrCreate(
                    [
                        'team_id' => $invitation->team_id,
                        'user_id' => $invitation->user_id,
                    ],
                    [
                        'role' => $invitation->role,
                        'status' => 'active',
                        'joined_at' => now(),
                    ]
                );

                // Add roster history
                TeamRosterHistory::create([
                    'team_id' => $invitation->team_id,
                    'user_id' => $invitation->user_id,
                    'action' => 'joined',
                    'role' => $invitation->role,
                    'actor_id' => $actor->id,
                    'created_at' => now(),
                ]);

                // Notify user or team leader
                $targetNotifyUser = $invitation->type === 'invitation' ? $invitation->invitedBy : $invitation->user;
                if ($targetNotifyUser) {
                    $this->notificationService->send(
                        $targetNotifyUser,
                        new GenericNotification(
                            title: 'Team Update',
                            message: "Team application/invitation for {$invitation->team?->name} was accepted.",
                            type: 'team',
                            actionUrl: "/teams/{$invitation->team_id}"
                        ),
                        eventType: 'team_invitations'
                    );
                }
            }

            return $invitation->fresh(['team', 'user']);
        });
    }
}
