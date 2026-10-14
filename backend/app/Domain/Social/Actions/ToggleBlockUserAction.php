<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Follow;
use App\Domain\Social\Models\UserBlock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ToggleBlockUserAction
{
    public function execute(User $user, User $targetUser): array
    {
        if ($user->id === $targetUser->id) {
            throw new InvalidArgumentException('You cannot block yourself.');
        }

        return DB::transaction(function () use ($user, $targetUser) {
            $existing = UserBlock::where('user_id', $user->id)
                ->where('blocked_user_id', $targetUser->id)
                ->first();

            if ($existing) {
                $existing->delete();

                return ['status' => 'unblocked', 'is_blocked' => false];
            }

            // Create block
            UserBlock::create([
                'user_id' => $user->id,
                'blocked_user_id' => $targetUser->id,
            ]);

            // Sever follows in both directions
            Follow::where(function ($q) use ($user, $targetUser) {
                $q->where('follower_id', $user->id)->where('followable_type', User::class)->where('followable_id', $targetUser->id);
            })->orWhere(function ($q) use ($user, $targetUser) {
                $q->where('follower_id', $targetUser->id)->where('followable_type', User::class)->where('followable_id', $user->id);
            })->delete();

            return ['status' => 'blocked', 'is_blocked' => true];
        });
    }
}
