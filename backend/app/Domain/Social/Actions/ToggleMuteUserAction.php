<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\UserMute;
use App\Models\User;
use InvalidArgumentException;

class ToggleMuteUserAction
{
    public function execute(User $user, User $targetUser): array
    {
        if ($user->id === $targetUser->id) {
            throw new InvalidArgumentException('You cannot mute yourself.');
        }

        $existing = UserMute::where('user_id', $user->id)
            ->where('muted_user_id', $targetUser->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return ['status' => 'unmuted', 'is_muted' => false];
        }

        UserMute::create([
            'user_id' => $user->id,
            'muted_user_id' => $targetUser->id,
        ]);

        return ['status' => 'muted', 'is_muted' => true];
    }
}
