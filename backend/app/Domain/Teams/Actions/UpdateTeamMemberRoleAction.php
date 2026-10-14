<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Teams\Models\TeamMember;
use App\Domain\Teams\Models\TeamRosterHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateTeamMemberRoleAction
{
    public function execute(User $actor, TeamMember $member, string $newRole): TeamMember
    {
        $validRoles = ['owner', 'captain', 'coach', 'manager', 'player', 'substitute'];
        if (! in_array($newRole, $validRoles, true)) {
            throw new InvalidArgumentException("Invalid team role: {$newRole}");
        }

        if ($member->role === $newRole) {
            return $member;
        }

        return DB::transaction(function () use ($actor, $member, $newRole) {
            $oldRole = $member->role;
            $member->update(['role' => $newRole]);

            $action = in_array($newRole, ['owner', 'captain', 'coach', 'manager'], true) ? 'promoted' : 'demoted';

            TeamRosterHistory::create([
                'team_id' => $member->team_id,
                'user_id' => $member->user_id,
                'action' => $action,
                'role' => $newRole,
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);

            return $member->fresh('user.profile');
        });
    }
}
