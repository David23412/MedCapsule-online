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
        try {
            $user = auth()->user();
            $limit = $request->get('limit', 20);
            
            // LETHAL CACHING: Ultra-aggressive caching for notifications
            $cacheKey = "notifications_{$user->id}_{$limit}_" . now()->format('Y-m-d-H-i');
            
            $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($user, $limit) {
                return [
                    'notifications' => UniversalNotificationService::getNotifications($user, $limit),
                    'unread_count' => UniversalNotificationService::getUnreadCount($user)
                ];
            });
            
            return response()->json([
                'success' => true,
                'notifications' => $data['notifications'],
                'unread_count' => $data['unread_count']
            ]);
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
            
            // LETHAL CACHING: Ultra-aggressive caching for notification stats
            $cacheKey = "notification_stats_{$user->id}_" . now()->format('Y-m-d-H');
            
            $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($user) {
                return UniversalNotificationService::getStats($user);
            });
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}