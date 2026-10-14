<?php

namespace App\Policies;

use App\Models\User;

class ModerationPolicy
{
    public function viewQueue(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moderator']);
    }

    public function takeAction(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moderator']);
    }

    public function report(User $user): bool
    {
        return $user->isActive();
    }
}
