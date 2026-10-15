<?php

namespace App\Domain\Sponsorship\Policies;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Models\BrandProfile;
use App\Models\User;

class BrandProfilePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, BrandProfile $profile): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SPONSOR->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value)
            || $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function update(User $user, BrandProfile $profile): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $profile->user_id === $user->id;
    }
}
