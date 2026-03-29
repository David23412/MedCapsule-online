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
     * In-memory debounce for updateDailyStats: unix timestamp of the last write.
     * updateDailyStats runs in every request's terminating() callback, issuing
     * 2 cache ops (read + write) regardless of how recently it last ran.
     * Debouncing to once every DAILY_STATS_INTERVAL seconds per PHP-FPM worker
     * eliminates the vast majority of those cache ops while keeping the counters
     * accurate enough for operational dashboards.
     */
    private static int $dailyStatsLastUpdated = 0;

    /** Minimum seconds between consecutive daily-stats cache writes per worker. */
    private const DAILY_STATS_INTERVAL = 5;
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime   = microtime(true);
        $startMemory = memory_get_usage(true);
        $requestId   = uniqid('req_');

        $request->headers->set('X-Request-ID', $requestId);

        // Optional query logging (off by default)
        $queryCount        = 0;
        $slowQueries       = [];
        $slowQueryThreshold = config('medcapsule.performance.slow_query_threshold_ms', 1000);

        if (config('medcapsule.performance.query_logging', false)) {
            DB::listen(function ($query) use (&$queryCount, &$slowQueries, $slowQueryThreshold) {
                $queryCount++;
                if ($query->time > $slowQueryThreshold) {
                    $slowQueries[] = ['sql' => $query->sql, 'time' => $query->time];
                }
            });
        }

        $response = $next($request);

        $duration    = (microtime(true) - $startTime) * 1000;
        $memoryUsage = memory_get_usage(true) - $startMemory;
        $peakMemory  = memory_get_peak_usage(true);

        $response->headers->set('X-Request-ID',   $requestId);
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');

        $isSlowRequest = $duration     > config('medcapsule.performance.slow_request_threshold_ms', 2000);
        $isHighMemory  = $memoryUsage  > config('medcapsule.performance.high_memory_threshold_mb', 50) * 1024 * 1024;

        if ($isSlowRequest || $isHighMemory || !empty($slowQueries)) {
            Log::warning('Slow request', [
                'request_id'        => $requestId,
                'method'            => $request->method(),
                'url'               => $request->fullUrl(),
                'status_code'       => $response->getStatusCode(),
                'user_id'           => auth()->id(),
                'duration_ms'       => round($duration, 2),
                'memory_usage'      => $this->formatBytes($memoryUsage),
                'peak_memory'       => $this->formatBytes($peakMemory),
                'query_count'       => $queryCount,
                'slow_queries'      => $slowQueries,
                'route'             => optional($request->route())->getName(),
            ]);
        }

        if (config('medcapsule.analytics.track_user_activity', true)) {
            $metrics = [
                'route'           => optional($request->route())->getName() ?? 'unknown',
                'method'          => $request->method(),
                'status_code'     => $response->getStatusCode(),
                'duration_ms'     => round($duration, 2),
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'query_count'     => $queryCount,
                'user_id'         => auth()->id(),
                'timestamp'       => now(),
            ];
            app()->terminating(fn() => $this->storePerformanceMetrics($requestId, $metrics));
        }

        return $response;
    }

    private function storePerformanceMetrics(string $requestId, array $metrics): void
    {
        try {
            // The per-request raw-metrics array (performance_metrics_Y-m-d-H) was
            // previously read, appended, and re-written on every single request,
            // growing to up to 1,000 items.  No other part of the codebase reads
            // that key, so the serialisation overhead was pure waste.  The
            // lightweight daily-stats aggregation below is all that is needed.
            $this->updateDailyStats($metrics);
        } catch (\Exception $e) {
            Log::error('Failed to store performance metrics', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function updateDailyStats(array $metrics): void
    {
        // Debounce: skip if we already wrote daily stats within this worker recently.
        $now = time();
        if (($now - self::$dailyStatsLastUpdated) < self::DAILY_STATS_INTERVAL) {
            return;
        }
        self::$dailyStatsLastUpdated = $now;

        $dailyKey = 'daily_performance_stats_' . now()->format('Y-m-d');
        $stats    = Cache::get($dailyKey, [
            'total_requests' => 0,
            'avg_duration_ms' => 0,
            'max_duration_ms' => 0,
            'slow_requests'  => 0,
            'error_requests' => 0,
            'routes'         => [],
        ]);

        $stats['total_requests']++;
        $stats['avg_duration_ms'] = (($stats['avg_duration_ms'] * ($stats['total_requests'] - 1)) + $metrics['duration_ms']) / $stats['total_requests'];
        $stats['max_duration_ms'] = max($stats['max_duration_ms'], $metrics['duration_ms']);

        if ($metrics['duration_ms'] > 2000)    { $stats['slow_requests']++; }
        if ($metrics['status_code'] >= 400)    { $stats['error_requests']++; }

        $route = $metrics['route'];
        if (!isset($stats['routes'][$route])) {
            $stats['routes'][$route] = ['count' => 0, 'avg_duration' => 0, 'errors' => 0];
        }
        $r = &$stats['routes'][$route];
        $r['count']++;
        $r['avg_duration'] = (($r['avg_duration'] * ($r['count'] - 1)) + $metrics['duration_ms']) / $r['count'];
        if ($metrics['status_code'] >= 400) { $r['errors']++; }

        Cache::put($dailyKey, $stats, now()->addDays(7));
    }

    private function formatBytes(int $bytes): string
    {
        $units     = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public static function getPerformanceStats(string $period = 'today'): array
    {
        $key = 'daily_performance_stats_' . match($period) {
            'yesterday' => now()->subDay()->format('Y-m-d'),
            default     => now()->format('Y-m-d'),
        };
        return Cache::get($key, [
            'total_requests' => 0,
            'avg_duration_ms' => 0,
            'max_duration_ms' => 0,
            'slow_requests'  => 0,
            'error_requests' => 0,
            'routes'         => [],
        ]);
    }
}
