<?php

namespace App\Domain\Education\Policies;

use App\Domain\Education\Models\Course;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        if ($course->is_published) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) || $user->hasRole(RoleEnum::MODERATOR->value)) {
            return true;
        }

        return $course->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::ORGANIZER->value)
            || $user->hasRole(RoleEnum::SCHOOL_ADMIN->value);
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $course->instructor_id === $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $course->instructor_id === $user->id;
    }

    public function enroll(User $user, Course $course): bool
    {
        return $course->is_published;
    }
}
