<?php

namespace App\Domain\Administration\Policies;

use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;

class AdminDashboardPolicy
{
    public function viewPlatformAnalytics(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function manageFeatureFlags(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function viewSystemHealth(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function viewCreatorAnalytics(User $user): bool
    {
        return $user->hasRole(RoleEnum::CREATOR->value)
            || $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value);
    }
}
