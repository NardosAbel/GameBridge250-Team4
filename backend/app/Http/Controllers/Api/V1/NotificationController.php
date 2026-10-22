<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\Models\NotificationPreference;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return response()->json([
            'data' => $notifications->items(),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $prefs = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['in_app' => true, 'email' => true, 'sms' => false, 'push' => true]
        );

        return response()->json([
            'data' => $prefs,
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'in_app' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'boolean'],
            'sms' => ['sometimes', 'boolean'],
            'push' => ['sometimes', 'boolean'],
            'preferences' => ['nullable', 'array'],
        ]);

        $prefs = NotificationPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'message' => 'Notification preferences updated.',
            'data' => $prefs,
        ]);
    }
}
