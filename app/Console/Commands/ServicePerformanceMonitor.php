<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServicePerformanceMonitor extends Command
{
    protected $signature = 'service:monitor {--duration=60 : Monitor duration in seconds} {--interval=5 : Check interval in seconds}';
    protected $description = 'Real-time service performance monitoring dashboard';
    
    private array $serviceMetrics = [];
    private array $performanceHistory = [];
    private int $startTime;
    
    public function handle(): int
    {
        $duration = (int) $this->option('duration');
        $interval = (int) $this->option('interval');
        
        $this->startTime = time();
        $endTime = $this->startTime + $duration;
        
        $this->info("🚀 STARTING SERVICE PERFORMANCE MONITORING");
        $this->info("Duration: {$duration} seconds, Interval: {$interval} seconds");
        $this->line("");
        
        while (time() < $endTime) {
            $this->collectMetrics();
            $this->displayDashboard();
            
            if (time() + $interval < $endTime) {
                sleep($interval);
            } else {
                break;
            }
        }
        
        $this->generateFinalReport();
        
        return 0;
    }
    
    private function collectMetrics(): void
    {
        $timestamp = time();
        
        // Collect system metrics
        $systemMetrics = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_load' => sys_getloadavg()[0] ?? 0,
            'timestamp' => $timestamp
        ];
        
        // Collect database metrics
        $dbMetrics = $this->collectDatabaseMetrics();
        
        // Collect cache metrics
        $cacheMetrics = $this->collectCacheMetrics();
        
        // Store metrics
        $this->serviceMetrics[$timestamp] = [
            'system' => $systemMetrics,
            'database' => $dbMetrics,
            'cache' => $cacheMetrics
        ];
        
        // Keep only last 100 measurements
        if (count($this->serviceMetrics) > 100) {
            $this->serviceMetrics = array_slice($this->serviceMetrics, -100, null, true);
        }
    }
    
    private function collectDatabaseMetrics(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test database connection
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $startTime) * 1000;
            
            // Get connection count (MySQL specific)
            $connections = 0;
            try {
                $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                $connections = $result[0]->Value ?? 0;
            } catch (\Exception $e) {
                // Fallback for non-MySQL databases
                $connections = 1;
            }
            
            // Test query performance
            $queryStartTime = microtime(true);
            DB::select("SELECT 1");
            $queryTime = (microtime(true) - $queryStartTime) * 1000;
            
            return [
                'connection_time_ms' => $connectionTime,
                'query_time_ms' => $queryTime,
                'active_connections' => $connections,
                'status' => 'healthy'
            ];
            
        } catch (\Exception $e) {
            return [
                'connection_time_ms' => 0,
                'query_time_ms' => 0,
                'active_connections' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function collectCacheMetrics(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test cache write
            $testKey = 'performance_monitor_test_' . time();
            Cache::put($testKey, 'test_value', 60);
            $writeTime = (microtime(true) - $startTime) * 1000;
            
            // Test cache read
            $readStartTime = microtime(true);
            $value = Cache::get($testKey);
            $readTime = (microtime(true) - $readStartTime) * 1000;
            
            // Clean up
            Cache::forget($testKey);
            
            return [
                'write_time_ms' => $writeTime,
                'read_time_ms' => $readTime,
                'status' => $value === 'test_value' ? 'healthy' : 'degraded'
            ];
            
        } catch (\Exception $e) {
            return [
                'write_time_ms' => 0,
                'read_time_ms' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function displayDashboard(): void
    {
        // Clear screen
        system('clear');
        
        $this->info("🔥 SERVICE PERFORMANCE MONITORING DASHBOARD 🔥");
        $this->info("Runtime: " . (time() - $this->startTime) . " seconds");
        $this->line("");
        
        if (empty($this->serviceMetrics)) {
            $this->warn("No metrics collected yet...");
            return;
        }
        
        $latest = end($this->serviceMetrics);
        
        // System Metrics
        $this->displaySystemMetrics($latest['system']);
        $this->line("");
        
        // Database Metrics
        $this->displayDatabaseMetrics($latest['database']);
        $this->line("");
        
        // Cache Metrics
        $this->displayCacheMetrics($latest['cache']);
        $this->line("");
        
        // Performance Trends
        $this->displayPerformanceTrends();
        $this->line("");
        
        // Alerts
        $this->displayAlerts($latest);
    }
    
    private function displaySystemMetrics(array $metrics): void
    {
        $this->info("📊 SYSTEM METRICS");
        $this->line("Memory Usage: " . $this->formatBytes($metrics['memory_usage']));
        $this->line("Memory Peak:  " . $this->formatBytes($metrics['memory_peak']));
        $this->line("CPU Load:     " . round($metrics['cpu_load'], 2));
        
        // Memory usage bar
        $memoryPercent = ($metrics['memory_usage'] / (1024 * 1024 * 1024)) * 100; // Assume 1GB max
        $this->displayProgressBar("Memory", $memoryPercent, 80);
    }
    
    private function displayDatabaseMetrics(array $metrics): void
    {
        $this->info("🗄️  DATABASE METRICS");
        
        if ($metrics['status'] === 'healthy') {
            $this->line("<info>Status: ✅ Healthy</info>");
            $this->line("Connection Time: " . round($metrics['connection_time_ms'], 2) . "ms");
            $this->line("Query Time:      " . round($metrics['query_time_ms'], 2) . "ms");
            $this->line("Connections:     " . $metrics['active_connections']);
            
            // Performance bars
            $this->displayProgressBar("Connection", $metrics['connection_time_ms'], 100);
            $this->displayProgressBar("Query", $metrics['query_time_ms'], 50);
        } else {
            $this->line("<error>Status: ❌ Error</error>");
            if (isset($metrics['error'])) {
                $this->line("Error: " . $metrics['error']);
            }
        }
    }
    
    private function displayCacheMetrics(array $metrics): void
    {
        $this->info("🚀 CACHE METRICS");
        
        if ($metrics['status'] === 'healthy') {
            $this->line("<info>Status: ✅ Healthy</info>");
            $this->line("Write Time: " . round($metrics['write_time_ms'], 2) . "ms");
            $this->line("Read Time:  " . round($metrics['read_time_ms'], 2) . "ms");
            
            // Performance bars
            $this->displayProgressBar("Write", $metrics['write_time_ms'], 10);
            $this->displayProgressBar("Read", $metrics['read_time_ms'], 5);
        } else {
            $this->line("<error>Status: ❌ Error</error>");
            if (isset($metrics['error'])) {
                $this->line("Error: " . $metrics['error']);
            }
        }
    }
    
    private function displayPerformanceTrends(): void
    {
        $this->info("📈 PERFORMANCE TRENDS (Last 10 measurements)");
        
        if (count($this->serviceMetrics) < 2) {
            $this->line("Not enough data for trends...");
            return;
        }
        
        $recent = array_slice($this->serviceMetrics, -10, null, true);
        
        // Memory trend
        $memoryValues = array_column(array_column($recent, 'system'), 'memory_usage');
        $memoryTrend = $this->calculateTrend($memoryValues);
        $this->displayTrend("Memory", $memoryTrend, $this->formatBytes(end($memoryValues)));
        
        // Database performance trend
        $dbTimes = [];
        foreach ($recent as $metrics) {
            if ($metrics['database']['status'] === 'healthy') {
                $dbTimes[] = $metrics['database']['query_time_ms'];
            }
        }
        if (!empty($dbTimes)) {
            $dbTrend = $this->calculateTrend($dbTimes);
            $this->displayTrend("DB Query", $dbTrend, round(end($dbTimes), 2) . "ms");
        }
        
        // Cache performance trend
        $cacheTimes = [];
        foreach ($recent as $metrics) {
            if ($metrics['cache']['status'] === 'healthy') {
                $cacheTimes[] = $metrics['cache']['read_time_ms'];
            }
        }
        if (!empty($cacheTimes)) {
            $cacheTrend = $this->calculateTrend($cacheTimes);
            $this->displayTrend("Cache Read", $cacheTrend, round(end($cacheTimes), 2) . "ms");
        }
    }
    
    private function displayAlerts(array $latest): void
    {
        $this->info("🚨 ALERTS");
        
        $alerts = [];
        
        // Memory alerts
        if ($latest['system']['memory_usage'] > 512 * 1024 * 1024) { // 512MB
            $alerts[] = "High memory usage: " . $this->formatBytes($latest['system']['memory_usage']);
        }
        
        // Database alerts
        if ($latest['database']['status'] === 'error') {
            $alerts[] = "Database connection error";
        } elseif ($latest['database']['query_time_ms'] > 100) {
            $alerts[] = "Slow database queries: " . round($latest['database']['query_time_ms'], 2) . "ms";
        }
        
        // Cache alerts
        if ($latest['cache']['status'] === 'error') {
            $alerts[] = "Cache system error";
        } elseif ($latest['cache']['read_time_ms'] > 20) {
            $alerts[] = "Slow cache reads: " . round($latest['cache']['read_time_ms'], 2) . "ms";
        }
        
        if (empty($alerts)) {
            $this->line("<info>✅ No alerts - All systems operating normally</info>");
        } else {
            foreach ($alerts as $alert) {
                $this->line("<error>⚠️  {$alert}</error>");
            }
        }
    }
    
    private function displayProgressBar(string $label, float $value, float $threshold): void
    {
        $maxWidth = 30;
        $percentage = min(($value / $threshold) * 100, 100);
        $filledWidth = (int) (($percentage / 100) * $maxWidth);
        
        $bar = str_repeat('█', $filledWidth) . str_repeat('░', $maxWidth - $filledWidth);
        
        $color = $percentage > 80 ? 'error' : ($percentage > 60 ? 'comment' : 'info');
        $this->line("<{$color}>{$label}: [{$bar}] " . round($percentage, 1) . "%</{$color}>");
    }
    
    private function displayTrend(string $label, string $trend, string $current): void
    {
        $arrow = match($trend) {
            'up' => '📈',
            'down' => '📉',
            'stable' => '➡️',
            default => '❓'
        };
        
        $this->line("{$arrow} {$label}: {$current} ({$trend})");
    }
    
    private function calculateTrend(array $values): string
    {
        if (count($values) < 2) {
            return 'stable';
        }
        
        $first = array_slice($values, 0, ceil(count($values) / 2));
        $second = array_slice($values, floor(count($values) / 2));
        
        $firstAvg = array_sum($first) / count($first);
        $secondAvg = array_sum($second) / count($second);
        
        $change = (($secondAvg - $firstAvg) / $firstAvg) * 100;
        
        if ($change > 5) {
            return 'up';
        } elseif ($change < -5) {
            return 'down';
        } else {
            return 'stable';
        }
    }
    
    private function generateFinalReport(): void
    {
        $this->info("📋 GENERATING FINAL MONITORING REPORT");
        
        $reportData = [
            'monitoring_duration' => time() - $this->startTime,
            'total_measurements' => count($this->serviceMetrics),
            'metrics_history' => $this->serviceMetrics,
            'summary' => $this->generateSummary(),
            'generated_at' => Carbon::now()->toISOString()
        ];
        
        $reportPath = storage_path('logs/service_monitoring_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));
        
        $this->info("📄 Monitoring report saved to: {$reportPath}");
    }
    
    private function generateSummary(): array
    {
        if (empty($this->serviceMetrics)) {
            return [];
        }
        
        $allMetrics = array_values($this->serviceMetrics);
        
        // Memory statistics
        $memoryValues = array_column(array_column($allMetrics, 'system'), 'memory_usage');
        $memoryStats = [
            'min' => min($memoryValues),
            'max' => max($memoryValues),
            'avg' => array_sum($memoryValues) / count($memoryValues)
        ];
        
        // Database statistics
        $dbTimes = [];
        $dbErrors = 0;
        foreach ($allMetrics as $metrics) {
            if ($metrics['database']['status'] === 'healthy') {
                $dbTimes[] = $metrics['database']['query_time_ms'];
            } else {
                $dbErrors++;
            }
        }
        
        $dbStats = [
            'avg_query_time' => !empty($dbTimes) ? array_sum($dbTimes) / count($dbTimes) : 0,
            'max_query_time' => !empty($dbTimes) ? max($dbTimes) : 0,
            'error_count' => $dbErrors,
            'uptime_percentage' => ((count($allMetrics) - $dbErrors) / count($allMetrics)) * 100
        ];
        
        // Cache statistics
        $cacheTimes = [];
        $cacheErrors = 0;
        foreach ($allMetrics as $metrics) {
            if ($metrics['cache']['status'] === 'healthy') {
                $cacheTimes[] = $metrics['cache']['read_time_ms'];
            } else {
                $cacheErrors++;
            }
        }
        
        $cacheStats = [
            'avg_read_time' => !empty($cacheTimes) ? array_sum($cacheTimes) / count($cacheTimes) : 0,
            'max_read_time' => !empty($cacheTimes) ? max($cacheTimes) : 0,
            'error_count' => $cacheErrors,
            'uptime_percentage' => ((count($allMetrics) - $cacheErrors) / count($allMetrics)) * 100
        ];
        
        return [
            'memory' => $memoryStats,
            'database' => $dbStats,
            'cache' => $cacheStats
        ];
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}