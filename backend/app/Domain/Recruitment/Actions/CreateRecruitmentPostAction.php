<?php

namespace App\Domain\Recruitment\Actions;

use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Domain\Teams\Models\TeamMember;
use App\Models\User;
use InvalidArgumentException;

class CreateRecruitmentPostAction
{
    public function execute(User $user, array $data): RecruitmentPost
    {
        if ($user->isBanned() || $user->isSuspended() || $user->isMuted()) {
            throw new InvalidArgumentException('Suspended or banned users cannot post recruitment notices.');
        }

        $type = $data['type'] ?? 'lft'; // lft = looking for team, lfp = looking for players
        $teamId = $data['team_id'] ?? null;

        if ($type === 'lfp') {
            if (! $teamId) {
                throw new InvalidArgumentException('Looking for players notices must specify a team.');
            }

            // Must be captain, manager, or owner of the team
            $isAuthorized = TeamMember::where('team_id', $teamId)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'captain', 'manager'])
                ->where('status', 'active')
                ->exists();

            if (! $isAuthorized) {
                throw new InvalidArgumentException('Only team leaders can post team recruitment listings.');
            }
        }

        return RecruitmentPost::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'game_id' => $data['game_id'],
            'type' => $type,
            'title' => $data['title'],
            'description' => $data['description'],
            'rank_tier' => $data['rank_tier'] ?? null,
            'role' => $data['role'] ?? null,
            'location' => $data['location'] ?? $user->profile?->location,
            'university' => $data['university'] ?? $user->profile?->university,
            'age_group' => $data['age_group'] ?? null,
            'availability' => $data['availability'] ?? null,
            'platform' => $data['platform'] ?? null,
            'status' => 'open',
        ]);
    }
}
