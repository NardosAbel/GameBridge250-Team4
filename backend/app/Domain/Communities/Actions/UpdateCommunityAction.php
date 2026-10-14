<?php

namespace App\Domain\Communities\Actions;

use App\Domain\Communities\Models\Community;
use Illuminate\Support\Str;

class UpdateCommunityAction
{
    public function execute(Community $community, array $data): Community
    {
        $updateData = [];

        foreach (['name', 'description', 'type', 'visibility', 'avatar_url', 'banner_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($updateData['name']) && $updateData['name'] !== $community->name) {
            $updateData['slug'] = Str::slug($updateData['name']);
        }

        $community->update($updateData);

        return $community->fresh(['creator', 'activeMembers.user.profile']);
    }
}
