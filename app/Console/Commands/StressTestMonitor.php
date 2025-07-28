<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class StressTestMonitor extends Command
{
    protected $signature = 'stress:monitor 
                            {--interval=5 : Monitoring interval in seconds}
                            {--duration=3600 : Monitoring duration in seconds}
                            {--alerts : Enable alert notifications}';

    protected $description = 'Real-time monitoring dashboard for stress testing';

    private $startTime;
    private $monitoringInterval;
    private $monitoringDuration;
    private $alertsEnabled;
    private $metrics = [];
    private $alerts = [];

    public function handle()
    {
        $this->startTime = time();
        $this->monitoringInterval = $this->option('interval');
        $this->monitoringDuration = $this->option('duration');
        $this->alertsEnabled = $this->option('alerts');

        $this->info("🖥️  LETHAL STRESS TEST MONITORING DASHBOARD ACTIVATED");
        $this->info("Monitoring Interval: {$this->monitoringInterval} seconds");
        $this->info("Monitoring Duration: {$this->monitoringDuration} seconds");
        $this->info("Alerts Enabled: " . ($this->alertsEnabled ? 'YES' : 'NO'));
        $this->line("");

        $this->displayHeader();

        while ((time() - $this->startTime) < $this->monitoringDuration) {
            $this->collectMetrics();
            $this->displayMetrics();
            $this->checkAlerts();
            $this->saveMetricsToCache();
            
            sleep($this->monitoringInterval);
            
            // Clear screen and redisplay (for real-time effect)
            if (function_exists('system')) {
                system('clear');
            }
            $this->displayHeader();
        }

        $this->generateMonitoringReport();
    }

    private function displayHeader()
    {
        $elapsed = time() - $this->startTime;
        $remaining = max(0, $this->monitoringDuration - $elapsed);
        
        $this->info("╔══════════════════════════════════════════════════════════════════════════════╗");
        $this->info("║                    🔥 LETHAL STRESS TEST MONITOR 🔥                          ║");
        $this->info("║                                                                              ║");
        $this->info("║  Elapsed: " . sprintf("%02d:%02d:%02d", floor($elapsed/3600), floor(($elapsed%3600)/60), $elapsed%60) . 
                   "                    Remaining: " . sprintf("%02d:%02d:%02d", floor($remaining/3600), floor(($remaining%3600)/60), $remaining%60) . "                ║");
        $this->info("╚══════════════════════════════════════════════════════════════════════════════╝");
        $this->line("");
    }

    private function collectMetrics()
    {
        $timestamp = time();
        
        // Database metrics
        $dbMetrics = $this->collectDatabaseMetrics();
        
        // Application metrics
        $appMetrics = $this->collectApplicationMetrics();
        
        // System metrics
        $systemMetrics = $this->collectSystemMetrics();
        
        // Business metrics
        $businessMetrics = $this->collectBusinessMetrics();
        
        // Queue metrics
        $queueMetrics = $this->collectQueueMetrics();

        $this->metrics[$timestamp] = [
            'database' => $dbMetrics,
            'application' => $appMetrics,
            'system' => $systemMetrics,
            'business' => $businessMetrics,
            'queue' => $queueMetrics
        ];

        // Keep only last 100 measurements to prevent memory issues
        if (count($this->metrics) > 100) {
            $this->metrics = array_slice($this->metrics, -100, null, true);
        }
    }

    private function collectDatabaseMetrics()
    {
        try {
            // Connection count
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $activeConnections = $connections[0]->Value ?? 0;

            // Query response time test
            $queryStart = microtime(true);
            DB::select("SELECT 1");
            $queryResponseTime = (microtime(true) - $queryStart) * 1000;

            // Get database size
            $dbSize = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $databaseSize = $dbSize[0]->size_mb ?? 0;

            // Get slow queries count
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueryCount = $slowQueries[0]->Value ?? 0;

            // Get lock waits
            $lockWaits = DB::select("SHOW STATUS LIKE 'Table_locks_waited'");
            $lockWaitCount = $lockWaits[0]->Value ?? 0;

            // Buffer pool hit ratio
            $bufferPoolStats = DB::select("
                SHOW STATUS WHERE Variable_name IN (
                    'Innodb_buffer_pool_read_requests',
                    'Innodb_buffer_pool_reads'
                )
            ");
            
            $readRequests = 0;
            $reads = 0;
            foreach ($bufferPoolStats as $stat) {
                if ($stat->Variable_name === 'Innodb_buffer_pool_read_requests') {
                    $readRequests = $stat->Value;
                } elseif ($stat->Variable_name === 'Innodb_buffer_pool_reads') {
                    $reads = $stat->Value;
                }
            }
            
            $bufferPoolHitRatio = $readRequests > 0 ? (($readRequests - $reads) / $readRequests) * 100 : 100;

            return [
                'active_connections' => (int)$activeConnections,
                'query_response_time_ms' => round($queryResponseTime, 2),
                'database_size_mb' => (float)$databaseSize,
                'slow_queries' => (int)$slowQueryCount,
                'lock_waits' => (int)$lockWaitCount,
                'buffer_pool_hit_ratio' => round($bufferPoolHitRatio, 2)
            ];

        } catch (\Exception $e) {
            return [
                'active_connections' => 0,
                'query_response_time_ms' => 999999,
                'database_size_mb' => 0,
                'slow_queries' => 0,
                'lock_waits' => 0,
                'buffer_pool_hit_ratio' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function collectApplicationMetrics()
    {
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->convertToBytes(ini_get('memory_limit'));

        // CPU load average (Unix systems)
        $cpuLoad = 0;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuLoad = $load[0] ?? 0;
        }

        // Active sessions count
        $activeSessions = 0;
        try {
            $activeSessions = DB::table('sessions')->count();
        } catch (\Exception $e) {
            // Sessions table might not exist
        }

        // Cache hit ratio (if using Redis/Memcached)
        $cacheHitRatio = $this->calculateCacheHitRatio();

        return [
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'memory_usage_percent' => round(($memoryUsage / $memoryLimit) * 100, 2),
            'cpu_load' => round($cpuLoad, 2),
            'active_sessions' => $activeSessions,
            'cache_hit_ratio' => $cacheHitRatio
        ];
    }

    private function collectSystemMetrics()
    {
        // Disk usage
        $diskFree = disk_free_space('.');
        $diskTotal = disk_total_space('.');
        $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

        // Process count
        $processCount = 0;
        if (function_exists('shell_exec')) {
            $processCount = (int)shell_exec('ps aux | wc -l');
        }

        // Network connections (if available)
        $networkConnections = 0;
        if (function_exists('shell_exec')) {
            $networkConnections = (int)shell_exec('netstat -an | wc -l');
        }

        return [
            'disk_free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
            'disk_total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
            'disk_usage_percent' => round($diskUsagePercent, 2),
            'process_count' => $processCount,
            'network_connections' => $networkConnections
        ];
    }

    private function collectBusinessMetrics()
    {
        try {
            // User activity
            $activeUsers = DB::table('sessions')
                ->where('last_activity', '>', time() - 300) // Active in last 5 minutes
                ->distinct('user_id')
                ->count('user_id');

            // Quiz activity
            $quizzesGenerated = DB::table('quizzes')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            // Assignment activity
            $assignmentsCreated = DB::table('assignments')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            // Notification activity
            $notificationsSent = DB::table('notifications')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            // Payment activity
            $paymentsProcessed = 0;
            try {
                $paymentsProcessed = DB::table('payments')
                    ->where('created_at', '>', now()->subMinutes(5))
                    ->count();
            } catch (\Exception $e) {
                // Payments table might not exist
            }

            // Error rate
            $totalRequests = $quizzesGenerated + $assignmentsCreated + $notificationsSent + $paymentsProcessed;
            $errorRate = 0; // This would need to be tracked separately in a real implementation

            return [
                'active_users' => $activeUsers,
                'quizzes_generated_5min' => $quizzesGenerated,
                'assignments_created_5min' => $assignmentsCreated,
                'notifications_sent_5min' => $notificationsSent,
                'payments_processed_5min' => $paymentsProcessed,
                'total_requests_5min' => $totalRequests,
                'error_rate_percent' => $errorRate
            ];

        } catch (\Exception $e) {
            return [
                'active_users' => 0,
                'quizzes_generated_5min' => 0,
                'assignments_created_5min' => 0,
                'notifications_sent_5min' => 0,
                'payments_processed_5min' => 0,
                'total_requests_5min' => 0,
                'error_rate_percent' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function collectQueueMetrics()
    {
        try {
            // Queue size (pending jobs)
            $queueSize = Queue::size();

            // Failed jobs count
            $failedJobs = DB::table('failed_jobs')->count();

            // Jobs processed in last 5 minutes
            $recentJobs = DB::table('jobs')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            return [
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
                'jobs_processed_5min' => $recentJobs
            ];

        } catch (\Exception $e) {
            return [
                'queue_size' => 0,
                'failed_jobs' => 0,
                'jobs_processed_5min' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function displayMetrics()
    {
        if (empty($this->metrics)) {
            return;
        }

        $latest = end($this->metrics);
        
        // Database Metrics
        $this->info("📊 DATABASE METRICS");
        $this->line("├─ Active Connections: " . $this->formatMetric($latest['database']['active_connections'], 'connections'));
        $this->line("├─ Query Response Time: " . $this->formatMetric($latest['database']['query_response_time_ms'], 'ms', 200, 500));
        $this->line("├─ Database Size: " . $this->formatMetric($latest['database']['database_size_mb'], 'MB'));
        $this->line("├─ Slow Queries: " . $this->formatMetric($latest['database']['slow_queries'], 'queries'));
        $this->line("├─ Lock Waits: " . $this->formatMetric($latest['database']['lock_waits'], 'waits'));
        $this->line("└─ Buffer Pool Hit Ratio: " . $this->formatMetric($latest['database']['buffer_pool_hit_ratio'], '%', 95, 90));
        $this->line("");

        // Application Metrics
        $this->info("🖥️  APPLICATION METRICS");
        $this->line("├─ Memory Usage: " . $this->formatMetric($latest['application']['memory_usage_percent'], '%', 80, 95));
        $this->line("├─ Memory Peak: " . $this->formatMetric($latest['application']['memory_peak_mb'], 'MB'));
        $this->line("├─ CPU Load: " . $this->formatMetric($latest['application']['cpu_load'], '', 2, 4));
        $this->line("├─ Active Sessions: " . $this->formatMetric($latest['application']['active_sessions'], 'sessions'));
        $this->line("└─ Cache Hit Ratio: " . $this->formatMetric($latest['application']['cache_hit_ratio'], '%', 90, 80));
        $this->line("");

        // System Metrics
        $this->info("⚙️  SYSTEM METRICS");
        $this->line("├─ Disk Usage: " . $this->formatMetric($latest['system']['disk_usage_percent'], '%', 80, 95));
        $this->line("├─ Disk Free: " . $this->formatMetric($latest['system']['disk_free_gb'], 'GB'));
        $this->line("├─ Process Count: " . $this->formatMetric($latest['system']['process_count'], 'processes'));
        $this->line("└─ Network Connections: " . $this->formatMetric($latest['system']['network_connections'], 'connections'));
        $this->line("");

        // Business Metrics
        $this->info("💼 BUSINESS METRICS");
        $this->line("├─ Active Users: " . $this->formatMetric($latest['business']['active_users'], 'users'));
        $this->line("├─ Quizzes Generated (5min): " . $this->formatMetric($latest['business']['quizzes_generated_5min'], 'quizzes'));
        $this->line("├─ Assignments Created (5min): " . $this->formatMetric($latest['business']['assignments_created_5min'], 'assignments'));
        $this->line("├─ Notifications Sent (5min): " . $this->formatMetric($latest['business']['notifications_sent_5min'], 'notifications'));
        $this->line("├─ Payments Processed (5min): " . $this->formatMetric($latest['business']['payments_processed_5min'], 'payments'));
        $this->line("└─ Error Rate: " . $this->formatMetric($latest['business']['error_rate_percent'], '%', 1, 5));
        $this->line("");

        // Queue Metrics
        $this->info("🔄 QUEUE METRICS");
        $this->line("├─ Queue Size: " . $this->formatMetric($latest['queue']['queue_size'], 'jobs'));
        $this->line("├─ Failed Jobs: " . $this->formatMetric($latest['queue']['failed_jobs'], 'jobs'));
        $this->line("└─ Jobs Processed (5min): " . $this->formatMetric($latest['queue']['jobs_processed_5min'], 'jobs'));
        $this->line("");

        // Display alerts if any
        if (!empty($this->alerts)) {
            $this->error("🚨 ACTIVE ALERTS:");
            foreach (array_slice($this->alerts, -5) as $alert) {
                $this->line("   ⚠️  {$alert}");
            }
            $this->line("");
        }
    }

    private function formatMetric($value, $unit, $warningThreshold = null, $criticalThreshold = null)
    {
        $formatted = $value . ($unit ? " {$unit}" : "");
        
        if ($warningThreshold !== null && $criticalThreshold !== null) {
            if ($value >= $criticalThreshold) {
                return "<fg=red>{$formatted}</>";
            } elseif ($value >= $warningThreshold) {
                return "<fg=yellow>{$formatted}</>";
            } else {
                return "<fg=green>{$formatted}</>";
            }
        }
        
        return $formatted;
    }

    private function checkAlerts()
    {
        if (!$this->alertsEnabled || empty($this->metrics)) {
            return;
        }

        $latest = end($this->metrics);
        $timestamp = date('H:i:s');

        // Database alerts
        if ($latest['database']['query_response_time_ms'] > 500) {
            $this->alerts[] = "[{$timestamp}] CRITICAL: Database response time > 500ms ({$latest['database']['query_response_time_ms']}ms)";
        }

        if ($latest['database']['active_connections'] > 100) {
            $this->alerts[] = "[{$timestamp}] WARNING: High database connections ({$latest['database']['active_connections']})";
        }

        // Application alerts
        if ($latest['application']['memory_usage_percent'] > 95) {
            $this->alerts[] = "[{$timestamp}] CRITICAL: Memory usage > 95% ({$latest['application']['memory_usage_percent']}%)";
        }

        if ($latest['application']['cpu_load'] > 4) {
            $this->alerts[] = "[{$timestamp}] CRITICAL: High CPU load ({$latest['application']['cpu_load']})";
        }

        // System alerts
        if ($latest['system']['disk_usage_percent'] > 95) {
            $this->alerts[] = "[{$timestamp}] CRITICAL: Disk usage > 95% ({$latest['system']['disk_usage_percent']}%)";
        }

        // Business alerts
        if ($latest['business']['error_rate_percent'] > 5) {
            $this->alerts[] = "[{$timestamp}] CRITICAL: Error rate > 5% ({$latest['business']['error_rate_percent']}%)";
        }

        // Keep only last 50 alerts
        if (count($this->alerts) > 50) {
            $this->alerts = array_slice($this->alerts, -50);
        }
    }

    private function calculateCacheHitRatio()
    {
        // This is a simplified implementation
        // In a real scenario, you'd track cache hits/misses
        try {
            // Test cache performance
            $key = 'stress_test_cache_' . uniqid();
            $value = 'test_value';
            
            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            
            return $retrieved === $value ? 100 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function saveMetricsToCache()
    {
        try {
            Cache::put('stress_test_metrics', $this->metrics, 3600);
            Cache::put('stress_test_alerts', $this->alerts, 3600);
        } catch (\Exception $e) {
            // Cache might not be available
        }
    }

    private function generateMonitoringReport()
    {
        $this->info("📄 GENERATING MONITORING REPORT");
        
        $reportData = [
            'monitoring_duration' => $this->monitoringDuration,
            'total_measurements' => count($this->metrics),
            'alerts_generated' => count($this->alerts),
            'metrics' => $this->metrics,
            'alerts' => $this->alerts,
            'summary' => $this->generateSummary()
        ];

        $reportPath = storage_path('logs/stress_test_monitoring_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info("📄 Monitoring report saved to: {$reportPath}");
        
        // Display summary
        $summary = $reportData['summary'];
        $this->info("📊 MONITORING SUMMARY:");
        $this->line("├─ Average DB Response Time: {$summary['avg_db_response_time']}ms");
        $this->line("├─ Peak Memory Usage: {$summary['peak_memory_usage']}%");
        $this->line("├─ Max Active Connections: {$summary['max_active_connections']}");
        $this->line("├─ Total Alerts: {$summary['total_alerts']}");
        $this->line("└─ System Stability: {$summary['stability_rating']}");
    }

    private function generateSummary()
    {
        if (empty($this->metrics)) {
            return [];
        }

        $dbResponseTimes = [];
        $memoryUsages = [];
        $activeConnections = [];

        foreach ($this->metrics as $metric) {
            $dbResponseTimes[] = $metric['database']['query_response_time_ms'];
            $memoryUsages[] = $metric['application']['memory_usage_percent'];
            $activeConnections[] = $metric['database']['active_connections'];
        }

        $avgDbResponseTime = array_sum($dbResponseTimes) / count($dbResponseTimes);
        $peakMemoryUsage = max($memoryUsages);
        $maxActiveConnections = max($activeConnections);
        $totalAlerts = count($this->alerts);

        // Calculate stability rating
        $stabilityRating = 'EXCELLENT';
        if ($totalAlerts > 10 || $avgDbResponseTime > 200 || $peakMemoryUsage > 90) {
            $stabilityRating = 'POOR';
        } elseif ($totalAlerts > 5 || $avgDbResponseTime > 100 || $peakMemoryUsage > 80) {
            $stabilityRating = 'FAIR';
        } elseif ($totalAlerts > 0 || $avgDbResponseTime > 50 || $peakMemoryUsage > 70) {
            $stabilityRating = 'GOOD';
        }

        return [
            'avg_db_response_time' => round($avgDbResponseTime, 2),
            'peak_memory_usage' => round($peakMemoryUsage, 2),
            'max_active_connections' => $maxActiveConnections,
            'total_alerts' => $totalAlerts,
            'stability_rating' => $stabilityRating
        ];
    }

    private function convertToBytes($value)
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }
}