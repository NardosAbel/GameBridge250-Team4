<?php

namespace App\Domain\Social\Actions;

use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Social\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ToggleFollowAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $follower, Model $followable): array
    {
        // Prevent following self
        if ($followable instanceof User && $follower->id === $followable->id) {
            throw new InvalidArgumentException('You cannot follow yourself.');
        }

        $existing = Follow::where('follower_id', $follower->id)
            ->where('followable_type', $followable->getMorphClass())
            ->where('followable_id', $followable->getKey())
            ->first();

        if ($existing) {
            $existing->delete();

            return ['status' => 'unfollowed', 'is_following' => false];
        }

        Follow::create([
            'follower_id' => $follower->id,
            'followable_type' => $followable->getMorphClass(),
            'followable_id' => $followable->getKey(),
        ]);

        // Send notification if following another user
        if ($followable instanceof User) {
            $this->notificationService->send(
                $followable,
                new GenericNotification(
                    title: 'New Follower',
                    message: "{$follower->name} started following you.",
                    type: 'social',
                    actionUrl: "/gamer/{$follower->profile?->handle}"
                ),
                eventType: 'follows'
            );
        }

        return ['status' => 'followed', 'is_following' => true];
    }
}
