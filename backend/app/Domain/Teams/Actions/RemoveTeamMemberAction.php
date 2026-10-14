<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Teams\Models\TeamMember;
use App\Domain\Teams\Models\TeamRosterHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RemoveTeamMemberAction
{
    public function execute(User $actor, TeamMember $member): void
    {
        if ($member->role === 'owner') {
            throw new InvalidArgumentException('Team owner cannot be removed. Transfer ownership first.');
        }

        DB::transaction(function () use ($actor, $member) {
            $isSelf = $actor->id === $member->user_id;
            $action = $isSelf ? 'left' : 'removed';

            TeamRosterHistory::create([
                'team_id' => $member->team_id,
                'user_id' => $member->user_id,
                'action' => $action,
                'role' => $member->role,
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);

            $member->delete();
        });
    }
}
