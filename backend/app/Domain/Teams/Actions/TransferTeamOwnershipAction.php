<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Domain\Teams\Models\TeamRosterHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferTeamOwnershipAction
{
    public function execute(User $currentOwner, Team $team, User $newOwner): Team
    {
        if ($currentOwner->id === $newOwner->id) {
            throw new InvalidArgumentException('New owner must be different from current owner.');
        }

        return DB::transaction(function () use ($currentOwner, $team, $newOwner) {
            // Find current owner's membership
            $currentMember = TeamMember::where('team_id', $team->id)
                ->where('user_id', $currentOwner->id)
                ->firstOrFail();

            // Find new owner's membership
            $newOwnerMember = TeamMember::where('team_id', $team->id)
                ->where('user_id', $newOwner->id)
                ->first();

            if (! $newOwnerMember) {
                throw new InvalidArgumentException('New owner must be an existing member of the team.');
            }

            // Transfer ownership
            $team->leader_id = $newOwner->id;
            $team->save();

            $newOwnerMember->role = 'owner';
            $newOwnerMember->save();

            // Demote previous owner to captain
            $currentMember->role = 'captain';
            $currentMember->save();

            // Record roster history
            TeamRosterHistory::create([
                'team_id' => $team->id,
                'user_id' => $newOwner->id,
                'action' => 'transferred',
                'role' => 'owner',
                'actor_id' => $currentOwner->id,
                'created_at' => now(),
            ]);

            return $team->fresh(['leader', 'activeMembers.user.profile']);
        });
    }
}
