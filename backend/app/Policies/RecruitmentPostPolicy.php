<?php

namespace App\Policies;

use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Models\User;

class RecruitmentPostPolicy
{
    public function view(?User $user, RecruitmentPost $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, RecruitmentPost $post): bool
    {
        return $user->id === $post->user_id || $user->hasRole('admin');
    }

    public function delete(User $user, RecruitmentPost $post): bool
    {
        return $user->id === $post->user_id || $user->hasRole('admin');
    }
}
