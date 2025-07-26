<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;

class NotificationActionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $notificationId = $request->get('notification_id');
            
            $success = UniversalNotificationService::markAsRead($user, $notificationId);
            $unreadCount = UniversalNotificationService::getUnreadCount($user);
            
            return response()->json([
                'success' => $success,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $success = UniversalNotificationService::markAllAsRead($user);
            
            return response()->json([
                'success' => $success,
                'unread_count' => 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Delete specific notification immediately
     */
    public function deleteNotification(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $notificationId = $request->get('notification_id');
            
            $success = UniversalNotificationService::deleteNotification($user, $notificationId);
            
            return response()->json([
                'success' => $success,
                'message' => 'Notification deleted for database optimization'
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}