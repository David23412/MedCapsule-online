<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;

class NotificationCleanupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Clean up expired notifications - LETHAL ERROR HANDLING
     */
    public function cleanup(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $removedCount = UniversalNotificationService::cleanupExpiredNotifications($user);
            
            
            return response()->json([
                'success' => true,
                'removed_count' => $removedCount,
                'message' => "Successfully cleaned up {$removedCount} expired notifications"
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to cleanup notifications'
            ], 500);
        }
    }

    /**
     * AGGRESSIVE CLEANUP - Delete all viewed notifications immediately - LETHAL ERROR HANDLING
     */
    public function aggressiveCleanup(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $deletedCount = UniversalNotificationService::aggressiveDeleteViewed($user);
            
            
            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => 'All viewed notifications deleted for database optimization'
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * ULTRA AGGRESSIVE CLEANUP - Delete old read notifications
     */
    public function ultraCleanup(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $deletedCount = UniversalNotificationService::ultraAggressiveCleanup($user, 1);
            
            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => 'Ultra aggressive cleanup completed for maximum database optimization'
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * Clear all test notifications
     */
    public function clearTestNotifications(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $cacheKey = "user_notifications_{$user->id}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            \Illuminate\Support\Facades\Cache::put("user_unread_count_{$user->id}", 0, 86400);

            return response()->json([
                'success' => true,
                'message' => 'All test notifications cleared'
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}