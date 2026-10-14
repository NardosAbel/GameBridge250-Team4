<?php

namespace App\Domain\Communities\Actions;

use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;
use InvalidArgumentException;

class ApproveCommunityMemberAction
{
    public function execute(User $actor, CommunityMember $member, bool $approved): CommunityMember
    {
        if ($member->status !== 'pending') {
            throw new InvalidArgumentException('Member is not pending approval.');
        }

        if ($approved) {
            $member->update([
                'status' => 'active',
                'joined_at' => now(),
            ]);
        } else {
            $member->delete();
        }

        return $member;
    }
}
