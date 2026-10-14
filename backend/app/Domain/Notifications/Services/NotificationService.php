<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification respecting user's multi-channel preferences.
     */
    public function send(User $user, Notification $notification, string $eventType = 'general'): void
    {
        $prefs = $user->notificationPreference ?? NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['in_app' => true, 'email' => true, 'sms' => false, 'push' => true]
        );

        // Check if granular preference disables this event
        if (! empty($prefs->preferences) && isset($prefs->preferences[$eventType]) && ! $prefs->preferences[$eventType]) {
            return;
        }

        // 1. In-App Notification (Laravel database channel)
        if ($prefs->in_app) {
            $user->notify($notification);
        }

        // 2. Email channel abstraction
        if ($prefs->email && $user->email) {
            Log::info("Email notification queued for user {$user->id} ({$user->email}): {$eventType}");
        }

        // 3. SMS channel abstraction (e.g. for urgent match / verification alerts)
        if ($prefs->sms && $user->phone) {
            Log::info("SMS notification queued for user {$user->id} ({$user->phone}): {$eventType}");
        }

        // 4. Push channel abstraction (FCM / WebPush)
        if ($prefs->push) {
            Log::info("Push notification queued for user {$user->id}: {$eventType}");
        }
    }
}
