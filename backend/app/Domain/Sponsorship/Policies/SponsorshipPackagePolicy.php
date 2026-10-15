<?php

namespace App\Domain\Sponsorship\Policies;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Models\SponsorshipPackage;
use App\Models\User;

class SponsorshipPackagePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, SponsorshipPackage $package): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value)
            || $user->hasRole(RoleEnum::SPONSOR->value)
            || $user->hasRole(RoleEnum::CREATOR->value)
            || $user->hasRole(RoleEnum::TEAM_CAPTAIN->value)
            || $user->hasRole(RoleEnum::VENUE_ADMIN->value);
    }

    public function update(User $user, SponsorshipPackage $package): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $package->creator_id === $user->id;
    }

    public function delete(User $user, SponsorshipPackage $package): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $package->creator_id === $user->id;
    }
}
