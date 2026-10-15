<?php

namespace App\Domain\Education\Policies;

use App\Domain\Education\Models\AwarenessCampaign;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;

class AwarenessCampaignPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AwarenessCampaign $campaign): bool
    {
        if ($campaign->status === 'published') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::MODERATOR->value)
            || $campaign->created_by_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value)
            || $user->hasRole(RoleEnum::SCHOOL_ADMIN->value)
            || $user->hasRole(RoleEnum::SPONSOR->value);
    }

    public function update(User $user, AwarenessCampaign $campaign): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) || $user->hasRole(RoleEnum::MODERATOR->value)) {
            return true;
        }

        return $campaign->created_by_id === $user->id;
    }

    public function delete(User $user, AwarenessCampaign $campaign): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $campaign->created_by_id === $user->id;
    }
}
