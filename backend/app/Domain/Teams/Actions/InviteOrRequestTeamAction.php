<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamInvitation;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;
use InvalidArgumentException;

class InviteOrRequestTeamAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $actor, Team $team, User $targetUser, string $type = 'invitation', string $role = 'player', ?string $message = null): TeamInvitation
    {
        // Check if user is already an active member
        $isMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $targetUser->id)
            ->where('status', 'active')
            ->exists();

        if ($isMember) {
            throw new InvalidArgumentException('User is already an active member of this team.');
        }

        // Check if there is already a pending invitation/request
        $pending = TeamInvitation::where('team_id', $team->id)
            ->where('user_id', $targetUser->id)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            throw new InvalidArgumentException('A pending invitation or join request already exists.');
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'user_id' => $targetUser->id,
            'type' => $type,
            'role' => $role,
            'status' => 'pending',
            'invited_by_id' => $actor->id,
            'message' => $message,
        ]);

        if ($type === 'invitation') {
            $this->notificationService->send(
                $targetUser,
                new GenericNotification(
                    title: 'Team Invitation',
                    message: "You have been invited to join {$team->name} as {$role}.",
                    type: 'team',
                    actionUrl: "/teams/{$team->id}"
                ),
                eventType: 'team_invitations'
            );
        } else {
            // Join request: notify team leader
            if ($team->leader) {
                $this->notificationService->send(
                    $team->leader,
                    new GenericNotification(
                        title: 'Team Join Request',
                        message: "{$targetUser->name} requested to join {$team->name}.",
                        type: 'team',
                        actionUrl: "/teams/{$team->id}"
                    ),
                    eventType: 'team_invitations'
                );
            }
        }

        return $invitation->load(['team', 'user.profile', 'invitedBy']);
    }
}
