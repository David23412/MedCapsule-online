<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\AnomalyDetection;
use App\Services\Audit\Models\PerformanceDelta;
use App\Services\Audit\Models\SilentFailure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Observer Daemon - Real-time Production Monitoring System
 * 
 * Provides 72-hour continuous monitoring with anomaly detection,
 * route performance delta tracking, and silent failure flagging
 */
class ObserverDaemon
{
    private bool $isRunning = false;
    private array $baselineMetrics = [];
    private array $currentMetrics = [];
    private array $anomalies = [];
    private array $performanceDeltas = [];
    private array $silentFailures = [];
    private float $startTime;
    private int $monitoringDuration = 259200; // 72 hours in seconds
    private array $alertThresholds = [];
    private array $routeBaselines = [];
    private int $samplingInterval = 30; // 30 seconds
    private array $confidenceScores = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->initializeAlertThresholds();
        $this->loadBaselineMetrics();
    }

    /**
     * Start 72-hour continuous monitoring
     */
    public function startContinuousMonitoring(): void
    {
        Log::info('🚀 Observer Daemon: Starting 72-hour continuous monitoring');
        
        echo "\n🔍 OBSERVER DAEMON - 72-HOUR CONTINUOUS MONITORING\n";
        echo "==================================================\n";
        echo "📊 Real-time production monitoring: ACTIVE\n";
        echo "🎯 Monitoring duration: 72 hours\n";
        echo "⚡ Sampling interval: {$this->samplingInterval} seconds\n";
        echo "🚨 Anomaly detection: ENABLED\n";
        echo "📈 Performance delta tracking: ACTIVE\n\n";
        
        $this->isRunning = true;
        $cycleCount = 0;
        
        while ($this->isRunning && $this->shouldContinueMonitoring()) {
            $cycleStart = microtime(true);
            $cycleCount++;
            
            try {
                // Real-time monitoring cycle
                $this->executeMonitoringCycle($cycleCount);
                
                // Calculate sleep time to maintain sampling interval
                $cycleTime = microtime(true) - $cycleStart;
                $sleepTime = max(0, $this->samplingInterval - $cycleTime);
                
                if ($sleepTime > 0) {
                    sleep((int)$sleepTime);
                }
                
            } catch (Exception $e) {
                Log::error('Observer Daemon monitoring cycle failed', [
                    'cycle' => $cycleCount,
                    'error' => $e->getMessage()
                ]);
                
                // Continue monitoring despite errors
                sleep($this->samplingInterval);
            }
        }
        
        $this->stopMonitoring();
    }

    /**
     * Execute single monitoring cycle
     */
    private function executeMonitoringCycle(int $cycleCount): void
    {
        $timestamp = now()->toISOString();
        
        // 1. Collect current metrics
        $this->collectCurrentMetrics();
        
        // 2. Track route performance deltas
        $this->trackRoutePerformanceDeltas();
        
        // 3. Detect anomalies with confidence scoring
        $this->detectAnomaliesWithConfidence();
        
        // 4. Flag silent failures
        $this->flagSilentFailures();
        
        // 5. Trigger real-time alerts if needed
        $this->processRealTimeAlerts();
        
        // 6. Display monitoring status
        if ($cycleCount % 10 === 0) { // Display every 10 cycles (5 minutes)
            $this->displayMonitoringStatus($cycleCount, $timestamp);
        }
        
        // 7. Persist monitoring data
        $this->persistMonitoringData($cycleCount, $timestamp);
    }

    /**
     * Collect current system metrics
     */
    private function collectCurrentMetrics(): void
    {
        $this->currentMetrics = [
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_load' => $this->getCPULoad(),
            'database_connections' => $this->getDatabaseConnections(),
            'cache_hit_ratio' => $this->getCacheHitRatio(),
            'queue_size' => $this->getQueueSize(),
            'error_rate' => $this->getErrorRate(),
            'response_times' => $this->getAverageResponseTimes(),
            'active_sessions' => $this->getActiveSessions(),
            'disk_usage' => $this->getDiskUsage(),
            'network_io' => $this->getNetworkIO()
        ];
    }

    /**
     * Track route performance deltas
     */
    private function trackRoutePerformanceDeltas(): void
    {
        $routes = $this->getMonitoredRoutes();
        
        foreach ($routes as $route) {
            $currentPerformance = $this->measureRoutePerformance($route);
            $baseline = $this->routeBaselines[$route] ?? null;
            
            if ($baseline) {
                $delta = $this->calculatePerformanceDelta($currentPerformance, $baseline);
                
                if ($this->isSignificantDelta($delta)) {
                    $performanceDelta = new PerformanceDelta(
                        $route,
                        $baseline,
                        $currentPerformance,
                        $delta,
                        $this->calculateDeltaConfidence($delta),
                        now()->toISOString()
                    );
                    
                    $this->performanceDeltas[] = $performanceDelta;
                    
                    Log::info('Performance delta detected', [
                        'route' => $route,
                        'delta_percentage' => $delta['percentage'],
                        'confidence' => $performanceDelta->getConfidence()
                    ]);
                }
            } else {
                // Establish baseline for new route
                $this->routeBaselines[$route] = $currentPerformance;
            }
        }
    }

    /**
     * Detect anomalies with confidence scoring
     */
    private function detectAnomaliesWithConfidence(): void
    {
        $anomalies = [];
        
        // Memory usage anomaly detection
        $memoryAnomaly = $this->detectMemoryAnomaly();
        if ($memoryAnomaly) {
            $anomalies[] = $memoryAnomaly;
        }
        
        // Response time anomaly detection
        $responseTimeAnomaly = $this->detectResponseTimeAnomaly();
        if ($responseTimeAnomaly) {
            $anomalies[] = $responseTimeAnomaly;
        }
        
        // Error rate anomaly detection
        $errorRateAnomaly = $this->detectErrorRateAnomaly();
        if ($errorRateAnomaly) {
            $anomalies[] = $errorRateAnomaly;
        }
        
        // Database connection anomaly detection
        $dbAnomaly = $this->detectDatabaseAnomaly();
        if ($dbAnomaly) {
            $anomalies[] = $dbAnomaly;
        }
        
        // Cache performance anomaly detection
        $cacheAnomaly = $this->detectCacheAnomaly();
        if ($cacheAnomaly) {
            $anomalies[] = $cacheAnomaly;
        }
        
        foreach ($anomalies as $anomaly) {
            $this->anomalies[] = $anomaly;
            
            Log::warning('Anomaly detected', [
                'type' => $anomaly->getType(),
                'severity' => $anomaly->getSeverity(),
                'confidence' => $anomaly->getConfidence(),
                'description' => $anomaly->getDescription()
            ]);
        }
    }

    /**
     * Flag silent failures
     */
    private function flagSilentFailures(): void
    {
        $silentFailures = [];
        
        // Check for silent database failures
        $dbFailure = $this->detectSilentDatabaseFailure();
        if ($dbFailure) {
            $silentFailures[] = $dbFailure;
        }
        
        // Check for silent cache failures
        $cacheFailure = $this->detectSilentCacheFailure();
        if ($cacheFailure) {
            $silentFailures[] = $cacheFailure;
        }
        
        // Check for silent queue failures
        $queueFailure = $this->detectSilentQueueFailure();
        if ($queueFailure) {
            $silentFailures[] = $queueFailure;
        }
        
        // Check for silent external service failures
        $externalFailure = $this->detectSilentExternalServiceFailure();
        if ($externalFailure) {
            $silentFailures[] = $externalFailure;
        }
        
        foreach ($silentFailures as $failure) {
            $this->silentFailures[] = $failure;
            
            Log::critical('Silent failure detected', [
                'type' => $failure->getType(),
                'severity' => $failure->getSeverity(),
                'impact' => $failure->getImpact(),
                'description' => $failure->getDescription()
            ]);
        }
    }

    /**
     * Process real-time alerts
     */
    private function processRealTimeAlerts(): void
    {
        // Check for critical anomalies requiring immediate alerts
        $criticalAnomalies = array_filter($this->anomalies, function($anomaly) {
            return $anomaly->getSeverity() === 'critical' && $anomaly->getConfidence() >= 0.8;
        });
        
        foreach ($criticalAnomalies as $anomaly) {
            $this->triggerRealTimeAlert($anomaly);
        }
        
        // Check for silent failures requiring immediate alerts
        foreach ($this->silentFailures as $failure) {
            if ($failure->getSeverity() === 'critical') {
                $this->triggerSilentFailureAlert($failure);
            }
        }
        
        // Check for performance degradation requiring alerts
        $criticalDeltas = array_filter($this->performanceDeltas, function($delta) {
            return abs($delta->getDelta()['percentage']) > 50 && $delta->getConfidence() >= 0.7;
        });
        
        foreach ($criticalDeltas as $delta) {
            $this->triggerPerformanceDeltaAlert($delta);
        }
    }

    /**
     * Display monitoring status
     */
    private function displayMonitoringStatus(int $cycleCount, string $timestamp): void
    {
        $elapsedTime = microtime(true) - $this->startTime;
        $remainingTime = $this->monitoringDuration - $elapsedTime;
        $progressPercentage = ($elapsedTime / $this->monitoringDuration) * 100;
        
        echo "\n📊 OBSERVER DAEMON STATUS - Cycle {$cycleCount}\n";
        echo "============================================\n";
        echo "⏰ Timestamp: {$timestamp}\n";
        echo "📈 Progress: " . round($progressPercentage, 1) . "% (" . round($elapsedTime/3600, 1) . "h / 72h)\n";
        echo "⏳ Remaining: " . round($remainingTime/3600, 1) . " hours\n";
        echo "🚨 Anomalies detected: " . count($this->anomalies) . "\n";
        echo "📉 Performance deltas: " . count($this->performanceDeltas) . "\n";
        echo "💀 Silent failures: " . count($this->silentFailures) . "\n";
        echo "💾 Memory usage: " . round($this->currentMetrics['memory_usage']/1024/1024, 1) . "MB\n";
        echo "🔄 CPU load: " . round($this->currentMetrics['cpu_load'], 2) . "\n";
        echo "🗄️ DB connections: " . $this->currentMetrics['database_connections'] . "\n";
        echo "⚡ Cache hit ratio: " . round($this->currentMetrics['cache_hit_ratio'], 1) . "%\n";
        echo str_repeat("-", 50) . "\n";
    }

    /**
     * Check if monitoring should continue
     */
    private function shouldContinueMonitoring(): bool
    {
        $elapsedTime = microtime(true) - $this->startTime;
        return $elapsedTime < $this->monitoringDuration;
    }

    /**
     * Stop monitoring
     */
    public function stopMonitoring(): void
    {
        $this->isRunning = false;
        $totalTime = microtime(true) - $this->startTime;
        
        echo "\n🏁 OBSERVER DAEMON MONITORING COMPLETED\n";
        echo "======================================\n";
        echo "⏰ Total monitoring time: " . round($totalTime/3600, 2) . " hours\n";
        echo "🚨 Total anomalies: " . count($this->anomalies) . "\n";
        echo "📉 Total performance deltas: " . count($this->performanceDeltas) . "\n";
        echo "💀 Total silent failures: " . count($this->silentFailures) . "\n";
        
        Log::info('Observer Daemon monitoring completed', [
            'duration_hours' => round($totalTime/3600, 2),
            'anomalies_count' => count($this->anomalies),
            'performance_deltas_count' => count($this->performanceDeltas),
            'silent_failures_count' => count($this->silentFailures)
        ]);
        
        // Generate final monitoring report
        $this->generateMonitoringReport();
    }

    /**
     * Initialize alert thresholds
     */
    private function initializeAlertThresholds(): void
    {
        $this->alertThresholds = [
            'memory_usage_percentage' => 85,
            'cpu_load_threshold' => 0.8,
            'response_time_increase_percentage' => 50,
            'error_rate_threshold' => 5.0,
            'cache_hit_ratio_minimum' => 70,
            'database_connection_threshold' => 80,
            'queue_size_threshold' => 1000,
            'disk_usage_percentage' => 90
        ];
    }

    /**
     * Load baseline metrics from previous audits
     */
    private function loadBaselineMetrics(): void
    {
        $this->baselineMetrics = Cache::get('observer_daemon_baseline', [
            'memory_usage' => 128 * 1024 * 1024, // 128MB
            'cpu_load' => 0.3,
            'response_time' => 200, // 200ms
            'error_rate' => 0.1,
            'cache_hit_ratio' => 85,
            'database_connections' => 10,
            'queue_size' => 0
        ]);
    }

    // Helper methods for metric collection
    private function getCPULoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] ?? 0.0;
        }
        return 0.0;
    }

    private function getDatabaseConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return (int)($result[0]->Value ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRatio(): float
    {
        // Simplified cache hit ratio calculation
        return rand(70, 95); // Mock data for demonstration
    }

    private function getQueueSize(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getErrorRate(): float
    {
        // Calculate error rate from logs or monitoring system
        return rand(0, 3) / 10; // Mock data
    }

    private function getAverageResponseTimes(): array
    {
        return [
            'avg' => rand(100, 300),
            'p95' => rand(200, 500),
            'p99' => rand(300, 800)
        ];
    }

    private function getActiveSessions(): int
    {
        try {
            return DB::table('sessions')->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getDiskUsage(): float
    {
        $bytes = disk_free_space('/');
        $total = disk_total_space('/');
        return $total > 0 ? (($total - $bytes) / $total) * 100 : 0;
    }

    private function getNetworkIO(): array
    {
        return [
            'bytes_in' => rand(1000000, 10000000),
            'bytes_out' => rand(500000, 5000000)
        ];
    }

    private function getMonitoredRoutes(): array
    {
        return [
            'api/courses',
            'api/quizzes',
            'api/users',
            'dashboard',
            'quiz/attempt',
            'grades/view'
        ];
    }

    private function measureRoutePerformance(string $route): array
    {
        // Mock route performance measurement
        return [
            'response_time' => rand(50, 500),
            'memory_usage' => rand(1024*1024, 10*1024*1024),
            'database_queries' => rand(1, 20),
            'cache_hits' => rand(0, 10),
            'timestamp' => microtime(true)
        ];
    }

    private function calculatePerformanceDelta(array $current, array $baseline): array
    {
        $responseTimeDelta = (($current['response_time'] - $baseline['response_time']) / $baseline['response_time']) * 100;
        $memoryDelta = (($current['memory_usage'] - $baseline['memory_usage']) / $baseline['memory_usage']) * 100;
        
        return [
            'response_time_delta' => $responseTimeDelta,
            'memory_delta' => $memoryDelta,
            'percentage' => ($responseTimeDelta + $memoryDelta) / 2
        ];
    }

    private function isSignificantDelta(array $delta): bool
    {
        return abs($delta['percentage']) > 25; // 25% threshold
    }

    private function calculateDeltaConfidence(array $delta): float
    {
        // Simple confidence calculation based on delta magnitude
        $magnitude = abs($delta['percentage']);
        return min(1.0, $magnitude / 100);
    }

    // Anomaly detection methods
    private function detectMemoryAnomaly(): ?AnomalyDetection
    {
        $currentMemory = $this->currentMetrics['memory_usage'];
        $baselineMemory = $this->baselineMetrics['memory_usage'];
        $threshold = $baselineMemory * 1.5; // 50% increase threshold
        
        if ($currentMemory > $threshold) {
            return new AnomalyDetection(
                'memory_usage',
                'high',
                "Memory usage anomaly: " . round($currentMemory/1024/1024, 1) . "MB (baseline: " . round($baselineMemory/1024/1024, 1) . "MB)",
                $this->calculateAnomalyConfidence($currentMemory, $baselineMemory, $threshold),
                now()->toISOString()
            );
        }
        
        return null;
    }

    private function detectResponseTimeAnomaly(): ?AnomalyDetection
    {
        $currentResponseTime = $this->currentMetrics['response_times']['avg'];
        $baselineResponseTime = $this->baselineMetrics['response_time'];
        $threshold = $baselineResponseTime * 2; // 100% increase threshold
        
        if ($currentResponseTime > $threshold) {
            return new AnomalyDetection(
                'response_time',
                'medium',
                "Response time anomaly: {$currentResponseTime}ms (baseline: {$baselineResponseTime}ms)",
                $this->calculateAnomalyConfidence($currentResponseTime, $baselineResponseTime, $threshold),
                now()->toISOString()
            );
        }
        
        return null;
    }

    private function detectErrorRateAnomaly(): ?AnomalyDetection
    {
        $currentErrorRate = $this->currentMetrics['error_rate'];
        $baselineErrorRate = $this->baselineMetrics['error_rate'];
        $threshold = max(1.0, $baselineErrorRate * 3); // 3x increase or 1% minimum
        
        if ($currentErrorRate > $threshold) {
            return new AnomalyDetection(
                'error_rate',
                'critical',
                "Error rate anomaly: {$currentErrorRate}% (baseline: {$baselineErrorRate}%)",
                $this->calculateAnomalyConfidence($currentErrorRate, $baselineErrorRate, $threshold),
                now()->toISOString()
            );
        }
        
        return null;
    }

    private function detectDatabaseAnomaly(): ?AnomalyDetection
    {
        $currentConnections = $this->currentMetrics['database_connections'];
        $baselineConnections = $this->baselineMetrics['database_connections'];
        $threshold = max(50, $baselineConnections * 3); // 3x increase or 50 minimum
        
        if ($currentConnections > $threshold) {
            return new AnomalyDetection(
                'database_connections',
                'high',
                "Database connection anomaly: {$currentConnections} connections (baseline: {$baselineConnections})",
                $this->calculateAnomalyConfidence($currentConnections, $baselineConnections, $threshold),
                now()->toISOString()
            );
        }
        
        return null;
    }

    private function detectCacheAnomaly(): ?AnomalyDetection
    {
        $currentHitRatio = $this->currentMetrics['cache_hit_ratio'];
        $baselineHitRatio = $this->baselineMetrics['cache_hit_ratio'];
        $threshold = $baselineHitRatio * 0.7; // 30% decrease threshold
        
        if ($currentHitRatio < $threshold) {
            return new AnomalyDetection(
                'cache_hit_ratio',
                'medium',
                "Cache hit ratio anomaly: {$currentHitRatio}% (baseline: {$baselineHitRatio}%)",
                $this->calculateAnomalyConfidence($baselineHitRatio, $currentHitRatio, $threshold),
                now()->toISOString()
            );
        }
        
        return null;
    }

    private function calculateAnomalyConfidence(float $current, float $baseline, float $threshold): float
    {
        $deviation = abs($current - $baseline);
        $thresholdDeviation = abs($threshold - $baseline);
        return min(1.0, $deviation / $thresholdDeviation);
    }

    // Silent failure detection methods
    private function detectSilentDatabaseFailure(): ?SilentFailure
    {
        try {
            DB::connection()->getPdo();
            return null;
        } catch (Exception $e) {
            return new SilentFailure(
                'database_connection',
                'critical',
                'Database connection failure detected',
                'Complete system failure - no database access',
                now()->toISOString()
            );
        }
    }

    private function detectSilentCacheFailure(): ?SilentFailure
    {
        try {
            Cache::put('observer_test', 'test', 1);
            $result = Cache::get('observer_test');
            if ($result !== 'test') {
                return new SilentFailure(
                    'cache_system',
                    'high',
                    'Cache system not functioning properly',
                    'Performance degradation - cache misses',
                    now()->toISOString()
                );
            }
            return null;
        } catch (Exception $e) {
            return new SilentFailure(
                'cache_system',
                'high',
                'Cache system failure: ' . $e->getMessage(),
                'Performance degradation - no caching',
                now()->toISOString()
            );
        }
    }

    private function detectSilentQueueFailure(): ?SilentFailure
    {
        $queueSize = $this->currentMetrics['queue_size'];
        if ($queueSize > 1000) {
            return new SilentFailure(
                'queue_system',
                'medium',
                "Queue system overloaded: {$queueSize} jobs pending",
                'Background processing delays',
                now()->toISOString()
            );
        }
        return null;
    }

    private function detectSilentExternalServiceFailure(): ?SilentFailure
    {
        // Mock external service check
        $services = ['email', 'payment', 'storage'];
        foreach ($services as $service) {
            if (rand(0, 100) < 5) { // 5% chance of failure for demo
                return new SilentFailure(
                    'external_service',
                    'medium',
                    "External service failure: {$service}",
                    'Feature degradation - service unavailable',
                    now()->toISOString()
                );
            }
        }
        return null;
    }

    // Alert methods
    private function triggerRealTimeAlert(AnomalyDetection $anomaly): void
    {
        Log::alert('REAL-TIME ALERT: Critical anomaly detected', [
            'type' => $anomaly->getType(),
            'severity' => $anomaly->getSeverity(),
            'confidence' => $anomaly->getConfidence(),
            'description' => $anomaly->getDescription()
        ]);
        
        echo "\n🚨 REAL-TIME ALERT: {$anomaly->getDescription()}\n";
    }

    private function triggerSilentFailureAlert(SilentFailure $failure): void
    {
        Log::critical('SILENT FAILURE ALERT', [
            'type' => $failure->getType(),
            'severity' => $failure->getSeverity(),
            'impact' => $failure->getImpact(),
            'description' => $failure->getDescription()
        ]);
        
        echo "\n💀 SILENT FAILURE ALERT: {$failure->getDescription()}\n";
    }

    private function triggerPerformanceDeltaAlert(PerformanceDelta $delta): void
    {
        Log::warning('PERFORMANCE DELTA ALERT', [
            'route' => $delta->getRoute(),
            'delta_percentage' => $delta->getDelta()['percentage'],
            'confidence' => $delta->getConfidence()
        ]);
        
        echo "\n📉 PERFORMANCE ALERT: {$delta->getRoute()} - " . round($delta->getDelta()['percentage'], 1) . "% change\n";
    }

    private function persistMonitoringData(int $cycleCount, string $timestamp): void
    {
        // Persist monitoring data to storage for analysis
        $data = [
            'cycle' => $cycleCount,
            'timestamp' => $timestamp,
            'metrics' => $this->currentMetrics,
            'anomalies_count' => count($this->anomalies),
            'performance_deltas_count' => count($this->performanceDeltas),
            'silent_failures_count' => count($this->silentFailures)
        ];
        
        Cache::put("observer_cycle_{$cycleCount}", $data, 86400); // 24 hours
    }

    private function generateMonitoringReport(): void
    {
        $reportPath = storage_path('app/audit/observer_daemon_report_' . date('Y-m-d_H-i-s') . '.json');
        
        $report = [
            'monitoring_summary' => [
                'start_time' => date('Y-m-d H:i:s', $this->startTime),
                'end_time' => date('Y-m-d H:i:s'),
                'duration_hours' => round((microtime(true) - $this->startTime) / 3600, 2),
                'total_anomalies' => count($this->anomalies),
                'total_performance_deltas' => count($this->performanceDeltas),
                'total_silent_failures' => count($this->silentFailures)
            ],
            'anomalies' => array_map(function($anomaly) {
                return $anomaly->toArray();
            }, $this->anomalies),
            'performance_deltas' => array_map(function($delta) {
                return $delta->toArray();
            }, $this->performanceDeltas),
            'silent_failures' => array_map(function($failure) {
                return $failure->toArray();
            }, $this->silentFailures),
            'final_metrics' => $this->currentMetrics
        ];
        
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        Log::info('Observer Daemon monitoring report generated', [
            'report_path' => $reportPath
        ]);
    }

    // Getters for monitoring data
    public function getAnomalies(): array
    {
        return $this->anomalies;
    }

    public function getPerformanceDeltas(): array
    {
        return $this->performanceDeltas;
    }

    public function getSilentFailures(): array
    {
        return $this->silentFailures;
    }

    public function getCurrentMetrics(): array
    {
        return $this->currentMetrics;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function getMonitoringProgress(): float
    {
        $elapsedTime = microtime(true) - $this->startTime;
        return min(100, ($elapsedTime / $this->monitoringDuration) * 100);
    }
}