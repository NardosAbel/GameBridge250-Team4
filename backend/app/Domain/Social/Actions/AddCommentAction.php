<?php

namespace App\Domain\Social\Actions;

use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Post;
use App\Models\User;
use InvalidArgumentException;

class AddCommentAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $user, Post $post, string $content, ?int $parentId = null): Comment
    {
        if ($user->isBanned() || $user->isSuspended() || $user->isMuted()) {
            throw new InvalidArgumentException('Suspended or banned users cannot comment.');
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $content,
        ]);

        // Notify post author if not the commenter
        if ($post->user_id !== $user->id && $post->user) {
            $this->notificationService->send(
                $post->user,
                new GenericNotification(
                    title: 'New Comment',
                    message: "{$user->name} commented on your post.",
                    type: 'social',
                    actionUrl: "/posts/{$post->id}"
                ),
                eventType: 'comments'
            );
        }

        return $comment->load(['user.profile']);
    }
}
