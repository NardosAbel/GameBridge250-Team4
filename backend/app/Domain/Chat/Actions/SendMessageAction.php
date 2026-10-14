<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Models\ChatChannel;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Profiles\Models\Profile;
use App\Models\User;
use InvalidArgumentException;

class SendMessageAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function execute(User $user, ChatChannel $channel, string $message, ?string $attachmentUrl = null): ChatMessage
    {
        if ($user->isBanned() || $user->isSuspended()) {
            throw new InvalidArgumentException('Suspended or banned users cannot send chat messages.');
        }

        // Parse @mentions (e.g. @abebe)
        $mentionedUserIds = [];
        preg_match_all('/@([a-zA-Z0-9_.-]+)/', $message, $matches);
        if (! empty($matches[1])) {
            $handles = array_unique($matches[1]);
            $profiles = Profile::whereIn('handle', $handles)->orWhereIn('gamer_tag', $handles)->pluck('user_id')->all();
            $usersByName = User::whereIn('name', $handles)->pluck('id')->all();
            $mentionedUserIds = array_unique(array_merge($profiles, $usersByName));
        }

        $chatMessage = $channel->messages()->create([
            'user_id' => $user->id,
            'message' => $message,
            'mentions' => $mentionedUserIds,
            'attachment_url' => $attachmentUrl,
        ]);

        // Send notifications to mentioned users
        foreach ($mentionedUserIds as $mentionedId) {
            if ($mentionedId !== $user->id) {
                $mentionedUser = User::find($mentionedId);
                if ($mentionedUser) {
                    $this->notificationService->send(
                        $mentionedUser,
                        new GenericNotification(
                            title: 'You were mentioned',
                            message: "{$user->name} mentioned you in chat: \"".mb_substr($message, 0, 50).'"',
                            type: 'chat',
                            actionUrl: "/chat/{$channel->id}"
                        ),
                        eventType: 'chat_mentions'
                    );
                }
            }
        }

        return $chatMessage->load('user.profile');
    }
}
