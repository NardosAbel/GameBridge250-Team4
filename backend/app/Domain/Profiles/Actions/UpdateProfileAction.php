<?php

namespace App\Domain\Profiles\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProfileAction
{
    /**
     * Update or create the profile for a given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): Profile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = $user->profile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gamer_tag' => $data['gamer_tag'] ?? 'Gamer_'.substr($user->uuid, 0, 6),
                    'is_public' => true,
                ]
            );

            $oldValues = $profile->only([
                'gamer_tag', 'bio', 'university', 'school_id_number', 'is_public',
            ]);

            $profile->update(array_filter([
                'gamer_tag' => $data['gamer_tag'] ?? null,
                'handle' => $data['handle'] ?? ($data['gamer_tag'] ?? null),
                'bio' => $data['bio'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'cover_url' => $data['cover_url'] ?? ($data['banner_url'] ?? null),
                'banner_url' => $data['banner_url'] ?? ($data['cover_url'] ?? null),
                'location' => $data['location'] ?? null,
                'university' => $data['university'] ?? null,
                'school_id_number' => $data['school_id_number'] ?? null,
                'platform_handles' => $data['platform_handles'] ?? null,
                'preferred_games' => $data['preferred_games'] ?? null,
                'roles' => $data['roles'] ?? null,
                'platforms' => $data['platforms'] ?? null,
                'availability' => $data['availability'] ?? null,
                'privacy_settings' => $data['privacy_settings'] ?? null,
                'achievements' => $data['achievements'] ?? null,
                'badges' => $data['badges'] ?? null,
                'is_public' => isset($data['is_public']) ? (bool) $data['is_public'] : null,
            ], fn ($val) => ! is_null($val)));

            if (isset($data['locale']) && in_array($data['locale'], ['en', 'am'], true)) {
                $user->update(['locale' => $data['locale']]);
            }

            AuditLogger::log(
                action: 'profile_updated',
                user: $user,
                entityType: Profile::class,
                entityId: $profile->id,
                oldValues: $oldValues,
                newValues: $profile->only(['gamer_tag', 'bio', 'university', 'school_id_number', 'is_public'])
            );

            return $profile;
        });
    }
}
