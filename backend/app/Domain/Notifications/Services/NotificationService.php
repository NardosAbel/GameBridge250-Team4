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

        // 2. Email channel — prefer GenericNotification fields; otherwise use toArray.
        if ($prefs->email && $user->email) {
            $title = null;
            $body = null;
            if ($notification instanceof GenericNotification) {
                $title = $notification->title;
                $body = $notification->message;
            } elseif (method_exists($notification, 'toArray')) {
                $payload = $notification->toArray($user);
                $title = is_array($payload) ? ($payload['title'] ?? null) : null;
                $body = is_array($payload) ? ($payload['message'] ?? null) : null;
            }

            if (is_string($title) && $title !== '' && is_string($body)) {
                Mail::raw(
                    $title."\n\n".$body,
                    function ($message) use ($user, $title): void {
                        $message->to($user->email)->subject($title);
                    }
                );
            }
        }

        if ($prefs->sms && $user->phone) {
            Log::info("SMS provider is not configured for user {$user->id} event {$eventType}.");
        }

        if ($prefs->push) {
            Log::info("Push provider is not configured for user {$user->id} event {$eventType}.");
        }
    }
}
