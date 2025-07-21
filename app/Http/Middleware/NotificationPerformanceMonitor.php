<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificationPerformanceMonitor
{
    /**
     * Handle an incoming request and monitor notification performance
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        // Only monitor authenticated users
        if (auth()->check()) {
            $this->monitorNotificationPerformance($request, $startTime);
        }
        
        return $response;
    }
    
    /**
     * Monitor notification system performance
     */
    private function monitorNotificationPerformance(Request $request, float $startTime): void
    {
        $user = auth()->user();
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Check if this request involved notification operations
        if ($this->isNotificationRequest($request)) {
            $this->logNotificationPerformance($user->id, $request->path(), $executionTime);
            
            // Auto-cleanup if performance is degrading
            if ($executionTime > 500) { // If request took more than 500ms
                Log::warning("PERFORMANCE ALERT: Notification request took {$executionTime}ms for user {$user->id}");
                
                // Trigger aggressive cleanup
                UniversalNotificationService::performAggressiveCleanup($user);
                
                Log::info("AUTO-CLEANUP: Performed aggressive cleanup for user {$user->id} due to performance degradation");
            }
        }
        
        // Monitor notification cache size
        $this->monitorCacheSize($user);
    }
    
    /**
     * Check if request involves notification operations
     */
    private function isNotificationRequest(Request $request): bool
    {
        $notificationPaths = [
            'notifications',
            'notifications/mark-read',
            'notifications/mark-all-read',
            'dashboard', // Dashboard loads notifications
            'home' // Home page loads notifications
        ];
        
        foreach ($notificationPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log notification performance metrics
     */
    private function logNotificationPerformance(int $userId, string $path, float $executionTime): void
    {
        $cacheKey = "notification_performance_stats";
        $stats = Cache::get($cacheKey, [
            'total_requests' => 0,
            'total_time' => 0,
            'max_time' => 0,
            'slow_requests' => 0
        ]);
        
        $stats['total_requests']++;
        $stats['total_time'] += $executionTime;
        $stats['max_time'] = max($stats['max_time'], $executionTime);
        
        if ($executionTime > 200) { // Requests over 200ms are considered slow
            $stats['slow_requests']++;
        }
        
        $stats['avg_time'] = $stats['total_time'] / $stats['total_requests'];
        
        Cache::put($cacheKey, $stats, 3600); // Store for 1 hour
        
        // Log detailed performance data
        Log::info("NOTIFICATION PERFORMANCE", [
            'user_id' => $userId,
            'path' => $path,
            'execution_time_ms' => round($executionTime, 2),
            'avg_time_ms' => round($stats['avg_time'], 2),
            'slow_request_rate' => round(($stats['slow_requests'] / $stats['total_requests']) * 100, 2) . '%'
        ]);
    }
    
    /**
     * Monitor notification cache size for optimization
     */
    private function monitorCacheSize(object $user): void
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        $cacheSize = count($notifications);
        
        // Alert if cache size exceeds optimal limit
        if ($cacheSize > 5) {
            Log::warning("CACHE SIZE ALERT: User {$user->id} has {$cacheSize} notifications (limit: 5)");
            
            // Force immediate cleanup
            UniversalNotificationService::enforceLimit($user);
            
            Log::info("AUTO-ENFORCEMENT: Reduced notifications to 5 for user {$user->id}");
        }
        
        // Track cache efficiency
        $this->trackCacheEfficiency($user->id, $cacheSize);
    }
    
    /**
     * Track cache efficiency metrics
     */
    private function trackCacheEfficiency(int $userId, int $cacheSize): void
    {
        $statsKey = "notification_cache_efficiency";
        $stats = Cache::get($statsKey, [
            'total_users_checked' => 0,
            'total_cache_size' => 0,
            'users_over_limit' => 0,
            'optimal_users' => 0
        ]);
        
        $stats['total_users_checked']++;
        $stats['total_cache_size'] += $cacheSize;
        
        if ($cacheSize > 5) {
            $stats['users_over_limit']++;
        } elseif ($cacheSize <= 5) {
            $stats['optimal_users']++;
        }
        
        $stats['avg_cache_size'] = $stats['total_cache_size'] / $stats['total_users_checked'];
        $stats['optimization_rate'] = round(($stats['optimal_users'] / $stats['total_users_checked']) * 100, 2);
        
        Cache::put($statsKey, $stats, 3600);
        
        // Log efficiency metrics every 100 checks
        if ($stats['total_users_checked'] % 100 === 0) {
            Log::info("CACHE EFFICIENCY REPORT", [
                'avg_cache_size' => round($stats['avg_cache_size'], 2),
                'optimization_rate' => $stats['optimization_rate'] . '%',
                'users_over_limit' => $stats['users_over_limit'],
                'total_users_checked' => $stats['total_users_checked']
            ]);
        }
    }
}