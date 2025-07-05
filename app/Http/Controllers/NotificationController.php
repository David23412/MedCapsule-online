<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Notification;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Get all notifications for the authenticated user
     * Without auto-marking as read on view
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 20); 
        $userId = auth()->id();
        $cacheKey = "user_notifications_{$userId}_{$limit}";
        
        // If we have a timestamp parameter, bypass cache to ensure fresh data
        $bypassCache = $request->has('t');
        $cacheTtl = 10; // Reduced to 10 seconds for more responsive notifications
        
        // Check if we should use cache
        if (!$bypassCache && $cachedResult = Cache::get($cacheKey)) {
            return response()->json($cachedResult);
        }
        
        // Get notifications without modifying them
        $result = $this->notificationService->getUserNotifications($limit);
        
        // Cache the result
        Cache::put($cacheKey, $result, $cacheTtl);
        
        return response()->json($result);
    }
    
    /**
     * Mark all notifications as read for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request)
    {
        $userId = auth()->id();
        $notificationIds = $request->input('notification_ids', []);
        $markAll = $request->input('all', false);
        
        if ($markAll) {
            // Mark all as read
            Notification::where('user_id', $userId)
                        ->where('is_read', false)
                        ->update(['is_read' => true, 'read_at' => now()]);
        } elseif (!empty($notificationIds)) {
            // Mark specific notifications as read
            Notification::where('user_id', $userId)
                        ->whereIn('id', $notificationIds)
                        ->update(['is_read' => true, 'read_at' => now()]);
        }
        
        // Clear the cache - properly handle without wildcards
        Cache::forget("user_notifications_{$userId}_20"); // Clear the default limit cache
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Delete all read notifications for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRead(Request $request)
    {
        $userId = auth()->id();
        
        // Delete all read notifications
        $deleted = Notification::where('user_id', $userId)
                    ->where('is_read', true)
                    ->delete();
        
        // Clear all caches related to notifications for this user
        $cacheKey = "user_notifications_{$userId}_20"; // Default
        Cache::forget($cacheKey);
        
        // Also clear other potential caches with different limits
        $cacheLimits = [10, 50, 100];
        foreach ($cacheLimits as $limit) {
            Cache::forget("user_notifications_{$userId}_{$limit}");
        }
        
        return response()->json([
            'success' => true,
            'deleted_count' => $deleted,
            'message' => 'Read notifications deleted successfully'
        ]);
    }
} 