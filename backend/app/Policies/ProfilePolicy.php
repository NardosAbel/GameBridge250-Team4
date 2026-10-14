<?php

namespace App\Policies;

use App\Domain\Profiles\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    public function view(?User $user, Profile $profile): bool
    {
        return true;
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->hasRole('admin');
    }
}
