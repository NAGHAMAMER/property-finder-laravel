<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function all_notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();

        // Keep the historical API behavior: opening the notification list marks
        // currently unread notifications as read, while returning the snapshot.
        $user->unreadNotifications->each->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Lightweight endpoint used by the web fallback polling when a websocket
     * provider is not configured. It never changes read status.
     */
    public function live(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $user->notifications()->latest()->limit(50)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}
