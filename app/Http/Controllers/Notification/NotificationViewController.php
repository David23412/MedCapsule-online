<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;

class NotificationViewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Get notifications for the authenticated user - LETHAL CACHED
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['limit' => 'sometimes|integer|between:1,100']);
        try {
            $user = auth()->user();
            $limit = (int) ($validated['limit'] ?? 100);

            return response()->json([
                'success' => true,
                'notifications' => UniversalNotificationService::getNotifications($user, $limit),
                'unread_count' => UniversalNotificationService::getUnreadCount($user)
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Get notification statistics - LETHAL CACHED
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();

            return response()->json([
                'success' => true,
                'stats' => UniversalNotificationService::getStats($user)
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
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
                'message' => $success ? 'All notifications marked as read' : 'Failed to mark notifications as read'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mark all as read failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $notificationId = $request->input('notification_id');
            
            if (!$notificationId) {
                return response()->json(['success' => false, 'message' => 'Notification ID is required'], 400);
            }
            
            $success = UniversalNotificationService::markAsRead($user, $notificationId);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mark as read failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'notification_id' => $request->input('notification_id')
            ]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Delete a specific notification
     */
    public function deleteNotification(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $notificationId = $request->input('notification_id');
            
            if (!$notificationId) {
                return response()->json(['success' => false, 'message' => 'Notification ID is required'], 400);
            }
            
            $success = UniversalNotificationService::deleteNotification($user, $notificationId);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Notification deleted' : 'Failed to delete notification'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delete notification failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'notification_id' => $request->input('notification_id')
            ]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Cleanup expired notifications
     */
    public function cleanup(): JsonResponse
    {
        try {
            $user = auth()->user();
            $deletedCount = UniversalNotificationService::cleanupExpiredNotifications($user);
            
            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} expired notifications",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cleanup failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}
