<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PerformanceAnalysisEngine extends Command
{
    protected $signature = 'stress:analyze 
                            {--report-path= : Path to stress test report file}
                            {--monitoring-path= : Path to monitoring report file}
                            {--output=console : Output format (console|json|html|pdf)}
                            {--detailed : Include detailed analysis}
                            {--recommendations : Generate optimization recommendations}';

    protected $description = 'Analyze stress test results and generate comprehensive performance reports';

    private $analysisData = [];
    private $recommendations = [];
    private $bottlenecks = [];
    private $performanceMetrics = [];

    public function handle()
    {
        $this->info("📊 LETHAL PERFORMANCE ANALYSIS ENGINE ACTIVATED");
        
        $reportPath = $this->option('report-path');
        $monitoringPath = $this->option('monitoring-path');
        $outputFormat = $this->option('output');
        $detailed = $this->option('detailed');
        $generateRecommendations = $this->option('recommendations');

        // Load test data
        $this->loadTestData($reportPath, $monitoringPath);
        
        // Perform analysis
        $this->analyzePerformanceMetrics();
        $this->identifyBottlenecks();
        $this->calculateCapacityMetrics();
        $this->analyzeFailurePatterns();
        $this->assessSystemStability();
        
        if ($generateRecommendations) {
            $this->generateOptimizationRecommendations();
        }
        
        // Generate output
        $this->generateOutput($outputFormat, $detailed);
        
        $this->info("✅ PERFORMANCE ANALYSIS COMPLETED");
    }

    private function loadTestData($reportPath, $monitoringPath)
    {
        $this->info("📂 Loading test data...");
        
        // Load stress test report
        if ($reportPath && file_exists($reportPath)) {
            $stressData = json_decode(file_get_contents($reportPath), true);
            $this->analysisData['stress_test'] = $stressData;
            $this->info("   Loaded stress test data from: {$reportPath}");
        } else {
            // Load latest stress test report
            $latestReport = $this->findLatestReport('stress_test_report_*.json');
            if ($latestReport) {
                $stressData = json_decode(file_get_contents($latestReport), true);
                $this->analysisData['stress_test'] = $stressData;
                $this->info("   Loaded latest stress test data: " . basename($latestReport));
            }
        }
        
        // Load monitoring report
        if ($monitoringPath && file_exists($monitoringPath)) {
            $monitoringData = json_decode(file_get_contents($monitoringPath), true);
            $this->analysisData['monitoring'] = $monitoringData;
            $this->info("   Loaded monitoring data from: {$monitoringPath}");
        } else {
            // Load latest monitoring report
            $latestMonitoring = $this->findLatestReport('stress_test_monitoring_*.json');
            if ($latestMonitoring) {
                $monitoringData = json_decode(file_get_contents($latestMonitoring), true);
                $this->analysisData['monitoring'] = $monitoringData;
                $this->info("   Loaded latest monitoring data: " . basename($latestMonitoring));
            }
        }
        
        // Load current database statistics
        $this->loadCurrentDatabaseStats();
        
        // Load system information
        $this->loadSystemInformation();
    }

    private function findLatestReport($pattern)
    {
        $files = glob(storage_path("logs/{$pattern}"));
        if (empty($files)) {
            return null;
        }
        
        // Sort by modification time, newest first
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return $files[0];
    }

    private function loadCurrentDatabaseStats()
    {
        $this->info("   Loading current database statistics...");
        
        try {
            // Database size
            $dbSize = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    COUNT(*) as table_count
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            
            // Connection statistics
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            
            // Query cache statistics
            $queryCache = DB::select("
                SHOW STATUS WHERE Variable_name IN (
                    'Qcache_hits', 'Qcache_inserts', 'Qcache_not_cached'
                )
            ");
            
            // InnoDB statistics
            $innodbStats = DB::select("
                SHOW STATUS WHERE Variable_name IN (
                    'Innodb_buffer_pool_read_requests',
                    'Innodb_buffer_pool_reads',
                    'Innodb_rows_read',
                    'Innodb_rows_inserted',
                    'Innodb_rows_updated',
                    'Innodb_rows_deleted'
                )
            ");
            
            $this->analysisData['current_db_stats'] = [
                'database_size' => $dbSize[0] ?? null,
                'connections' => [
                    'current' => $connections[0]->Value ?? 0,
                    'max' => $maxConnections[0]->Value ?? 0
                ],
                'query_cache' => $queryCache,
                'innodb_stats' => $innodbStats
            ];
            
        } catch (\Exception $e) {
            $this->warn("   Could not load database statistics: " . $e->getMessage());
        }
    }

    private function loadSystemInformation()
    {
        $this->info("   Loading system information...");
        
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
            'extensions' => get_loaded_extensions(),
        ];
        
        // System load (Unix systems)
        if (function_exists('sys_getloadavg')) {
            $systemInfo['load_average'] = sys_getloadavg();
        }
        
        // Disk space
        $systemInfo['disk_space'] = [
            'free' => disk_free_space('.'),
            'total' => disk_total_space('.')
        ];
        
        $this->analysisData['system_info'] = $systemInfo;
    }

    private function analyzePerformanceMetrics()
    {
        $this->info("📈 Analyzing performance metrics...");
        
        $metrics = [];
        
        // Analyze stress test metrics
        if (isset($this->analysisData['stress_test']['metrics'])) {
            $stressMetrics = $this->analysisData['stress_test']['metrics'];
            
            // Database performance
            if (isset($stressMetrics['database'])) {
                $dbMetrics = $stressMetrics['database'];
                
                $metrics['database'] = [
                    'avg_response_time' => $this->calculateAverage($dbMetrics['query_response_times'] ?? []),
                    'max_response_time' => max($dbMetrics['query_response_times'] ?? [0]),
                    'min_response_time' => min($dbMetrics['query_response_times'] ?? [0]),
                    'response_time_95th' => $this->calculatePercentile($dbMetrics['query_response_times'] ?? [], 95),
                    'max_connections' => $dbMetrics['active_connections'] ?? 0,
                    'queries_per_second' => $dbMetrics['queries_per_second'] ?? 0,
                    'deadlocks' => $dbMetrics['deadlocks'] ?? 0,
                    'rollbacks' => $dbMetrics['rollbacks'] ?? 0
                ];
            }
            
            // Application performance
            if (isset($stressMetrics['application'])) {
                $appMetrics = $stressMetrics['application'];
                
                $metrics['application'] = [
                    'avg_memory_usage' => $appMetrics['memory_usage'] ?? 0,
                    'peak_memory_usage' => max([$appMetrics['memory_usage']] ?? [0]),
                    'cpu_utilization' => $appMetrics['cpu_utilization'] ?? 0,
                    'active_sessions' => $appMetrics['active_sessions'] ?? 0,
                    'error_rate' => $appMetrics['error_rate'] ?? 0
                ];
            }
        }
        
        // Analyze monitoring data
        if (isset($this->analysisData['monitoring']['metrics'])) {
            $monitoringMetrics = $this->analysisData['monitoring']['metrics'];
            
            $timeSeriesData = [];
            foreach ($monitoringMetrics as $timestamp => $data) {
                $timeSeriesData[] = [
                    'timestamp' => $timestamp,
                    'db_response_time' => $data['database']['query_response_time_ms'] ?? 0,
                    'memory_usage' => $data['application']['memory_usage_percent'] ?? 0,
                    'active_connections' => $data['database']['active_connections'] ?? 0,
                    'cpu_load' => $data['application']['cpu_load'] ?? 0
                ];
            }
            
            $metrics['time_series'] = $timeSeriesData;
            $metrics['monitoring_duration'] = count($timeSeriesData) * 5; // 5-second intervals
        }
        
        $this->performanceMetrics = $metrics;
    }

    private function identifyBottlenecks()
    {
        $this->info("🔍 Identifying performance bottlenecks...");
        
        $bottlenecks = [];
        
        // Database bottlenecks
        if (isset($this->performanceMetrics['database'])) {
            $db = $this->performanceMetrics['database'];
            
            if ($db['avg_response_time'] > 100) {
                $bottlenecks[] = [
                    'type' => 'database',
                    'category' => 'response_time',
                    'severity' => $db['avg_response_time'] > 500 ? 'critical' : 'warning',
                    'description' => "High database response time: {$db['avg_response_time']}ms average",
                    'impact' => 'User experience degradation, potential timeouts',
                    'threshold' => 100,
                    'current_value' => $db['avg_response_time']
                ];
            }
            
            if ($db['max_connections'] > 80) {
                $bottlenecks[] = [
                    'type' => 'database',
                    'category' => 'connections',
                    'severity' => $db['max_connections'] > 150 ? 'critical' : 'warning',
                    'description' => "High database connection usage: {$db['max_connections']} connections",
                    'impact' => 'Connection pool exhaustion risk',
                    'threshold' => 80,
                    'current_value' => $db['max_connections']
                ];
            }
            
            if ($db['deadlocks'] > 0) {
                $bottlenecks[] = [
                    'type' => 'database',
                    'category' => 'deadlocks',
                    'severity' => $db['deadlocks'] > 10 ? 'critical' : 'warning',
                    'description' => "Database deadlocks detected: {$db['deadlocks']} occurrences",
                    'impact' => 'Transaction failures, data consistency issues',
                    'threshold' => 0,
                    'current_value' => $db['deadlocks']
                ];
            }
        }
        
        // Application bottlenecks
        if (isset($this->performanceMetrics['application'])) {
            $app = $this->performanceMetrics['application'];
            
            if ($app['peak_memory_usage'] > 80) {
                $bottlenecks[] = [
                    'type' => 'application',
                    'category' => 'memory',
                    'severity' => $app['peak_memory_usage'] > 95 ? 'critical' : 'warning',
                    'description' => "High memory usage: {$app['peak_memory_usage']}% of limit",
                    'impact' => 'Memory exhaustion risk, potential crashes',
                    'threshold' => 80,
                    'current_value' => $app['peak_memory_usage']
                ];
            }
            
            if ($app['cpu_utilization'] > 70) {
                $bottlenecks[] = [
                    'type' => 'application',
                    'category' => 'cpu',
                    'severity' => $app['cpu_utilization'] > 90 ? 'critical' : 'warning',
                    'description' => "High CPU utilization: {$app['cpu_utilization']}%",
                    'impact' => 'Response time degradation, system instability',
                    'threshold' => 70,
                    'current_value' => $app['cpu_utilization']
                ];
            }
            
            if ($app['error_rate'] > 1) {
                $bottlenecks[] = [
                    'type' => 'application',
                    'category' => 'errors',
                    'severity' => $app['error_rate'] > 5 ? 'critical' : 'warning',
                    'description' => "High error rate: {$app['error_rate']}%",
                    'impact' => 'User experience issues, data integrity concerns',
                    'threshold' => 1,
                    'current_value' => $app['error_rate']
                ];
            }
        }
        
        // Time series analysis for trend bottlenecks
        if (isset($this->performanceMetrics['time_series'])) {
            $trends = $this->analyzeTrends($this->performanceMetrics['time_series']);
            
            foreach ($trends as $trend) {
                if ($trend['slope'] > 0.1) { // Increasing trend
                    $bottlenecks[] = [
                        'type' => 'trend',
                        'category' => $trend['metric'],
                        'severity' => $trend['slope'] > 0.5 ? 'critical' : 'warning',
                        'description' => "Increasing trend in {$trend['metric']}: {$trend['slope']} per minute",
                        'impact' => 'Performance degradation over time',
                        'threshold' => 0.1,
                        'current_value' => $trend['slope']
                    ];
                }
            }
        }
        
        // Sort bottlenecks by severity
        usort($bottlenecks, function($a, $b) {
            $severityOrder = ['critical' => 3, 'warning' => 2, 'info' => 1];
            return $severityOrder[$b['severity']] - $severityOrder[$a['severity']];
        });
        
        $this->bottlenecks = $bottlenecks;
    }

    private function calculateCapacityMetrics()
    {
        $this->info("📏 Calculating capacity metrics...");
        
        $capacity = [];
        
        // Database capacity
        if (isset($this->performanceMetrics['database'])) {
            $db = $this->performanceMetrics['database'];
            
            // Estimate maximum concurrent users based on response time
            $maxUsers = $this->estimateMaxUsers($db['avg_response_time']);
            
            // Connection pool capacity
            $connectionCapacity = $this->estimateConnectionCapacity($db['max_connections']);
            
            $capacity['database'] = [
                'estimated_max_users' => $maxUsers,
                'connection_pool_capacity' => $connectionCapacity,
                'query_throughput_capacity' => $db['queries_per_second'] * 1.2, // 20% buffer
                'recommended_connections' => max(100, $db['max_connections'] * 1.5)
            ];
        }
        
        // Application capacity
        if (isset($this->performanceMetrics['application'])) {
            $app = $this->performanceMetrics['application'];
            
            // Memory capacity
            $memoryCapacity = $this->estimateMemoryCapacity($app['peak_memory_usage']);
            
            // CPU capacity
            $cpuCapacity = $this->estimateCPUCapacity($app['cpu_utilization']);
            
            $capacity['application'] = [
                'memory_capacity_users' => $memoryCapacity,
                'cpu_capacity_users' => $cpuCapacity,
                'recommended_memory_gb' => max(4, ($app['peak_memory_usage'] / 100) * 8 * 1.5), // 50% buffer
                'recommended_cpu_cores' => max(2, ceil($app['cpu_utilization'] / 70))
            ];
        }
        
        // Overall system capacity
        $capacity['overall'] = [
            'current_capacity_users' => min(
                $capacity['database']['estimated_max_users'] ?? 1000,
                $capacity['application']['memory_capacity_users'] ?? 1000,
                $capacity['application']['cpu_capacity_users'] ?? 1000
            ),
            'recommended_capacity_users' => max(
                $capacity['database']['estimated_max_users'] ?? 1000,
                $capacity['application']['memory_capacity_users'] ?? 1000,
                $capacity['application']['cpu_capacity_users'] ?? 1000
            ) * 1.3 // 30% buffer
        ];
        
        $this->performanceMetrics['capacity'] = $capacity;
    }

    private function analyzeFailurePatterns()
    {
        $this->info("🔍 Analyzing failure patterns...");
        
        $failureAnalysis = [];
        
        // Analyze monitoring alerts
        if (isset($this->analysisData['monitoring']['alerts'])) {
            $alerts = $this->analysisData['monitoring']['alerts'];
            
            $alertTypes = [];
            $alertFrequency = [];
            
            foreach ($alerts as $alert) {
                // Extract alert type from message
                if (preg_match('/(\w+):\s*(.+)/', $alert, $matches)) {
                    $type = $matches[1];
                    $alertTypes[] = $type;
                    
                    // Extract timestamp
                    if (preg_match('/\[(\d{2}:\d{2}:\d{2})\]/', $alert, $timeMatches)) {
                        $time = $timeMatches[1];
                        $alertFrequency[$time] = ($alertFrequency[$time] ?? 0) + 1;
                    }
                }
            }
            
            $failureAnalysis['alerts'] = [
                'total_alerts' => count($alerts),
                'alert_types' => array_count_values($alertTypes),
                'alert_frequency' => $alertFrequency,
                'most_common_alert' => !empty($alertTypes) ? array_keys(array_count_values($alertTypes))[0] : null
            ];
        }
        
        // Analyze failure injection results
        if (isset($this->analysisData['failure_injection'])) {
            $failures = $this->analysisData['failure_injection']['failures'] ?? [];
            
            $failureTypes = array_column($failures, 'type');
            $failureStatuses = array_column($failures, 'status');
            $executionTimes = array_column($failures, 'execution_time');
            
            $failureAnalysis['failure_injection'] = [
                'total_failures' => count($failures),
                'failure_types' => array_count_values($failureTypes),
                'success_rate' => count(array_filter($failureStatuses, fn($s) => $s === 'completed')) / max(1, count($failures)) * 100,
                'avg_execution_time' => array_sum($executionTimes) / max(1, count($executionTimes)),
                'most_problematic_failure' => $this->findMostProblematicFailure($failures)
            ];
        }
        
        $this->performanceMetrics['failure_analysis'] = $failureAnalysis;
    }

    private function assessSystemStability()
    {
        $this->info("⚖️ Assessing system stability...");
        
        $stability = [
            'overall_score' => 0,
            'factors' => [],
            'rating' => 'UNKNOWN'
        ];
        
        $scores = [];
        
        // Database stability
        if (isset($this->performanceMetrics['database'])) {
            $db = $this->performanceMetrics['database'];
            
            $dbScore = 100;
            if ($db['avg_response_time'] > 200) $dbScore -= 30;
            if ($db['avg_response_time'] > 500) $dbScore -= 30;
            if ($db['deadlocks'] > 0) $dbScore -= 20;
            if ($db['rollbacks'] > 10) $dbScore -= 10;
            
            $scores['database'] = max(0, $dbScore);
            $stability['factors']['database'] = [
                'score' => $scores['database'],
                'issues' => $this->getDatabaseStabilityIssues($db)
            ];
        }
        
        // Application stability
        if (isset($this->performanceMetrics['application'])) {
            $app = $this->performanceMetrics['application'];
            
            $appScore = 100;
            if ($app['peak_memory_usage'] > 80) $appScore -= 20;
            if ($app['peak_memory_usage'] > 95) $appScore -= 30;
            if ($app['cpu_utilization'] > 70) $appScore -= 15;
            if ($app['cpu_utilization'] > 90) $appScore -= 25;
            if ($app['error_rate'] > 1) $appScore -= 20;
            if ($app['error_rate'] > 5) $appScore -= 30;
            
            $scores['application'] = max(0, $appScore);
            $stability['factors']['application'] = [
                'score' => $scores['application'],
                'issues' => $this->getApplicationStabilityIssues($app)
            ];
        }
        
        // Alert frequency impact
        if (isset($this->performanceMetrics['failure_analysis']['alerts'])) {
            $alerts = $this->performanceMetrics['failure_analysis']['alerts'];
            
            $alertScore = 100;
            if ($alerts['total_alerts'] > 10) $alertScore -= 20;
            if ($alerts['total_alerts'] > 50) $alertScore -= 30;
            if ($alerts['total_alerts'] > 100) $alertScore -= 40;
            
            $scores['alerts'] = max(0, $alertScore);
            $stability['factors']['alerts'] = [
                'score' => $scores['alerts'],
                'total_alerts' => $alerts['total_alerts']
            ];
        }
        
        // Calculate overall score
        if (!empty($scores)) {
            $stability['overall_score'] = array_sum($scores) / count($scores);
        }
        
        // Determine rating
        if ($stability['overall_score'] >= 90) {
            $stability['rating'] = 'EXCELLENT';
        } elseif ($stability['overall_score'] >= 80) {
            $stability['rating'] = 'GOOD';
        } elseif ($stability['overall_score'] >= 70) {
            $stability['rating'] = 'FAIR';
        } elseif ($stability['overall_score'] >= 60) {
            $stability['rating'] = 'POOR';
        } else {
            $stability['rating'] = 'CRITICAL';
        }
        
        $this->performanceMetrics['stability'] = $stability;
    }

    private function generateOptimizationRecommendations()
    {
        $this->info("💡 Generating optimization recommendations...");
        
        $recommendations = [];
        
        // Database recommendations
        foreach ($this->bottlenecks as $bottleneck) {
            if ($bottleneck['type'] === 'database') {
                switch ($bottleneck['category']) {
                    case 'response_time':
                        $recommendations[] = [
                            'category' => 'Database Performance',
                            'priority' => $bottleneck['severity'] === 'critical' ? 'HIGH' : 'MEDIUM',
                            'title' => 'Optimize Database Query Performance',
                            'description' => 'High database response times detected',
                            'actions' => [
                                'Add database indexes for frequently queried columns',
                                'Implement query result caching (Redis/Memcached)',
                                'Optimize slow queries identified in the slow query log',
                                'Consider database connection pooling',
                                'Implement read replicas for read-heavy operations'
                            ],
                            'expected_impact' => 'Reduce response time by 30-60%',
                            'implementation_effort' => 'Medium'
                        ];
                        break;
                        
                    case 'connections':
                        $recommendations[] = [
                            'category' => 'Database Connections',
                            'priority' => 'HIGH',
                            'title' => 'Optimize Database Connection Management',
                            'description' => 'High database connection usage detected',
                            'actions' => [
                                'Implement connection pooling with pgBouncer or similar',
                                'Increase max_connections setting if hardware allows',
                                'Optimize application connection lifecycle',
                                'Implement connection timeout settings',
                                'Monitor for connection leaks'
                            ],
                            'expected_impact' => 'Support 2-3x more concurrent users',
                            'implementation_effort' => 'Medium'
                        ];
                        break;
                        
                    case 'deadlocks':
                        $recommendations[] = [
                            'category' => 'Database Concurrency',
                            'priority' => 'HIGH',
                            'title' => 'Resolve Database Deadlock Issues',
                            'description' => 'Database deadlocks detected during stress testing',
                            'actions' => [
                                'Analyze and optimize transaction ordering',
                                'Reduce transaction duration and scope',
                                'Implement proper locking strategies',
                                'Add deadlock retry logic in application',
                                'Consider using optimistic locking where appropriate'
                            ],
                            'expected_impact' => 'Eliminate transaction failures',
                            'implementation_effort' => 'High'
                        ];
                        break;
                }
            }
        }
        
        // Application recommendations
        foreach ($this->bottlenecks as $bottleneck) {
            if ($bottleneck['type'] === 'application') {
                switch ($bottleneck['category']) {
                    case 'memory':
                        $recommendations[] = [
                            'category' => 'Application Memory',
                            'priority' => $bottleneck['severity'] === 'critical' ? 'HIGH' : 'MEDIUM',
                            'title' => 'Optimize Memory Usage',
                            'description' => 'High memory usage detected',
                            'actions' => [
                                'Implement object pooling for frequently created objects',
                                'Add memory-efficient data structures',
                                'Implement pagination for large data sets',
                                'Add garbage collection optimization',
                                'Consider increasing server memory'
                            ],
                            'expected_impact' => 'Reduce memory usage by 20-40%',
                            'implementation_effort' => 'Medium'
                        ];
                        break;
                        
                    case 'cpu':
                        $recommendations[] = [
                            'category' => 'Application Performance',
                            'priority' => 'MEDIUM',
                            'title' => 'Optimize CPU Usage',
                            'description' => 'High CPU utilization detected',
                            'actions' => [
                                'Profile and optimize CPU-intensive operations',
                                'Implement asynchronous processing for heavy tasks',
                                'Add caching for computation-heavy operations',
                                'Consider horizontal scaling',
                                'Optimize algorithms and data structures'
                            ],
                            'expected_impact' => 'Reduce CPU usage by 15-30%',
                            'implementation_effort' => 'Medium'
                        ];
                        break;
                }
            }
        }
        
        // Infrastructure recommendations
        if (isset($this->performanceMetrics['capacity'])) {
            $capacity = $this->performanceMetrics['capacity'];
            
            $recommendations[] = [
                'category' => 'Infrastructure Scaling',
                'priority' => 'MEDIUM',
                'title' => 'Scale Infrastructure for Growth',
                'description' => 'Based on capacity analysis',
                'actions' => [
                    "Increase database connections to " . (isset($capacity['database']['recommended_connections']) ? $capacity['database']['recommended_connections'] : 'N/A'),
                    "Scale memory to " . (isset($capacity['application']['recommended_memory_gb']) ? $capacity['application']['recommended_memory_gb'] : 'N/A') . "GB",
                    "Add " . (isset($capacity['application']['recommended_cpu_cores']) ? $capacity['application']['recommended_cpu_cores'] : 'N/A') . " CPU cores",
                    'Implement load balancing for horizontal scaling',
                    'Set up auto-scaling based on metrics'
                ],
                'expected_impact' => "Support up to " . (isset($capacity['overall']['recommended_capacity_users']) ? $capacity['overall']['recommended_capacity_users'] : 'N/A') . " concurrent users",
                'implementation_effort' => 'High'
            ];
        }
        
        // Monitoring recommendations
        $recommendations[] = [
            'category' => 'Monitoring & Alerting',
            'priority' => 'MEDIUM',
            'title' => 'Enhance Monitoring and Alerting',
            'description' => 'Improve system observability',
            'actions' => [
                'Implement comprehensive application performance monitoring (APM)',
                'Set up proactive alerting for key metrics',
                'Add business metric monitoring',
                'Implement distributed tracing',
                'Create performance dashboards'
            ],
            'expected_impact' => 'Faster issue detection and resolution',
            'implementation_effort' => 'Medium'
        ];
        
        // Sort recommendations by priority
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1];
            return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
        });
        
        $this->recommendations = $recommendations;
    }

    private function generateOutput($format, $detailed)
    {
        switch ($format) {
            case 'json':
                $this->generateJSONOutput($detailed);
                break;
            case 'html':
                $this->generateHTMLOutput($detailed);
                break;
            case 'console':
            default:
                $this->generateConsoleOutput($detailed);
                break;
        }
    }

    private function generateConsoleOutput($detailed)
    {
        $this->info("📊 PERFORMANCE ANALYSIS REPORT");
        $this->line("Generated: " . now()->format('Y-m-d H:i:s'));
        $this->line("");
        
        // System Stability
        if (isset($this->performanceMetrics['stability'])) {
            $stability = $this->performanceMetrics['stability'];
            $this->info("⚖️ SYSTEM STABILITY: {$stability['rating']} ({$stability['overall_score']}/100)");
            $this->line("");
        }
        
        // Performance Summary
        $this->info("📈 PERFORMANCE SUMMARY");
        if (isset($this->performanceMetrics['database'])) {
            $db = $this->performanceMetrics['database'];
            $this->line("├─ Database Response Time: {$db['avg_response_time']}ms (95th: {$db['response_time_95th']}ms)");
            $this->line("├─ Max Database Connections: {$db['max_connections']}");
            $this->line("├─ Database Deadlocks: {$db['deadlocks']}");
        }
        
        if (isset($this->performanceMetrics['application'])) {
            $app = $this->performanceMetrics['application'];
            $this->line("├─ Peak Memory Usage: {$app['peak_memory_usage']}%");
            $this->line("├─ CPU Utilization: {$app['cpu_utilization']}%");
            $this->line("└─ Error Rate: {$app['error_rate']}%");
        }
        $this->line("");
        
        // Bottlenecks
        if (!empty($this->bottlenecks)) {
            $this->error("🚨 CRITICAL BOTTLENECKS IDENTIFIED");
            foreach (array_slice($this->bottlenecks, 0, 5) as $bottleneck) {
                $icon = $bottleneck['severity'] === 'critical' ? '🔴' : '🟡';
                $this->line("{$icon} {$bottleneck['description']}");
                $this->line("   Impact: {$bottleneck['impact']}");
            }
            $this->line("");
        }
        
        // Capacity Analysis
        if (isset($this->performanceMetrics['capacity']['overall'])) {
            $capacity = $this->performanceMetrics['capacity']['overall'];
            $this->info("📏 CAPACITY ANALYSIS");
            $this->line("├─ Current Capacity: {$capacity['current_capacity_users']} concurrent users");
            $this->line("└─ Recommended Capacity: {$capacity['recommended_capacity_users']} concurrent users");
            $this->line("");
        }
        
        // Top Recommendations
        if (!empty($this->recommendations)) {
            $this->info("💡 TOP OPTIMIZATION RECOMMENDATIONS");
            foreach (array_slice($this->recommendations, 0, 3) as $i => $rec) {
                $this->line(($i + 1) . ". {$rec['title']} ({$rec['priority']} Priority)");
                $this->line("   {$rec['description']}");
                $this->line("   Expected Impact: {$rec['expected_impact']}");
            }
        }
        
        if ($detailed) {
            $this->generateDetailedConsoleOutput();
        }
    }

    private function generateDetailedConsoleOutput()
    {
        $this->line("");
        $this->info("📋 DETAILED ANALYSIS");
        
        // All bottlenecks
        if (!empty($this->bottlenecks)) {
            $this->line("");
            $this->info("🔍 ALL BOTTLENECKS");
            foreach ($this->bottlenecks as $i => $bottleneck) {
                $this->line(($i + 1) . ". [{$bottleneck['severity']}] {$bottleneck['description']}");
                $this->line("   Type: {$bottleneck['type']} | Category: {$bottleneck['category']}");
                $this->line("   Threshold: {$bottleneck['threshold']} | Current: {$bottleneck['current_value']}");
                $this->line("   Impact: {$bottleneck['impact']}");
            }
        }
        
        // All recommendations
        if (!empty($this->recommendations)) {
            $this->line("");
            $this->info("💡 ALL RECOMMENDATIONS");
            foreach ($this->recommendations as $i => $rec) {
                $this->line(($i + 1) . ". {$rec['title']} ({$rec['priority']} Priority)");
                $this->line("   Category: {$rec['category']}");
                $this->line("   Description: {$rec['description']}");
                $this->line("   Expected Impact: {$rec['expected_impact']}");
                $this->line("   Implementation Effort: {$rec['implementation_effort']}");
                $this->line("   Actions:");
                foreach ($rec['actions'] as $action) {
                    $this->line("   • {$action}");
                }
                $this->line("");
            }
        }
    }

    private function generateJSONOutput($detailed)
    {
        $output = [
            'generated_at' => now()->toISOString(),
            'analysis_version' => '1.0',
            'performance_metrics' => $this->performanceMetrics,
            'bottlenecks' => $this->bottlenecks,
            'recommendations' => $this->recommendations
        ];
        
        if ($detailed) {
            $output['raw_data'] = $this->analysisData;
        }
        
        $filename = 'performance_analysis_' . date('Y-m-d_H-i-s') . '.json';
        $path = storage_path("logs/{$filename}");
        
        file_put_contents($path, json_encode($output, JSON_PRETTY_PRINT));
        
        $this->info("📄 JSON report saved to: {$path}");
    }

    private function generateHTMLOutput($detailed)
    {
        $html = $this->buildHTMLReport($detailed);
        
        $filename = 'performance_analysis_' . date('Y-m-d_H-i-s') . '.html';
        $path = storage_path("logs/{$filename}");
        
        file_put_contents($path, $html);
        
        $this->info("📄 HTML report saved to: {$path}");
    }

    private function buildHTMLReport($detailed)
    {
        $stability = $this->performanceMetrics['stability'] ?? ['rating' => 'UNKNOWN', 'overall_score' => 0];
        $capacity = $this->performanceMetrics['capacity']['overall'] ?? ['current_capacity_users' => 'N/A', 'recommended_capacity_users' => 'N/A'];
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <title>Performance Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; }
        .metric { margin: 10px 0; padding: 10px; border-left: 4px solid #007cba; }
        .bottleneck { margin: 10px 0; padding: 10px; border-left: 4px solid #dc3545; }
        .recommendation { margin: 10px 0; padding: 10px; border-left: 4px solid #28a745; }
        .critical { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        .high { font-weight: bold; color: #dc3545; }
        .medium { color: #ffc107; }
        .low { color: #28a745; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🚀 Performance Analysis Report</h1>
        <p>Generated: " . now()->format('Y-m-d H:i:s') . "</p>
        <h2>System Stability: {$stability['rating']} ({$stability['overall_score']}/100)</h2>
    </div>
    
    <h2>📊 Performance Summary</h2>";
        
        if (isset($this->performanceMetrics['database'])) {
            $db = $this->performanceMetrics['database'];
            $html .= "<div class='metric'>
                <h3>Database Performance</h3>
                <ul>
                    <li>Average Response Time: {$db['avg_response_time']}ms</li>
                    <li>95th Percentile Response Time: {$db['response_time_95th']}ms</li>
                    <li>Max Connections: {$db['max_connections']}</li>
                    <li>Deadlocks: {$db['deadlocks']}</li>
                </ul>
            </div>";
        }
        
        if (isset($this->performanceMetrics['application'])) {
            $app = $this->performanceMetrics['application'];
            $html .= "<div class='metric'>
                <h3>Application Performance</h3>
                <ul>
                    <li>Peak Memory Usage: {$app['peak_memory_usage']}%</li>
                    <li>CPU Utilization: {$app['cpu_utilization']}%</li>
                    <li>Error Rate: {$app['error_rate']}%</li>
                </ul>
            </div>";
        }
        
        $html .= "<h2>📏 Capacity Analysis</h2>
        <div class='metric'>
            <ul>
                <li>Current Capacity: {$capacity['current_capacity_users']} concurrent users</li>
                <li>Recommended Capacity: {$capacity['recommended_capacity_users']} concurrent users</li>
            </ul>
        </div>";
        
        if (!empty($this->bottlenecks)) {
            $html .= "<h2>🚨 Performance Bottlenecks</h2>";
            foreach ($this->bottlenecks as $bottleneck) {
                $class = $bottleneck['severity'];
                $html .= "<div class='bottleneck {$class}'>
                    <h3>{$bottleneck['description']}</h3>
                    <p><strong>Impact:</strong> {$bottleneck['impact']}</p>
                    <p><strong>Threshold:</strong> {$bottleneck['threshold']} | <strong>Current:</strong> {$bottleneck['current_value']}</p>
                </div>";
            }
        }
        
        if (!empty($this->recommendations)) {
            $html .= "<h2>💡 Optimization Recommendations</h2>";
            foreach ($this->recommendations as $rec) {
                $priorityClass = strtolower($rec['priority']);
                $html .= "<div class='recommendation'>
                    <h3 class='{$priorityClass}'>{$rec['title']} ({$rec['priority']} Priority)</h3>
                    <p>{$rec['description']}</p>
                    <p><strong>Expected Impact:</strong> {$rec['expected_impact']}</p>
                    <p><strong>Implementation Effort:</strong> {$rec['implementation_effort']}</p>
                    <h4>Actions:</h4>
                    <ul>";
                foreach ($rec['actions'] as $action) {
                    $html .= "<li>{$action}</li>";
                }
                $html .= "</ul></div>";
            }
        }
        
        $html .= "</body></html>";
        
        return $html;
    }

    // Helper methods

    private function calculateAverage($values)
    {
        return empty($values) ? 0 : array_sum($values) / count($values);
    }

    private function calculatePercentile($values, $percentile)
    {
        if (empty($values)) return 0;
        
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[max(0, $index)];
    }

    private function analyzeTrends($timeSeriesData)
    {
        $trends = [];
        
        if (count($timeSeriesData) < 2) {
            return $trends;
        }
        
        $metrics = ['db_response_time', 'memory_usage', 'active_connections', 'cpu_load'];
        
        foreach ($metrics as $metric) {
            $values = array_column($timeSeriesData, $metric);
            $slope = $this->calculateSlope($values);
            
            $trends[] = [
                'metric' => $metric,
                'slope' => $slope,
                'direction' => $slope > 0 ? 'increasing' : ($slope < 0 ? 'decreasing' : 'stable')
            ];
        }
        
        return $trends;
    }

    private function calculateSlope($values)
    {
        $n = count($values);
        if ($n < 2) return 0;
        
        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumXX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $values[$i];
            $sumXX += $x[$i] * $x[$i];
        }
        
        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
    }

    private function estimateMaxUsers($avgResponseTime)
    {
        // Simple estimation based on response time
        if ($avgResponseTime < 50) return 10000;
        if ($avgResponseTime < 100) return 5000;
        if ($avgResponseTime < 200) return 2000;
        if ($avgResponseTime < 500) return 1000;
        return 500;
    }

    private function estimateConnectionCapacity($maxConnections)
    {
        // Estimate capacity based on connection usage
        return (int)($maxConnections * 1.5); // 50% buffer
    }

    private function estimateMemoryCapacity($peakMemoryUsage)
    {
        // Estimate user capacity based on memory usage
        $availableMemory = 100 - $peakMemoryUsage;
        return (int)($availableMemory * 100); // Rough estimation
    }

    private function estimateCPUCapacity($cpuUtilization)
    {
        // Estimate user capacity based on CPU usage
        $availableCPU = 100 - $cpuUtilization;
        return (int)($availableCPU * 50); // Rough estimation
    }

    private function getDatabaseStabilityIssues($db)
    {
        $issues = [];
        if ($db['avg_response_time'] > 200) $issues[] = 'High response time';
        if ($db['deadlocks'] > 0) $issues[] = 'Deadlocks detected';
        if ($db['rollbacks'] > 10) $issues[] = 'High rollback rate';
        return $issues;
    }

    private function getApplicationStabilityIssues($app)
    {
        $issues = [];
        if ($app['peak_memory_usage'] > 80) $issues[] = 'High memory usage';
        if ($app['cpu_utilization'] > 70) $issues[] = 'High CPU usage';
        if ($app['error_rate'] > 1) $issues[] = 'High error rate';
        return $issues;
    }

    private function findMostProblematicFailure($failures)
    {
        $failedFailures = array_filter($failures, fn($f) => $f['status'] === 'failed');
        if (empty($failedFailures)) return null;
        
        $failureTypes = array_column($failedFailures, 'type');
        $counts = array_count_values($failureTypes);
        
        return array_key_first($counts);
    }
}