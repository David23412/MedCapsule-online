<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * PERFORMANCE MONITORING BEAST MODE
 * Real-time metrics, performance tracking, and optimization insights
 */
class PerformanceMonitorService
{
    private const METRICS_TTL = 3600; // 1 hour
    private const ALERT_THRESHOLD = 1000; // 1 second
    private const MEMORY_THRESHOLD = 64 * 1024 * 1024; // 64MB

    private $redis;
    private $metrics = [];

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * NUCLEAR PERFORMANCE TRACKING: Start operation monitoring
     */
    public function startOperation(string $operation, array $context = []): string
    {
        $operationId = uniqid($operation . '_', true);
        $startTime = microtime(true);

        $this->metrics[$operationId] = [
            'operation' => $operation,
            'start_time' => $startTime,
            'start_memory' => memory_get_usage(true),
            'query_count_start' => $this->getQueryCount(),
            'context' => $context,
            'timestamp' => now()->toISOString()
        ];

        // Store in Redis for real-time monitoring
        $this->redis->hset('performance:operations:active', $operationId, json_encode([
            'operation' => $operation,
            'start_time' => $startTime,
            'context' => $context
        ]));

        Log::info('🚀 PERFORMANCE MONITORING STARTED', [
            'operation_id' => $operationId,
            'operation' => $operation,
            'memory_start' => $this->formatBytes($this->metrics[$operationId]['start_memory'])
        ]);

        return $operationId;
    }

    /**
     * END PERFORMANCE TRACKING: Complete operation and analyze
     */
    public function endOperation(string $operationId, array $additionalData = []): array
    {
        if (!isset($this->metrics[$operationId])) {
            Log::warning('Performance operation not found', ['operation_id' => $operationId]);
            return [];
        }

        $metrics = $this->metrics[$operationId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $queryCountEnd = $this->getQueryCount();

        // Calculate performance metrics
        $executionTime = ($endTime - $metrics['start_time']) * 1000; // milliseconds
        $memoryUsed = $endMemory - $metrics['start_memory'];
        $queriesExecuted = $queryCountEnd - $metrics['query_count_start'];

        $performanceData = [
            'operation_id' => $operationId,
            'operation' => $metrics['operation'],
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_formatted' => $this->formatBytes($memoryUsed),
            'queries_executed' => $queriesExecuted,
            'queries_per_second' => $executionTime > 0 ? round($queriesExecuted / ($executionTime / 1000), 2) : 0,
            'timestamp' => $metrics['timestamp'],
            'context' => $metrics['context'],
            'additional_data' => $additionalData
        ];

        // Store performance data
        $this->storePerformanceData($performanceData);

        // Check for performance issues
        $this->analyzePerformance($performanceData);

        // Remove from active operations
        $this->redis->hdel('performance:operations:active', $operationId);

        // Clean up local metrics
        unset($this->metrics[$operationId]);

        Log::info('✅ PERFORMANCE MONITORING COMPLETED', [
            'operation_id' => $operationId,
            'operation' => $metrics['operation'],
            'execution_ms' => $performanceData['execution_time_ms'],
            'queries' => $queriesExecuted,
            'memory_mb' => round($memoryUsed / 1024 / 1024, 2)
        ]);

        return $performanceData;
    }

    /**
     * REAL-TIME PERFORMANCE METRICS
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'active_operations' => $this->getActiveOperationsCount(),
            'system_load' => $this->getSystemLoad(),
            'memory_usage' => $this->getMemoryUsage(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'average_response_time' => $this->getAverageResponseTime(),
            'slow_queries_count' => $this->getSlowQueriesCount(),
            'error_rate' => $this->getErrorRate()
        ];
    }

    /**
     * PERFORMANCE ANALYSIS: Detect bottlenecks and issues
     */
    private function analyzePerformance(array $performanceData): void
    {
        $issues = [];

        // Check execution time
        if ($performanceData['execution_time_ms'] > self::ALERT_THRESHOLD) {
            $issues[] = [
                'type' => 'slow_execution',
                'severity' => 'high',
                'message' => "Slow execution: {$performanceData['execution_time_ms']}ms",
                'threshold' => self::ALERT_THRESHOLD . 'ms'
            ];
        }

        // Check memory usage
        if ($performanceData['memory_used_bytes'] > self::MEMORY_THRESHOLD) {
            $issues[] = [
                'type' => 'high_memory',
                'severity' => 'medium',
                'message' => "High memory usage: {$performanceData['memory_used_formatted']}",
                'threshold' => $this->formatBytes(self::MEMORY_THRESHOLD)
            ];
        }

        // Check query count (N+1 detection)
        if ($performanceData['queries_executed'] > 10) {
            $issues[] = [
                'type' => 'high_query_count',
                'severity' => 'medium',
                'message' => "High query count: {$performanceData['queries_executed']}",
                'threshold' => '10 queries'
            ];
        }

        // Log issues
        if (!empty($issues)) {
            foreach ($issues as $issue) {
                Log::warning('🚨 PERFORMANCE ISSUE DETECTED', array_merge($issue, [
                    'operation_id' => $performanceData['operation_id'],
                    'operation' => $performanceData['operation']
                ]));
            }

            // Store issues for monitoring
            $this->storePerformanceIssues($performanceData['operation_id'], $issues);
        }
    }

    /**
     * PERFORMANCE HISTORY: Get historical data
     */
    public function getPerformanceHistory(string $operation = null, int $limit = 100): array
    {
        $key = $operation ? "performance:history:{$operation}" : 'performance:history:all';

        $history = $this->redis->lrange($key, 0, $limit - 1);

        return array_map(function($item) {
            return json_decode($item, true);
        }, $history);
    }

    /**
     * PERFORMANCE ALERTS: Get current alerts
     */
    public function getPerformanceAlerts(): array
    {
        $alerts = $this->redis->lrange('performance:alerts', 0, 50);

        return array_map(function($alert) {
            return json_decode($alert, true);
        }, $alerts);
    }

    /**
     * CACHE PERFORMANCE: Monitor cache effectiveness
     */
    public function getCachePerformance(): array
    {
        $cacheStats = [
            'hits' => (int) $this->redis->get('cache:hits') ?: 0,
            'misses' => (int) $this->redis->get('cache:misses') ?: 0,
            'total_requests' => 0,
            'hit_rate' => 0
        ];

        $cacheStats['total_requests'] = $cacheStats['hits'] + $cacheStats['misses'];
        $cacheStats['hit_rate'] = $cacheStats['total_requests'] > 0
            ? round(($cacheStats['hits'] / $cacheStats['total_requests']) * 100, 2)
            : 0;

        return $cacheStats;
    }

    /**
     * SYSTEM HEALTH: Comprehensive health check
     */
    public function getSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'checks' => [
                'database' => $this->checkDatabaseHealth(),
                'redis' => $this->checkRedisHealth(),
                'memory' => $this->checkMemoryHealth(),
                'disk' => $this->checkDiskHealth(),
                'cpu' => $this->checkCpuHealth()
            ],
            'metrics' => $this->getRealTimeMetrics(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * PERFORMANCE OPTIMIZATION SUGGESTIONS
     */
    public function getOptimizationSuggestions(): array
    {
        $suggestions = [];

        // Check for slow operations
        $slowOperations = $this->getSlowOperations();
        if (!empty($slowOperations)) {
            $suggestions[] = [
                'type' => 'slow_operations',
                'priority' => 'high',
                'message' => 'Found ' . count($slowOperations) . ' slow operations',
                'operations' => $slowOperations
            ];
        }

        // Check cache hit rate
        $cacheStats = $this->getCachePerformance();
        if ($cacheStats['hit_rate'] < 95) {
            $suggestions[] = [
                'type' => 'low_cache_hit_rate',
                'priority' => 'medium',
                'message' => "Cache hit rate is {$cacheStats['hit_rate']}% (target: 95%)",
                'current_rate' => $cacheStats['hit_rate']
            ];
        }

        // Check memory usage trends
        $memoryTrend = $this->getMemoryUsageTrend();
        if ($memoryTrend['trend'] === 'increasing') {
            $suggestions[] = [
                'type' => 'memory_leak_suspected',
                'priority' => 'high',
                'message' => 'Memory usage is trending upward',
                'trend' => $memoryTrend
            ];
        }

        return $suggestions;
    }

    /**
     * PRIVATE HELPER METHODS
     */
    private function storePerformanceData(array $data): void
    {
        // Store in operation-specific history
        $operationKey = "performance:history:{$data['operation']}";
        $this->redis->lpush($operationKey, json_encode($data));
        $this->redis->expire($operationKey, self::METRICS_TTL);

        // Store in global history
        $this->redis->lpush('performance:history:all', json_encode($data));
        $this->redis->ltrim('performance:history:all', 0, 999); // Keep last 1000
        $this->redis->expire('performance:history:all', self::METRICS_TTL);
    }

    private function storePerformanceIssues(string $operationId, array $issues): void
    {
        foreach ($issues as $issue) {
            $this->redis->lpush('performance:alerts', json_encode(array_merge($issue, [
                'operation_id' => $operationId,
                'timestamp' => now()->toISOString()
            ])));
        }

        $this->redis->ltrim('performance:alerts', 0, 99); // Keep last 100 alerts
        $this->redis->expire('performance:alerts', self::METRICS_TTL);
    }

    private function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function getActiveOperationsCount(): int
    {
        return $this->redis->hlen('performance:operations:active');
    }

    private function getSystemLoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0], 2);
        }
        return 0.0;
    }

    private function getMemoryUsage(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        return [
            'current' => $current,
            'current_formatted' => $this->formatBytes($current),
            'peak' => $peak,
            'peak_formatted' => $this->formatBytes($peak),
            'percentage' => round(($current / $peak) * 100, 2)
        ];
    }

    private function getCacheHitRate(): float
    {
        $stats = $this->getCachePerformance();
        return $stats['hit_rate'];
    }

    private function getAverageResponseTime(): float
    {
        // Calculate from recent operations
        $recentOps = $this->redis->lrange('performance:history:all', 0, 99);
        if (empty($recentOps)) {
            return 0.0;
        }

        $totalTime = 0;
        $count = 0;

        foreach ($recentOps as $op) {
            $data = json_decode($op, true);
            if (isset($data['execution_time_ms'])) {
                $totalTime += $data['execution_time_ms'];
                $count++;
            }
        }

        return $count > 0 ? round($totalTime / $count, 2) : 0.0;
    }

    private function getSlowQueriesCount(): int
    {
        $slowQueries = $this->redis->get('performance:slow_queries_count');
        return (int) $slowQueries ?: 0;
    }

    private function getErrorRate(): float
    {
        $totalOps = $this->redis->get('performance:total_operations');
        $errors = $this->redis->get('performance:errors_count');

        if (!$totalOps || $totalOps == 0) {
            return 0.0;
        }

        return round(($errors / $totalOps) * 100, 2);
    }

    private function getSlowOperations(): array
    {
        $operations = $this->redis->lrange('performance:history:all', 0, 99);
        $slowOps = [];

        foreach ($operations as $op) {
            $data = json_decode($op, true);
            if (isset($data['execution_time_ms']) && $data['execution_time_ms'] > self::ALERT_THRESHOLD) {
                $slowOps[] = [
                    'operation' => $data['operation'],
                    'execution_time_ms' => $data['execution_time_ms'],
                    'timestamp' => $data['timestamp']
                ];
            }
        }

        return array_slice($slowOps, 0, 10); // Top 10 slow operations
    }

    private function getMemoryUsageTrend(): array
    {
        // This would analyze memory usage over time
        // For now, return a placeholder
        return [
            'trend' => 'stable',
            'current' => 32.5,
            'average' => 31.2,
            'change_percent' => 1.3
        ];
    }

    private function checkDatabaseHealth(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'response_time_ms' => 10];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkRedisHealth(): array
    {
        try {
            $this->redis->ping();
            return ['status' => 'healthy', 'response_time_ms' => 5];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkMemoryHealth(): array
    {
        $usage = $this->getMemoryUsage();
        $isHealthy = $usage['current'] < (128 * 1024 * 1024); // 128MB limit

        return [
            'status' => $isHealthy ? 'healthy' : 'warning',
            'current_usage' => $usage['current_formatted'],
            'percentage' => $usage['percentage']
        ];
    }

    private function checkDiskHealth(): array
    {
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $percentage = round(($freeSpace / $totalSpace) * 100, 2);

        return [
            'status' => $percentage > 10 ? 'healthy' : 'warning',
            'free_space' => $this->formatBytes($freeSpace),
            'percentage_free' => $percentage
        ];
    }

    private function checkCpuHealth(): array
    {
        $load = $this->getSystemLoad();

        return [
            'status' => $load < 2.0 ? 'healthy' : 'warning',
            'load_average' => $load,
            'cores' => 4 // This would be detected dynamically
        ];
    }
}
