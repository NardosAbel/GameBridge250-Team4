<?php

namespace App\Domain\Education\Policies;

use App\Domain\Education\Models\LearningPath;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;

class LearningPathPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, LearningPath $learningPath): bool
    {
        if ($learningPath->is_published) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::SCHOOL_ADMIN->value)
            || $learningPath->created_by_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::SCHOOL_ADMIN->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value);
    }

    public function assign(User $user, LearningPath $learningPath): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::SCHOOL_ADMIN->value);
    }
}
