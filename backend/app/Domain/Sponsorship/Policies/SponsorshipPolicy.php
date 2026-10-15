<?php

namespace App\Domain\Sponsorship\Policies;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Models\User;

class SponsorshipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sponsorship $sponsorship): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if ($sponsorship->applicant_user_id === $user->id || $sponsorship->sponsor_id === $user->id) {
            return true;
        }

        if ($sponsorship->brandProfile && $sponsorship->brandProfile->user_id === $user->id) {
            return true;
        }

        if ($sponsorship->package && $sponsorship->package->creator_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SPONSOR->value)
            || $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value)
            || $user->brandProfiles()->exists();
    }

    public function review(User $user, Sponsorship $sponsorship): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        // Package creator or target owner
        if ($sponsorship->package && $sponsorship->package->creator_id === $user->id) {
            return true;
        }

        return false;
    }

    public function update(User $user, Sponsorship $sponsorship): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $sponsorship->applicant_user_id === $user->id
            || ($sponsorship->package && $sponsorship->package->creator_id === $user->id);
    }

    public function exportReport(User $user, Sponsorship $sponsorship): bool
    {
        return $this->view($user, $sponsorship);
    }
}
