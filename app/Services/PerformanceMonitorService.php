<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;

/**
 * FIXED: Performance Monitoring Service - Real-time System Performance Tracking
 * Provides comprehensive monitoring, alerting, and optimization recommendations
 */
class PerformanceMonitorService
{
    // Performance thresholds
    const THRESHOLDS = [
        'response_time' => 200,      // ms
        'memory_usage' => 100,       // MB
        'cache_hit_rate' => 90,      // %
        'db_connection_time' => 50,  // ms
        'n1_query_count' => 5,       // per request
        'error_rate' => 1,           // %
    ];

    // Alert levels
    const ALERT_LEVELS = [
        'low' => ['threshold' => 75, 'color' => 'green'],
        'medium' => ['threshold' => 85, 'color' => 'yellow'],
        'high' => ['threshold' => 95, 'color' => 'red'],
        'critical' => ['threshold' => 100, 'color' => 'black']
    ];

    /**
     * FIXED: Start operation timing
     */
    public function startOperation(string $operation, array $context = []): string
    {
        $operationId = uniqid('perf_', true);

        $performanceData = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true),
            'context' => $context,
            'started_at' => now()->toISOString()
        ];

        // Store in cache for fast retrieval
        Cache::put("perf:operation:{$operationId}", $performanceData, 300); // 5 minutes

        return $operationId;
    }

    /**
     * FIXED: End operation timing and record metrics
     */
    public function endOperation(string $operationId, array $additionalData = []): array
    {
        $startData = Cache::get("perf:operation:{$operationId}");

        if (!$startData) {
            Log::warning('Performance operation not found', ['operation_id' => $operationId]);
            return ['error' => 'Operation not found'];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'operation' => $startData['operation'],
            'duration_ms' => round(($endTime - $startData['start_time']) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startData['memory_start']) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'context' => $startData['context'],
            'additional_data' => $additionalData,
            'completed_at' => now()->toISOString()
        ];

        // Store metrics
        $this->storePerformanceMetrics($metrics);

        // Check for performance issues
        $this->analyzePerformance($metrics);

        // Clean up
        Cache::forget("perf:operation:{$operationId}");

        return $metrics;
    }

    /**
     * FIXED: Store performance metrics for analysis
     */
    private function storePerformanceMetrics(array $metrics): void
    {
        // Store in cache for real-time access
        $cacheKey = "perf:metrics:" . date('Y-m-d-H');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = $metrics;

        // Keep only last 1000 metrics per hour
        if (count($existingMetrics) > 1000) {
            $existingMetrics = array_slice($existingMetrics, -1000);
        }

        Cache::put($cacheKey, $existingMetrics, 7200); // 2 hours

        // Store in database for long-term analysis (sample every 10th metric)
        if (rand(1, 10) === 1) {
            $this->storeMetricsInDatabase($metrics);
        }
    }

    /**
     * FIXED: Store metrics in database for historical analysis
     */
    private function storeMetricsInDatabase(array $metrics): void
    {
        try {
            DB::table('performance_metrics')->insert([
                'operation' => $metrics['operation'],
                'duration_ms' => $metrics['duration_ms'],
                'memory_usage_mb' => $metrics['memory_usage_mb'],
                'peak_memory_mb' => $metrics['peak_memory_mb'],
                'context' => json_encode($metrics['context']),
                'additional_data' => json_encode($metrics['additional_data']),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
                'metrics' => $metrics
            ]);
        }
    }

    /**
     * FIXED: Analyze performance and generate alerts
     */
    private function analyzePerformance(array $metrics): void
    {
        $alerts = [];

        // Check response time
        if ($metrics['duration_ms'] > self::THRESHOLDS['response_time']) {
            $alerts[] = [
                'type' => 'slow_response',
                'severity' => $this->calculateSeverity($metrics['duration_ms'], self::THRESHOLDS['response_time']),
                'message' => "Slow response: {$metrics['duration_ms']}ms for {$metrics['operation']}",
                'threshold' => self::THRESHOLDS['response_time'],
                'actual' => $metrics['duration_ms']
            ];
        }

        // Check memory usage
        if ($metrics['memory_usage_mb'] > self::THRESHOLDS['memory_usage']) {
            $alerts[] = [
                'type' => 'high_memory',
                'severity' => $this->calculateSeverity($metrics['memory_usage_mb'], self::THRESHOLDS['memory_usage']),
                'message' => "High memory usage: {$metrics['memory_usage_mb']}MB for {$metrics['operation']}",
                'threshold' => self::THRESHOLDS['memory_usage'],
                'actual' => $metrics['memory_usage_mb']
            ];
        }

        // Generate alerts if any issues found
        foreach ($alerts as $alert) {
            $this->generateAlert($alert, $metrics);
        }
    }

    /**
     * FIXED: Calculate alert severity
     */
    private function calculateSeverity(float $actual, float $threshold): string
    {
        $ratio = $actual / $threshold;

        if ($ratio >= 2) return 'critical';
        if ($ratio >= 1.5) return 'high';
        if ($ratio >= 1.2) return 'medium';
        return 'low';
    }

    /**
     * FIXED: Generate performance alert
     */
    private function generateAlert(array $alert, array $metrics): void
    {
        $alertData = [
            'operation' => $metrics['operation'],
            'alert_type' => $alert['type'],
            'severity' => $alert['severity'],
            'message' => $alert['message'],
            'threshold' => $alert['threshold'],
            'actual_value' => $alert['actual'],
            'context' => $metrics['context'],
            'created_at' => now()->toISOString()
        ];

        // Store alert
        $alerts = Cache::get('performance:alerts', []);
        $alerts[] = $alertData;

        // Keep only last 100 alerts
        if (count($alerts) > 100) {
            $alerts = array_slice($alerts, -100);
        }

        Cache::put('performance:alerts', $alerts, 3600); // 1 hour

        // Log alert
        Log::warning('Performance Alert Generated', $alertData);

        // Send notification for critical alerts
        if ($alert['severity'] === 'critical') {
            $this->sendCriticalAlertNotification($alertData);
        }
    }

    /**
     * FIXED: Send critical alert notification
     */
    private function sendCriticalAlertNotification(array $alertData): void
    {
        try {
            // Send to admin notification system
            $notificationData = [
                'title' => '🚨 Critical Performance Alert',
                'message' => $alertData['message'],
                'severity' => $alertData['severity'],
                'operation' => $alertData['operation'],
                'timestamp' => $alertData['created_at']
            ];

            // Log for monitoring systems
            Log::critical('Critical Performance Alert', $notificationData);

        } catch (\Exception $e) {
            Log::error('Failed to send critical alert notification', [
                'error' => $e->getMessage(),
                'alert' => $alertData
            ]);
        }
    }

    /**
     * FIXED: Get real-time performance metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'current_response_time' => $this->getAverageResponseTime(),
            'memory_usage' => $this->getCurrentMemoryUsage(),
            'active_operations' => $this->getActiveOperationsCount(),
            'recent_alerts' => $this->getRecentAlerts(5),
            'system_health' => $this->calculateSystemHealth(),
            'performance_trend' => $this->analyzePerformanceTrend()
        ];
    }

    /**
     * FIXED: Get system health metrics
     */
    public function getSystemHealth(): array
    {
        return [
            'database_connections' => $this->getDatabaseConnectionStatus(),
            'cache_performance' => $this->getCachePerformance(),
            'memory_status' => $this->getMemoryStatus(),
            'error_rate' => $this->getErrorRate(),
            'uptime' => $this->getSystemUptime()
        ];
    }

    /**
     * FIXED: Get performance alerts
     */
    public function getPerformanceAlerts(): array
    {
        return Cache::get('performance:alerts', []);
    }

    /**
     * FIXED: Get average response time for last hour
     */
    private function getAverageResponseTime(): float
    {
        $metrics = Cache::get("perf:metrics:" . date('Y-m-d-H'), []);

        if (empty($metrics)) {
            return 0.0;
        }

        $totalTime = array_sum(array_column($metrics, 'duration_ms'));
        return round($totalTime / count($metrics), 2);
    }

    /**
     * FIXED: Get current memory usage
     */
    private function getCurrentMemoryUsage(): array
    {
        return [
            'current' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * FIXED: Get active operations count
     */
    private function getActiveOperationsCount(): int
    {
        try {
            $connection = Redis::connection();
            $cursor = 0;
            $count = 0;
            $scanCount = 100;

            do {
                $result = $connection->scan($cursor, [
                    'match' => 'perf:operation:*',
                    'count' => $scanCount,
                ]);

                if ($result === false) {
                    break;
                }

                if (is_array($result) && array_key_exists(0, $result) && array_key_exists(1, $result)) {
                    $cursor = (int) $result[0];
                    $keys = $result[1];
                } else {
                    $cursor = 0;
                    $keys = $result;
                }

                $count += count($this->normalizeRedisKeys(is_array($keys) ? $keys : [$keys]));
            } while ((int) $cursor !== 0);

            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function normalizeRedisKeys(array $keys): array
    {
        $normalized = [];

        array_walk_recursive($keys, function ($value) use (&$normalized) {
            if (!is_scalar($value)) {
                return;
            }

            $key = trim((string) $value);
            if ($key !== '') {
                $normalized[] = $key;
            }
        });

        return array_values(array_unique($normalized));
    }

    /**
     * FIXED: Get recent alerts
     */
    private function getRecentAlerts(int $limit): array
    {
        $alerts = Cache::get('performance:alerts', []);
        return array_slice($alerts, -$limit);
    }

    /**
     * FIXED: Calculate system health score
     */
    private function calculateSystemHealth(): float
    {
        $metrics = $this->getRealTimeMetrics();
        $healthFactors = [];

        // Response time health (40% weight)
        $rtHealth = $metrics['current_response_time'] <= self::THRESHOLDS['response_time'] ? 100 :
                   max(0, 100 - (($metrics['current_response_time'] - self::THRESHOLDS['response_time']) / 10));
        $healthFactors[] = ['factor' => 'response_time', 'weight' => 0.4, 'score' => $rtHealth];

        // Memory health (30% weight)
        $memHealth = $metrics['memory_usage']['current'] <= self::THRESHOLDS['memory_usage'] ? 100 :
                    max(0, 100 - (($metrics['memory_usage']['current'] - self::THRESHOLDS['memory_usage']) / 10));
        $healthFactors[] = ['factor' => 'memory', 'weight' => 0.3, 'score' => $memHealth];

        // Alert health (20% weight)
        $alertHealth = count($metrics['recent_alerts']) === 0 ? 100 :
                      max(0, 100 - (count($metrics['recent_alerts']) * 10));
        $healthFactors[] = ['factor' => 'alerts', 'weight' => 0.2, 'score' => $alertHealth];

        // Error rate health (10% weight)
        $errorHealth = $this->getErrorRate() <= self::THRESHOLDS['error_rate'] ? 100 :
                      max(0, 100 - ($this->getErrorRate() - self::THRESHOLDS['error_rate']) * 10);
        $healthFactors[] = ['factor' => 'error_rate', 'weight' => 0.1, 'score' => $errorHealth];

        // Calculate weighted average
        $totalScore = 0;
        foreach ($healthFactors as $factor) {
            $totalScore += $factor['score'] * $factor['weight'];
        }

        return round($totalScore, 2);
    }

    /**
     * FIXED: Analyze performance trend
     */
    private function analyzePerformanceTrend(): array
    {
        $currentHour = date('Y-m-d-H');
        $previousHour = date('Y-m-d-H', strtotime('-1 hour'));

        $currentMetrics = Cache::get("perf:metrics:{$currentHour}", []);
        $previousMetrics = Cache::get("perf:metrics:{$previousHour}", []);

        if (empty($currentMetrics) || empty($previousMetrics)) {
            return ['trend' => 'unknown', 'change_percent' => 0, 'data_points' => 0];
        }

        $currentAvg = array_sum(array_column($currentMetrics, 'duration_ms')) / count($currentMetrics);
        $previousAvg = array_sum(array_column($previousMetrics, 'duration_ms')) / count($previousMetrics);

        $changePercent = $previousAvg > 0 ? (($currentAvg - $previousAvg) / $previousAvg) * 100 : 0;

        return [
            'trend' => $changePercent > 5 ? 'worsening' : ($changePercent < -5 ? 'improving' : 'stable'),
            'change_percent' => round($changePercent, 2),
            'current_avg' => round($currentAvg, 2),
            'previous_avg' => round($previousAvg, 2),
            'data_points' => count($currentMetrics) + count($previousMetrics)
        ];
    }

    /**
     * FIXED: Get database connection status
     */
    private function getDatabaseConnectionStatus(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'connection_time_ms' => round($connectionTime, 2),
                'is_healthy' => $connectionTime <= self::THRESHOLDS['db_connection_time']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection_time_ms' => 0,
                'is_healthy' => false
            ];
        }
    }

    /**
     * FIXED: Get cache performance
     */
    private function getCachePerformance(): array
    {
        if (!class_exists('Redis')) {
            return ['hit_rate' => 0, 'status' => 'no_redis'];
        }

        try {
            $info = Redis::info('stats');
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;

            $total = $hits + $misses;
            $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;

            return [
                'hit_rate' => round($hitRate, 2),
                'hits' => $hits,
                'misses' => $misses,
                'is_healthy' => $hitRate >= self::THRESHOLDS['cache_hit_rate'],
                'status' => 'healthy'
            ];
        } catch (\Exception $e) {
            return [
                'hit_rate' => 0,
                'error' => $e->getMessage(),
                'is_healthy' => false,
                'status' => 'error'
            ];
        }
    }

    /**
     * FIXED: Get memory status
     */
    private function getMemoryStatus(): array
    {
        $current = round(memory_get_usage(true) / 1024 / 1024, 2);
        $peak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current_mb' => $current,
            'peak_mb' => $peak,
            'limit_mb' => $limit,
            'usage_percent' => $limit > 0 ? round(($current / $limit) * 100, 2) : 0,
            'is_healthy' => $current <= self::THRESHOLDS['memory_usage']
        ];
    }

    /**
     * FIXED: Get error rate
     */
    private function getErrorRate(): float
    {
        $logs = Cache::get('performance:alerts', []);
        $errorLogs = array_filter($logs, function ($log) {
            return in_array($log['alert_type'], ['error', 'exception', 'critical']);
        });

        $totalLogs = count($logs);
        return $totalLogs > 0 ? round((count($errorLogs) / $totalLogs) * 100, 2) : 0.0;
    }

    /**
     * FIXED: Get system uptime
     */
    private function getSystemUptime(): array
    {
        $uptime = $this->getSystemUptimeSeconds();

        return [
            'seconds' => $uptime,
            'formatted' => $this->formatUptime($uptime),
            'days' => floor($uptime / 86400),
            'hours' => floor(($uptime % 86400) / 3600),
            'minutes' => floor(($uptime % 3600) / 60)
        ];
    }

    /**
     * FIXED: Get system uptime in seconds (placeholder)
     */
    private function getSystemUptimeSeconds(): int
    {
        // In a real system, this would read from /proc/uptime or similar
        // For now, return a placeholder value
        return Cache::get('system:start_time', time()) - time();
    }

    /**
     * FIXED: Format uptime for display
     */
    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * FIXED: Parse memory limit
     */
    private function parseMemoryLimit(string $limit): float
    {
        $unit = strtolower(substr($limit, -1));
        $value = (float) substr($limit, 0, -1);

        switch ($unit) {
            case 'g': return $value * 1024;
            case 'm': return $value;
            case 'k': return $value / 1024;
            default: return (float) $limit;
        }
    }

    /**
     * FIXED: Generate comprehensive performance report
     */
    public function generatePerformanceReport(): array
    {
        $report = [
            'generated_at' => now()->toISOString(),
            'period' => 'last_24_hours',
            'summary' => [
                'overall_health' => $this->calculateSystemHealth(),
                'total_operations' => $this->getTotalOperationsCount(),
                'average_response_time' => $this->getAverageResponseTime(),
                'peak_memory_usage' => $this->getPeakMemoryUsage(),
                'error_rate' => $this->getErrorRate()
            ],
            'metrics' => [
                'response_times' => $this->getResponseTimeDistribution(),
                'memory_usage' => $this->getMemoryUsageTrend(),
                'cache_performance' => $this->getCachePerformance(),
                'database_performance' => $this->getDatabaseConnectionStatus()
            ],
            'alerts' => [
                'total_alerts' => count($this->getPerformanceAlerts()),
                'critical_alerts' => $this->getCriticalAlertsCount(),
                'recent_alerts' => $this->getRecentAlerts(10)
            ],
            'recommendations' => $this->generateOptimizationRecommendations()
        ];

        return $report;
    }

    /**
     * FIXED: Get total operations count
     */
    private function getTotalOperationsCount(): int
    {
        $count = 0;
        for ($i = 0; $i < 24; $i++) {
            $hour = date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get("perf:metrics:{$hour}", []);
            $count += count($metrics);
        }
        return $count;
    }

    /**
     * FIXED: Get response time distribution
     */
    private function getResponseTimeDistribution(): array
    {
        $metrics = Cache::get("perf:metrics:" . date('Y-m-d-H'), []);

        if (empty($metrics)) {
            return ['fast' => 0, 'medium' => 0, 'slow' => 0];
        }

        $distribution = ['fast' => 0, 'medium' => 0, 'slow' => 0];

        foreach ($metrics as $metric) {
            $duration = $metric['duration_ms'];
            if ($duration <= 100) {
                $distribution['fast']++;
            } elseif ($duration <= 500) {
                $distribution['medium']++;
            } else {
                $distribution['slow']++;
            }
        }

        return $distribution;
    }

    /**
     * FIXED: Get memory usage trend
     */
    private function getMemoryUsageTrend(): array
    {
        $metrics = Cache::get("perf:metrics:" . date('Y-m-d-H'), []);

        if (empty($metrics)) {
            return ['average' => 0, 'peak' => 0, 'trend' => 'stable'];
        }

        $memoryUsages = array_column($metrics, 'memory_usage_mb');
        $average = round(array_sum($memoryUsages) / count($memoryUsages), 2);
        $peak = round(max($memoryUsages), 2);

        return [
            'average_mb' => $average,
            'peak_mb' => $peak,
            'trend' => $this->analyzeMemoryTrend($memoryUsages)
        ];
    }

    /**
     * FIXED: Analyze memory trend
     */
    private function analyzeMemoryTrend(array $memoryUsages): string
    {
        if (count($memoryUsages) < 2) return 'stable';

        $recent = array_slice($memoryUsages, -5);
        $older = array_slice($memoryUsages, 0, -5);

        if (empty($older)) return 'stable';

        $recentAvg = array_sum($recent) / count($recent);
        $olderAvg = array_sum($older) / count($older);

        $change = (($recentAvg - $olderAvg) / $olderAvg) * 100;

        if ($change > 10) return 'increasing';
        if ($change < -10) return 'decreasing';
        return 'stable';
    }

    /**
     * FIXED: Get peak memory usage
     */
    private function getPeakMemoryUsage(): float
    {
        $metrics = Cache::get("perf:metrics:" . date('Y-m-d-H'), []);
        if (empty($metrics)) return 0.0;

        return round(max(array_column($metrics, 'peak_memory_mb')), 2);
    }

    /**
     * FIXED: Get critical alerts count
     */
    private function getCriticalAlertsCount(): int
    {
        $alerts = $this->getPerformanceAlerts();
        return count(array_filter($alerts, function ($alert) {
            return ($alert['severity'] ?? 'low') === 'critical';
        }));
    }

    /**
     * FIXED: Generate optimization recommendations
     */
    private function generateOptimizationRecommendations(): array
    {
        $recommendations = [];

        $health = $this->calculateSystemHealth();
        if ($health < 80) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'system_health',
                'title' => 'Improve Overall System Health',
                'description' => 'System health is below 80%. Review performance bottlenecks.',
                'estimated_impact' => '20-30% improvement'
            ];
        }

        $avgResponseTime = $this->getAverageResponseTime();
        if ($avgResponseTime > self::THRESHOLDS['response_time']) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'response_time',
                'title' => 'Optimize Response Times',
                'description' => "Average response time ({$avgResponseTime}ms) exceeds threshold.",
                'estimated_impact' => '50-70% faster responses'
            ];
        }

        $cachePerformance = $this->getCachePerformance();
        if (($cachePerformance['hit_rate'] ?? 0) < self::THRESHOLDS['cache_hit_rate']) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'cache_performance',
                'title' => 'Improve Cache Hit Rate',
                'description' => 'Cache hit rate is below 90%. Optimize cache strategy.',
                'estimated_impact' => '40-60% better performance'
            ];
        }

        $memoryUsage = $this->getCurrentMemoryUsage();
        if ($memoryUsage['current'] > self::THRESHOLDS['memory_usage']) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'memory_usage',
                'title' => 'Reduce Memory Usage',
                'description' => "Memory usage ({$memoryUsage['current']}MB) exceeds threshold.",
                'estimated_impact' => '30-50% less memory usage'
            ];
        }

        return $recommendations;
    }
}
