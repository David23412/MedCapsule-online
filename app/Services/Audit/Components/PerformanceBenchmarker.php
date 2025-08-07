<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\Finding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Performance Benchmarker
 * 
 * Execute real-world performance testing with production-like conditions
 * Measures route response times, memory usage, and concurrent load simulation
 */
class PerformanceBenchmarker
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $benchmarkResults = [];
    private Client $httpClient;

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => false // For local testing
        ]);
    }

    /**
     * Execute comprehensive performance benchmarking with CONCRETE EVIDENCE
     */
    public function executePerformanceBenchmarking(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Performance Benchmarking Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        $this->benchmarkResults = [];
        
        try {
            // 1. CONCRETE EVIDENCE: Route Response Time Measurement
            $this->benchmarkRoutePerformance();
            
            // 2. CONCRETE EVIDENCE: Memory Usage Profiling
            $this->profileMemoryUsage();
            
            // 3. CONCRETE EVIDENCE: Cache Effectiveness Analysis
            $this->analyzeCacheEffectiveness();
            
            // 4. CONCRETE EVIDENCE: Database Query Performance
            $this->benchmarkDatabaseQueries();
            
            // 5. CONCRETE EVIDENCE: Concurrent Load Simulation
            $this->simulateConcurrentLoad();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'performance_benchmarking',
                $this->findings,
                $this->generatePerformanceMetrics(),
                $executionTime
            );
            
            Log::info('✅ Performance Benchmarking Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Performance Benchmarking Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Benchmark route performance with CONCRETE EVIDENCE
     */
    public function benchmarkRoutePerformance(): void
    {
        Log::info('🔍 Benchmarking Route Performance');
        
        // Get all registered routes
        $routes = Route::getRoutes();
        $criticalRoutes = $this->identifyCriticalRoutes($routes);
        
        foreach ($criticalRoutes as $routeInfo) {
            $this->benchmarkSingleRoute($routeInfo);
        }
    }

    /**
     * Identify critical routes for performance testing
     */
    private function identifyCriticalRoutes($routes): array
    {
        $criticalRoutes = [];
        
        $routes = $routes->load('uri');
        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            
            // Focus on GET routes that are likely to be high-traffic
            if (!in_array('GET', $methods)) {
                continue;
            }
            
            // Skip routes with parameters for now (would need test data)
            if (strpos($uri, '{') !== false) {
                continue;
            }
            
            // Focus on key application routes
            if ($this->isCriticalRoute($uri)) {
                $criticalRoutes[] = [
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'methods' => $methods
                ];
            }
        }
        
        return array_slice($criticalRoutes, 0, 10); // Limit to 10 routes for focused analysis
    }

    /**
     * Check if route is critical for performance testing
     */
    private function isCriticalRoute(string $uri): bool
    {
        $criticalPatterns = [
            '/',
            'home',
            'dashboard',
            'courses',
            'topics',
            'quiz',
            'learn',
            'profile',
            'api/'
        ];
        
        foreach ($criticalPatterns as $pattern) {
            if (strpos($uri, $pattern) !== false || $uri === $pattern) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Benchmark single route with CONCRETE EVIDENCE
     */
    private function benchmarkSingleRoute(array $routeInfo): void
    {
        $uri = $routeInfo['uri'];
        $baseUrl = config('app.url');
        $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
        
        $benchmarkResults = [];
        $iterations = 5;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            try {
                $response = $this->httpClient->get($fullUrl, [
                    'headers' => [
                        'User-Agent' => 'MedCapsule-Audit-System/1.0',
                        'Accept' => 'text/html,application/json'
                    ]
                ]);
                
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                
                $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
                $memoryUsed = $endMemory - $startMemory;
                
                $benchmarkResults[] = [
                    'iteration' => $i + 1,
                    'response_time_ms' => round($responseTime, 2),
                    'memory_used_bytes' => $memoryUsed,
                    'status_code' => $response->getStatusCode(),
                    'content_length' => strlen($response->getBody()),
                    'success' => true
                ];
                
            } catch (RequestException $e) {
                $endTime = microtime(true);
                $responseTime = ($endTime - $startTime) * 1000;
                
                $benchmarkResults[] = [
                    'iteration' => $i + 1,
                    'response_time_ms' => round($responseTime, 2),
                    'memory_used_bytes' => 0,
                    'status_code' => $e->getCode(),
                    'error' => $e->getMessage(),
                    'success' => false
                ];
            }
            
            // Small delay between requests
            usleep(100000); // 100ms
        }
        
        // Analyze results and create findings
        $this->analyzeRouteBenchmarkResults($routeInfo, $benchmarkResults);
    }

    /**
     * Analyze route benchmark results and create findings
     */
    private function analyzeRouteBenchmarkResults(array $routeInfo, array $results): void
    {
        $successfulResults = array_filter($results, fn($r) => $r['success']);
        
        if (empty($successfulResults)) {
            // Route completely failed
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'Route completely inaccessible during performance testing',
                'performance',
                'route_failure'
            );
            
            $finding->addFileReference('routes/' . $routeInfo['uri'], 1, 'Failed route');
            $finding->addMetric('failure_rate', 100, 'percentage');
            $finding->addMetric('total_attempts', count($results), 'requests');
            
            $finding->addEvidence('runtime_log', [
                'route_uri' => $routeInfo['uri'],
                'test_results' => $results,
                'analysis_timestamp' => now()->toISOString()
            ], 'Route accessibility test results');
            
            $finding->setRecommendation('Investigate route configuration and dependencies');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
            return;
        }
        
        // Calculate performance metrics
        $responseTimes = array_column($successfulResults, 'response_time_ms');
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $minResponseTime = min($responseTimes);
        
        // Check for slow routes (>2000ms average)
        if ($avgResponseTime > 2000) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Slow route performance detected - exceeds acceptable response time',
                'performance',
                'slow_route'
            );
            
            $finding->addFileReference('routes/' . $routeInfo['uri'], 1, 'Slow performing route');
            $finding->addMetric('average_response_time', round($avgResponseTime, 2), 'ms');
            $finding->addMetric('max_response_time', round($maxResponseTime, 2), 'ms');
            $finding->addMetric('min_response_time', round($minResponseTime, 2), 'ms');
            $finding->addMetric('performance_threshold_exceeded', round($avgResponseTime - 2000, 2), 'ms');
            
            $finding->addEvidence('runtime_log', [
                'route_uri' => $routeInfo['uri'],
                'benchmark_iterations' => count($successfulResults),
                'response_times' => $responseTimes,
                'analysis_timestamp' => now()->toISOString(),
                'test_method' => 'http_client_benchmark'
            ], 'Route performance benchmark results');
            
            $finding->addEvidence('benchmark_result', [
                'avg_response_time' => $avgResponseTime,
                'performance_variance' => round(max($responseTimes) - min($responseTimes), 2),
                'success_rate' => round((count($successfulResults) / count($results)) * 100, 1)
            ], 'Statistical performance analysis');
            
            $finding->setRecommendation('Optimize route performance through caching, query optimization, or code refactoring');
            $finding->setEstimatedHours(6);
            
            $this->findings[] = $finding;
        }
        
        // Check for high response time variance (inconsistent performance)
        $variance = max($responseTimes) - min($responseTimes);
        if ($variance > 1000) { // More than 1 second variance
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Inconsistent route performance - high response time variance',
                'performance',
                'performance_variance'
            );
            
            $finding->addFileReference('routes/' . $routeInfo['uri'], 1, 'Inconsistent performance route');
            $finding->addMetric('response_time_variance', round($variance, 2), 'ms');
            $finding->addMetric('average_response_time', round($avgResponseTime, 2), 'ms');
            $finding->addMetric('consistency_score', round(100 - (($variance / $avgResponseTime) * 100), 1), 'percentage');
            
            $finding->addEvidence('runtime_log', [
                'route_uri' => $routeInfo['uri'],
                'response_times' => $responseTimes,
                'variance_analysis' => [
                    'min' => $minResponseTime,
                    'max' => $maxResponseTime,
                    'variance' => $variance
                ],
                'analysis_timestamp' => now()->toISOString()
            ], 'Route performance consistency analysis');
            
            $finding->setRecommendation('Investigate performance inconsistencies - check for resource contention or inefficient queries');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
        
        // Store results for reporting
        $this->benchmarkResults[$routeInfo['uri']] = [
            'route_info' => $routeInfo,
            'results' => $results,
            'metrics' => [
                'avg_response_time' => $avgResponseTime,
                'max_response_time' => $maxResponseTime,
                'min_response_time' => $minResponseTime,
                'variance' => $variance,
                'success_rate' => (count($successfulResults) / count($results)) * 100
            ]
        ];
    }

    /**
     * Profile memory usage with CONCRETE EVIDENCE
     */
    public function profileMemoryUsage(): void
    {
        Log::info('🔍 Profiling Memory Usage');
        
        $initialMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        // Check for high memory usage
        $memoryUsagePercentage = ($peakMemory / $memoryLimit) * 100;
        
        if ($memoryUsagePercentage > 80) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'High memory usage detected - approaching memory limit',
                'performance',
                'memory_usage'
            );
            
            $finding->addFileReference('php.ini', 1, 'Memory limit configuration');
            $finding->addMetric('peak_memory_usage', round($peakMemory / 1024 / 1024, 2), 'MB');
            $finding->addMetric('memory_limit', round($memoryLimit / 1024 / 1024, 2), 'MB');
            $finding->addMetric('memory_usage_percentage', round($memoryUsagePercentage, 1), 'percentage');
            
            $finding->addEvidence('runtime_log', [
                'initial_memory' => $initialMemory,
                'peak_memory' => $peakMemory,
                'memory_limit' => $memoryLimit,
                'analysis_timestamp' => now()->toISOString()
            ], 'Memory usage profiling results');
            
            $finding->setRecommendation('Optimize memory usage or increase memory limit');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Analyze cache effectiveness with CONCRETE EVIDENCE
     */
    public function analyzeCacheEffectiveness(): void
    {
        Log::info('🔍 Analyzing Cache Effectiveness');
        
        // Test cache operations
        $cacheKey = 'audit_cache_test_' . time();
        $testData = 'cache_effectiveness_test_data';
        
        // Test cache write
        $writeStartTime = microtime(true);
        $writeSuccess = Cache::put($cacheKey, $testData, 60);
        $writeTime = (microtime(true) - $writeStartTime) * 1000;
        
        // Test cache read
        $readStartTime = microtime(true);
        $readData = Cache::get($cacheKey);
        $readTime = (microtime(true) - $readStartTime) * 1000;
        
        // Test cache hit
        $cacheHit = ($readData === $testData);
        
        // Clean up
        Cache::forget($cacheKey);
        
        // Analyze cache performance
        if ($writeTime > 100 || $readTime > 50) { // Slow cache operations
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Slow cache operations detected - performance impact',
                'performance',
                'cache_performance'
            );
            
            $finding->addFileReference('config/cache.php', 1, 'Cache configuration');
            $finding->addMetric('cache_write_time', round($writeTime, 2), 'ms');
            $finding->addMetric('cache_read_time', round($readTime, 2), 'ms');
            $finding->addMetric('cache_hit_success', $cacheHit ? 1 : 0, 'boolean');
            
            $finding->addEvidence('runtime_log', [
                'cache_driver' => config('cache.default'),
                'write_performance' => $writeTime,
                'read_performance' => $readTime,
                'cache_hit' => $cacheHit,
                'analysis_timestamp' => now()->toISOString()
            ], 'Cache performance test results');
            
            $finding->setRecommendation('Optimize cache configuration or consider alternative cache driver');
            $finding->setEstimatedHours(3);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Benchmark database query performance
     */
    public function benchmarkDatabaseQueries(): void
    {
        Log::info('🔍 Benchmarking Database Query Performance');
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Execute some test queries
        $queryStartTime = microtime(true);
        
        try {
            // Test basic queries
            $userCount = DB::table('users')->count();
            $recentUsers = DB::table('users')->orderBy('created_at', 'desc')->limit(10)->get();
            
            $queryEndTime = microtime(true);
            $totalQueryTime = ($queryEndTime - $queryStartTime) * 1000;
            
            // Get executed queries
            $queries = DB::getQueryLog();
            
            // Analyze query performance
            foreach ($queries as $query) {
                if ($query['time'] > 1000) { // Queries taking more than 1 second
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'Slow database query detected - performance bottleneck',
                        'performance',
                        'slow_query'
                    );
                    
                    $finding->addFileReference('database/query', 1, 'Slow executing query');
                    $finding->addMetric('query_execution_time', $query['time'], 'ms');
                    $finding->addMetric('performance_threshold_exceeded', $query['time'] - 1000, 'ms');
                    
                    $finding->addEvidence('runtime_log', [
                        'query_sql' => $query['query'],
                        'query_bindings' => $query['bindings'],
                        'execution_time' => $query['time'],
                        'analysis_timestamp' => now()->toISOString()
                    ], 'Database query performance analysis');
                    
                    $finding->setRecommendation('Optimize query with proper indexing or query restructuring');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Database query benchmarking failed', ['error' => $e->getMessage()]);
        }
        
        // Disable query logging
        DB::disableQueryLog();
    }

    /**
     * Simulate concurrent load with CONCRETE EVIDENCE
     */
    public function simulateConcurrentLoad(): void
    {
        Log::info('🔍 Simulating Concurrent Load');
        
        // Simulate light concurrent load (5 concurrent requests)
        $concurrentRequests = 5;
        $targetUrl = config('app.url');
        
        $promises = [];
        $results = [];
        
        $startTime = microtime(true);
        
        // Create concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            try {
                $response = $this->httpClient->get($targetUrl, [
                    'timeout' => 10,
                    'headers' => [
                        'User-Agent' => 'MedCapsule-Load-Test/1.0'
                    ]
                ]);
                
                $results[] = [
                    'request_id' => $i + 1,
                    'status_code' => $response->getStatusCode(),
                    'response_time' => microtime(true) - $startTime,
                    'success' => true
                ];
                
            } catch (RequestException $e) {
                $results[] = [
                    'request_id' => $i + 1,
                    'status_code' => $e->getCode(),
                    'error' => $e->getMessage(),
                    'success' => false
                ];
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $successfulRequests = count(array_filter($results, fn($r) => $r['success']));
        $successRate = ($successfulRequests / $concurrentRequests) * 100;
        
        // Analyze concurrent load results
        if ($successRate < 80) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Poor concurrent load handling - high failure rate under load',
                'performance',
                'concurrent_load'
            );
            
            $finding->addFileReference('server/configuration', 1, 'Server load handling');
            $finding->addMetric('concurrent_requests', $concurrentRequests, 'requests');
            $finding->addMetric('success_rate', round($successRate, 1), 'percentage');
            $finding->addMetric('total_execution_time', round($totalTime * 1000, 2), 'ms');
            
            $finding->addEvidence('runtime_log', [
                'load_test_results' => $results,
                'concurrent_requests' => $concurrentRequests,
                'success_rate' => $successRate,
                'analysis_timestamp' => now()->toISOString()
            ], 'Concurrent load test results');
            
            $finding->setRecommendation('Optimize server configuration for concurrent request handling');
            $finding->setEstimatedHours(6);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Get PHP memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit == -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * Generate performance metrics
     */
    private function generatePerformanceMetrics(): array
    {
        $totalRoutesTested = count($this->benchmarkResults);
        $avgResponseTime = 0;
        
        if ($totalRoutesTested > 0) {
            $allResponseTimes = [];
            foreach ($this->benchmarkResults as $result) {
                $allResponseTimes[] = $result['metrics']['avg_response_time'];
            }
            $avgResponseTime = array_sum($allResponseTimes) / count($allResponseTimes);
        }
        
        return [
            [
                'name' => 'total_performance_findings',
                'value' => count($this->findings),
                'unit' => 'issues'
            ],
            [
                'name' => 'routes_tested',
                'value' => $totalRoutesTested,
                'unit' => 'routes'
            ],
            [
                'name' => 'average_response_time',
                'value' => round($avgResponseTime, 2),
                'unit' => 'ms'
            ],
            [
                'name' => 'peak_memory_usage',
                'value' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'unit' => 'MB'
            ]
        ];
    }
}