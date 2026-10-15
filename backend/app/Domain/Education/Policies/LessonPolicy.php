<?php

namespace App\Domain\Education\Policies;

use App\Domain\Education\Models\Lesson;
use App\Domain\Identity\Enums\RoleEnum;
use App\Models\User;

class LessonPolicy
{
    public function view(?User $user, Lesson $lesson): bool
    {
        if ($lesson->is_preview) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value) || $user->hasRole(RoleEnum::MODERATOR->value)) {
            return true;
        }

        if ($lesson->course->instructor_id === $user->id) {
            return true;
        }

        // Must be enrolled in course
        return $lesson->course->enrollments()->where('user_id', $user->id)->exists();
    }

    public function complete(User $user, Lesson $lesson): bool
    {
        return $lesson->course->enrollments()->where('user_id', $user->id)->exists()
            || $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function downloadOffline(User $user, Lesson $lesson): bool
    {
        if (! $lesson->is_downloadable) {
            return false;
        }

        return $this->view($user, $lesson);
    }
}
