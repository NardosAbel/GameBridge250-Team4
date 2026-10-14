<?php

namespace App\Domain\Recruitment\Actions;

use App\Domain\Recruitment\Models\RecruitmentPost;
use App\Models\User;
use InvalidArgumentException;

class CloseRecruitmentPostAction
{
    public function execute(User $user, RecruitmentPost $post): RecruitmentPost
    {
        if ($post->user_id !== $user->id && ! $user->hasRole('admin')) {
            throw new InvalidArgumentException('You are not authorized to close this recruitment post.');
        }

        $post->update(['status' => 'closed']);

        return $post;
    }
}
