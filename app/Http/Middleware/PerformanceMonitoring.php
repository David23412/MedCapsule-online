<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $requestId = uniqid('req_');
        
        // Add request ID to context for tracing
        $request->headers->set('X-Request-ID', $requestId);
        
        // Track database queries if enabled
        $queryCount = 0;
        $slowQueries = [];
        $slowQueryThreshold = config('medcapsule.performance.slow_query_threshold_ms', 1000);
        
        if (config('medcapsule.performance.query_logging', false)) {
            DB::listen(function ($query) use (&$queryCount, &$slowQueries, $slowQueryThreshold) {
                $queryCount++;
                if ($query->time > $slowQueryThreshold) {
                    $slowQueries[] = [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ];
                }
            });
        }
        
        // Log request start for important endpoints
        if ($this->shouldLogRequest($request)) {
            Log::info('Request started', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'memory_start' => $this->formatBytes($startMemory),
            ]);
        }
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $peakMemory = memory_get_peak_usage(true);
        
        // Add performance headers
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        
        // Determine if this is a performance issue
        $isSlowRequest = $duration > config('medcapsule.performance.slow_request_threshold_ms', 2000);
        $isHighMemory = $memoryUsage > config('medcapsule.performance.high_memory_threshold_mb', 50) * 1024 * 1024;
        $hasSlowQueries = !empty($slowQueries);
        
        // Log performance metrics
        $logLevel = 'info';
        if ($isSlowRequest || $isHighMemory || $hasSlowQueries) {
            $logLevel = 'warning';
        }
        
        if ($this->shouldLogRequest($request) || $logLevel === 'warning') {
            Log::$logLevel('Request completed', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'user_id' => auth()->id(),
                'duration_ms' => round($duration, 2),
                'memory_usage' => $this->formatBytes($memoryUsage),
                'peak_memory' => $this->formatBytes($peakMemory),
                'query_count' => $queryCount,
                'slow_queries_count' => count($slowQueries),
                'is_slow' => $isSlowRequest,
                'is_high_memory' => $isHighMemory,
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
            ]);
        }
        
        // Log slow queries separately for detailed analysis
        if (!empty($slowQueries)) {
            Log::warning('Slow queries detected', [
                'request_id' => $requestId,
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
                'slow_queries' => $slowQueries,
                'total_duration_ms' => round($duration, 2),
            ]);
        }
        
        // Store metrics for dashboard (if enabled) — deferred to run after response is sent
        if (config('medcapsule.analytics.track_user_activity', true)) {
            $metrics = [
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'query_count' => $queryCount,
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ];
            app()->terminating(function () use ($requestId, $metrics) {
                $this->storePerformanceMetrics($requestId, $metrics);
            });
        }
        
        return $response;
    }
    
    /**
     * Determine if we should log this request
     */
    private function shouldLogRequest(Request $request): bool
    {
        // Always log API requests and important actions
        $criticalRoutes = [
            'quiz.grade',
            'payment.submit',
            'admin.*',
            'api.*',
        ];
        
        $route = $request->route() ? $request->route()->getName() : '';
        
        foreach ($criticalRoutes as $pattern) {
            if (fnmatch($pattern, $route)) {
                return true;
            }
        }
        
        // Log POST, PUT, DELETE requests
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }
        
        // Skip static assets and health checks
        $skipPaths = [
            'health',
            'ping',
            '_debugbar',
            'telescope',
            'css/',
            'js/',
            'images/',
            'favicon.ico',
        ];
        
        foreach ($skipPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Store performance metrics for analysis
     */
    private function storePerformanceMetrics(string $requestId, array $metrics): void
    {
        try {
            // Store in cache for recent metrics (last hour)
            $cacheKey = 'performance_metrics_' . now()->format('Y-m-d-H');
            $currentMetrics = Cache::get($cacheKey, []);
            $currentMetrics[] = $metrics;
            
            // Keep only the last 1000 requests per hour to prevent memory issues
            if (count($currentMetrics) > 1000) {
                $currentMetrics = array_slice($currentMetrics, -1000);
            }
            
            Cache::put($cacheKey, $currentMetrics, now()->addHours(2));
            
            // Store aggregated daily stats
            $this->updateDailyStats($metrics);
            
        } catch (\Exception $e) {
            // Don't let monitoring failures affect the application
            Log::error('Failed to store performance metrics', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Update daily performance statistics
     */
    private function updateDailyStats(array $metrics): void
    {
        $dailyKey = 'daily_performance_stats_' . now()->format('Y-m-d');
        $stats = Cache::get($dailyKey, [
            'total_requests' => 0,
            'avg_duration_ms' => 0,
            'max_duration_ms' => 0,
            'slow_requests' => 0,
            'error_requests' => 0,
            'routes' => [],
        ]);
        
        $stats['total_requests']++;
        $stats['avg_duration_ms'] = (($stats['avg_duration_ms'] * ($stats['total_requests'] - 1)) + $metrics['duration_ms']) / $stats['total_requests'];
        $stats['max_duration_ms'] = max($stats['max_duration_ms'], $metrics['duration_ms']);
        
        if ($metrics['duration_ms'] > 2000) {
            $stats['slow_requests']++;
        }
        
        if ($metrics['status_code'] >= 400) {
            $stats['error_requests']++;
        }
        
        // Track route-specific stats
        $route = $metrics['route'];
        if (!isset($stats['routes'][$route])) {
            $stats['routes'][$route] = [
                'count' => 0,
                'avg_duration' => 0,
                'errors' => 0,
            ];
        }
        
        $routeStats = &$stats['routes'][$route];
        $routeStats['count']++;
        $routeStats['avg_duration'] = (($routeStats['avg_duration'] * ($routeStats['count'] - 1)) + $metrics['duration_ms']) / $routeStats['count'];
        
        if ($metrics['status_code'] >= 400) {
            $routeStats['errors']++;
        }
        
        Cache::put($dailyKey, $stats, now()->addDays(7));
    }
    
    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Get performance statistics for admin dashboard
     */
    public static function getPerformanceStats(string $period = 'today'): array
    {
        try {
            switch ($period) {
                case 'today':
                    $key = 'daily_performance_stats_' . now()->format('Y-m-d');
                    break;
                case 'yesterday':
                    $key = 'daily_performance_stats_' . now()->subDay()->format('Y-m-d');
                    break;
                default:
                    $key = 'daily_performance_stats_' . now()->format('Y-m-d');
            }
            
            return Cache::get($key, [
                'total_requests' => 0,
                'avg_duration_ms' => 0,
                'max_duration_ms' => 0,
                'slow_requests' => 0,
                'error_requests' => 0,
                'routes' => [],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve performance stats', [
                'period' => $period,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_requests' => 0,
                'avg_duration_ms' => 0,
                'max_duration_ms' => 0,
                'slow_requests' => 0,
                'error_requests' => 0,
                'routes' => [],
            ];
        }
    }
}