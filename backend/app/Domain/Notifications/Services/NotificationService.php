<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Models\NotificationPreference;
use App\Domain\Notifications\Notifications\GenericNotification;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        if ($prefs->email && $user->email && $notification instanceof GenericNotification) {
            Mail::raw(
                $notification->title."\n\n".$notification->message,
                function ($message) use ($user, $notification): void {
                    $message->to($user->email)->subject($notification->title);
                }
            );
        }

        if ($prefs->sms && $user->phone) {
            Log::info("SMS provider is not configured for user {$user->id} event {$eventType}.");
        }

        if ($prefs->push) {
            Log::info("Push provider is not configured for user {$user->id} event {$eventType}.");
        }
    }
}
