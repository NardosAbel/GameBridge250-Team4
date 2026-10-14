<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\TeamMember;
use App\Domain\Teams\Models\TeamRosterHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateTeamAction
{
    public function execute(User $user, array $data): Team
    {
        if ($user->isBanned() || $user->isSuspended()) {
            throw new InvalidArgumentException('Suspended or banned users cannot create teams.');
        }

        return DB::transaction(function () use ($user, $data) {
            $team = Team::create([
                'name' => $data['name'],
                'tag' => strtoupper($data['tag']),
                'bio' => $data['bio'] ?? null,
                'logo_url' => $data['logo_url'] ?? null,
                'banner_url' => $data['banner_url'] ?? null,
                'leader_id' => $user->id,
                'game_id' => $data['game_id'] ?? null,
                'university' => $data['university'] ?? $user->profile?->university,
                'location' => $data['location'] ?? $user->profile?->location,
                'recruitment_status' => $data['recruitment_status'] ?? 'open',
                'achievements' => $data['achievements'] ?? [],
                'is_active' => true,
            ]);

            // Add creator as owner
            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Log roster history
            TeamRosterHistory::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'action' => 'joined',
                'role' => 'owner',
                'actor_id' => $user->id,
                'created_at' => now(),
            ]);

            return $team->load(['leader', 'activeMembers.user.profile']);
        });
    }
}
