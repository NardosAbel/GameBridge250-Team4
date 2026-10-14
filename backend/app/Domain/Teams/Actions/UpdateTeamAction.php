<?php

namespace App\Domain\Teams\Actions;

use App\Domain\Teams\Models\Team;
use Illuminate\Support\Str;

class UpdateTeamAction
{
    public function execute(Team $team, array $data): Team
    {
        $updateData = [];

        foreach (['name', 'tag', 'bio', 'logo_url', 'banner_url', 'game_id', 'university', 'location', 'recruitment_status', 'achievements', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($updateData['name']) && $updateData['name'] !== $team->name) {
            $updateData['slug'] = Str::slug($updateData['name']);
        }

        if (isset($updateData['tag'])) {
            $updateData['tag'] = strtoupper($updateData['tag']);
        }

        $team->update($updateData);

        return $team->fresh(['leader', 'activeMembers.user.profile', 'game']);
    }
}
