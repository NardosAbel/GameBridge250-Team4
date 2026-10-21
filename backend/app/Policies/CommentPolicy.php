<?php

namespace App\Policies;

use App\Domain\Social\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->id === $comment->user_id) {
            return true;
        }

        if ($user->hasAnyRole(['super_admin', 'moderator'])) {
            return true;
        }

        // Post author can moderate comments on their post
        if ($comment->post && $comment->post->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
