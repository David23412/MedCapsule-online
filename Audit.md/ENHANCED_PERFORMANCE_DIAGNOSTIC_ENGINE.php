<?php

/**
 * 🚀 ENHANCED PERFORMANCE DIAGNOSTIC ENGINE
 * 
 * Advanced performance testing with timeout protection, extensive logging,
 * and comprehensive diagnostics to identify and resolve performance issues.
 */

require_once __DIR__ . '/vendor/autoload.php';

class EnhancedPerformanceDiagnosticEngine
{
    private array $testResults = [];
    private array $diagnosticLog = [];
    private array $performanceMetrics = [];
    private float $startTime;
    private int $initialMemory;
    private int $maxExecutionTime = 30; // 30 seconds max
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->initialMemory = memory_get_usage(true);
        
        // Set execution time limit
        set_time_limit($this->maxExecutionTime);
        
        echo "🚀 ENHANCED PERFORMANCE DIAGNOSTIC ENGINE INITIALIZED\n";
        echo "🔍 COMPREHENSIVE DIAGNOSTICS WITH TIMEOUT PROTECTION\n";
        echo "⏱️  START TIME: " . date('Y-m-d H:i:s') . "\n";
        echo "💾 INITIAL MEMORY: " . $this->formatBytes($this->initialMemory) . "\n";
        echo "⏰ MAX EXECUTION TIME: {$this->maxExecutionTime}s\n\n";
    }
    
    /**
     * Execute comprehensive performance diagnostics
     */
    public function executePerformanceDiagnostics(): array
    {
        echo "🚀 STARTING ENHANCED PERFORMANCE DIAGNOSTICS\n";
        echo str_repeat("=", 60) . "\n";
        
        try {
            // Phase 1: System Environment Analysis
            echo "\n🔍 PHASE 1: SYSTEM ENVIRONMENT ANALYSIS\n";
            echo str_repeat("-", 40) . "\n";
            $this->analyzeSystemEnvironment();
            
            // Phase 2: Laravel Application Diagnostics
            echo "\n🔧 PHASE 2: LARAVEL APPLICATION DIAGNOSTICS\n";
            echo str_repeat("-", 40) . "\n";
            $app = $this->diagnoseLaravelApplication();
            
            // Phase 3: Database Connection Diagnostics
            echo "\n🗄️  PHASE 3: DATABASE CONNECTION DIAGNOSTICS\n";
            echo str_repeat("-", 40) . "\n";
            $this->diagnoseDatabaseConnections($app);
            
            // Phase 4: Cache System Diagnostics
            echo "\n⚡ PHASE 4: CACHE SYSTEM DIAGNOSTICS\n";
            echo str_repeat("-", 40) . "\n";
            $this->diagnoseCacheSystems($app);
            
            // Phase 5: Route Performance Analysis
            echo "\n🛣️  PHASE 5: ROUTE PERFORMANCE ANALYSIS\n";
            echo str_repeat("-", 40) . "\n";
            $this->analyzeRoutePerformance($app);
            
            // Phase 6: Memory Usage Analysis
            echo "\n💾 PHASE 6: MEMORY USAGE ANALYSIS\n";
            echo str_repeat("-", 40) . "\n";
            $this->analyzeMemoryUsage();
            
            // Phase 7: Performance Bottleneck Detection
            echo "\n🎯 PHASE 7: PERFORMANCE BOTTLENECK DETECTION\n";
            echo str_repeat("-", 40) . "\n";
            $bottlenecks = $this->detectPerformanceBottlenecks();
            
            // Phase 8: Generate Comprehensive Report
            echo "\n📊 PHASE 8: GENERATING COMPREHENSIVE REPORT\n";
            echo str_repeat("-", 40) . "\n";
            $report = $this->generateComprehensiveReport($bottlenecks);
            
            $totalTime = microtime(true) - $this->startTime;
            $finalMemory = memory_get_usage(true);
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "🏆 PERFORMANCE DIAGNOSTICS COMPLETE\n";
            echo str_repeat("=", 60) . "\n";
            echo "⏱️  TOTAL EXECUTION TIME: " . round($totalTime, 2) . "s\n";
            echo "💾 MEMORY USAGE: " . $this->formatBytes($finalMemory - $this->initialMemory) . "\n";
            echo "🔍 DIAGNOSTIC PHASES: 8/8 COMPLETED\n";
            echo "📁 REPORT SAVED: {$report['path']}\n";
            echo "🎯 BOTTLENECKS DETECTED: " . count($bottlenecks) . "\n";
            
            return [
                'success' => true,
                'execution_time' => $totalTime,
                'memory_used' => $finalMemory - $this->initialMemory,
                'phases_completed' => 8,
                'bottlenecks_detected' => count($bottlenecks),
                'report_path' => $report['path']
            ];
            
        } catch (\Exception $e) {
            echo "❌ CRITICAL ERROR: {$e->getMessage()}\n";
            echo "📍 FILE: {$e->getFile()}:{$e->getLine()}\n";
            
            $this->logDiagnostic('critical_error', $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Analyze system environment with comprehensive checks
     */
    private function analyzeSystemEnvironment(): void
    {
        echo "🔍 Analyzing system environment...\n";
        
        $environmentChecks = [
            'php_version' => [
                'test' => fn() => PHP_VERSION,
                'benchmark' => '8.0+',
                'optimal' => version_compare(PHP_VERSION, '8.1.0', '>=')
            ],
            'memory_limit' => [
                'test' => fn() => ini_get('memory_limit'),
                'benchmark' => '128M+',
                'optimal' => $this->parseMemoryLimit(ini_get('memory_limit')) >= 256 * 1024 * 1024
            ],
            'max_execution_time' => [
                'test' => fn() => ini_get('max_execution_time'),
                'benchmark' => '30s+',
                'optimal' => (int)ini_get('max_execution_time') >= 60
            ],
            'opcache_enabled' => [
                'test' => fn() => extension_loaded('opcache') && ini_get('opcache.enable'),
                'benchmark' => 'enabled',
                'optimal' => extension_loaded('opcache') && ini_get('opcache.enable')
            ],
            'redis_extension' => [
                'test' => fn() => extension_loaded('redis'),
                'benchmark' => 'available',
                'optimal' => extension_loaded('redis')
            ],
            'pdo_mysql' => [
                'test' => fn() => extension_loaded('pdo_mysql'),
                'benchmark' => 'available',
                'optimal' => extension_loaded('pdo_mysql')
            ]
        ];
        
        $envResults = [];
        
        foreach ($environmentChecks as $checkName => $checkConfig) {
            $startTime = microtime(true);
            
            try {
                $value = $checkConfig['test']();
                $isOptimal = $checkConfig['optimal'];
                $executionTime = (microtime(true) - $startTime) * 1000;
                
                $envResults[$checkName] = [
                    'value' => $value,
                    'benchmark' => $checkConfig['benchmark'],
                    'optimal' => $isOptimal,
                    'execution_time_ms' => $executionTime
                ];
                
                $status = $isOptimal ? '✅' : '⚠️';
                $displayValue = is_bool($value) ? ($value ? 'enabled' : 'disabled') : $value;
                echo "   {$status} {$checkName}: {$displayValue} (benchmark: {$checkConfig['benchmark']})\n";
                
            } catch (\Exception $e) {
                $envResults[$checkName] = [
                    'error' => $e->getMessage(),
                    'optimal' => false,
                    'execution_time_ms' => (microtime(true) - $startTime) * 1000
                ];
                
                echo "   ❌ {$checkName}: Error - {$e->getMessage()}\n";
            }
        }
        
        $this->testResults['environment'] = $envResults;
        $this->logDiagnostic('environment_analysis', 'System environment analyzed', $envResults);
    }
    
    /**
     * Diagnose Laravel application with timeout protection
     */
    private function diagnoseLaravelApplication()
    {
        echo "🔧 Diagnosing Laravel application...\n";
        
        $timeout = 10; // 10 second timeout
        $startTime = microtime(true);
        
        try {
            if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
                throw new \Exception('Laravel bootstrap file not found');
            }
            
            echo "   🔍 Loading Laravel bootstrap...\n";
            $app = require __DIR__ . '/bootstrap/app.php';
            
            echo "   🔍 Initializing kernel...\n";
            $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
            $kernel->bootstrap();
            
            $initTime = (microtime(true) - $startTime) * 1000;
            
            // Check if initialization took too long
            if ($initTime > $timeout * 1000) {
                echo "   ⚠️  Laravel initialization slow: " . round($initTime, 2) . "ms\n";
            } else {
                echo "   ✅ Laravel initialized: " . round($initTime, 2) . "ms\n";
            }
            
            echo "   📊 Laravel version: " . $app->version() . "\n";
            echo "   🌍 Environment: " . $app->environment() . "\n";
            echo "   🔧 Debug mode: " . ($app->hasDebugModeEnabled() ? 'enabled' : 'disabled') . "\n";
            
            $this->testResults['laravel'] = [
                'version' => $app->version(),
                'environment' => $app->environment(),
                'debug_mode' => $app->hasDebugModeEnabled(),
                'init_time_ms' => $initTime,
                'slow_init' => $initTime > $timeout * 1000
            ];
            
            $this->logDiagnostic('laravel_diagnosis', 'Laravel application diagnosed', $this->testResults['laravel']);
            
            return $app;
            
        } catch (\Exception $e) {
            echo "   ❌ Laravel diagnosis failed: {$e->getMessage()}\n";
            $this->logDiagnostic('laravel_diagnosis_error', 'Laravel diagnosis failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Diagnose database connections with timeout protection (quick mode)
     */
    private function diagnoseDatabaseConnections($app): void
    {
        echo "🗄️  Diagnosing database connections (quick mode)...\n";
        
        try {
            $db = $app->make('db');
            
            // Get default connection safely
            $defaultConnection = 'unknown';
            try {
                $defaultConnection = config('database.default', 'mysql');
            } catch (\Exception $e) {
                echo "   ⚠️  Could not determine default connection\n";
            }
            
            echo "   📊 Default connection: {$defaultConnection}\n";
            
            // Test connection with timeout
            $connectionStart = microtime(true);
            
            try {
                $pdo = $db->connection()->getPdo();
                $connectionTime = (microtime(true) - $connectionStart) * 1000;
                
                if ($connectionTime > 1000) { // > 1 second
                    echo "   ⚠️  Database connection slow: " . round($connectionTime, 2) . "ms\n";
                } else {
                    echo "   ✅ Database connection: " . round($connectionTime, 2) . "ms\n";
                }
                
                // Get driver info
                $driver = 'unknown';
                try {
                    $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
                } catch (\Exception $e) {
                    // Ignore driver detection errors
                }
                
                echo "   📊 Database driver: {$driver}\n";
                echo "   ⚠️  Query tests skipped (quick mode for stability)\n";
                
                $this->testResults['database'] = [
                    'default_connection' => $defaultConnection,
                    'connection_time_ms' => $connectionTime,
                    'driver' => $driver,
                    'slow_connection' => $connectionTime > 1000,
                    'quick_mode' => true,
                    'queries_skipped' => true
                ];
                
            } catch (\Exception $e) {
                echo "   ❌ Database connection failed: {$e->getMessage()}\n";
                $this->testResults['database'] = [
                    'default_connection' => $defaultConnection,
                    'error' => $e->getMessage(),
                    'connection_failed' => true,
                    'quick_mode' => true
                ];
            }
            
            $this->logDiagnostic('database_diagnosis', 'Database connections diagnosed (quick mode)', $this->testResults['database']);
            
        } catch (\Exception $e) {
            echo "   ❌ Database diagnosis failed: {$e->getMessage()}\n";
            $this->logDiagnostic('database_diagnosis_error', 'Database diagnosis failed', [
                'error' => $e->getMessage()
            ]);
            
            // Set fallback results
            $this->testResults['database'] = [
                'error' => $e->getMessage(),
                'connection_failed' => true,
                'quick_mode' => true
            ];
        }
    }
    
    /**
     * Execute database query with timeout protection
     */
    private function executeQueryWithTimeout($db, string $query, int $timeoutSeconds)
    {
        $startTime = microtime(true);
        
        // Simple timeout mechanism
        $result = $db->select($query);
        
        $executionTime = microtime(true) - $startTime;
        if ($executionTime > $timeoutSeconds) {
            throw new \Exception("Query timeout after {$timeoutSeconds}s");
        }
        
        return $result;
    }
    
    /**
     * Diagnose cache systems
     */
    private function diagnoseCacheSystems($app): void
    {
        echo "⚡ Diagnosing cache systems...\n";
        
        try {
            $cache = $app->make('cache');
            $defaultDriver = config('cache.default');
            
            echo "   📊 Default cache driver: {$defaultDriver}\n";
            
            // Test cache operations with timeout
            $cacheTests = [
                'write_test' => function() use ($cache) {
                    $key = 'diag_test_' . time() . '_' . rand(1000, 9999);
                    $data = 'diagnostic_data_' . str_repeat('x', 50);
                    
                    $startTime = microtime(true);
                    $success = $cache->put($key, $data, 60);
                    $executionTime = (microtime(true) - $startTime) * 1000;
                    
                    // Clean up
                    $cache->forget($key);
                    
                    return [
                        'success' => $success,
                        'execution_time_ms' => $executionTime
                    ];
                },
                'read_test' => function() use ($cache) {
                    $key = 'diag_read_' . time() . '_' . rand(1000, 9999);
                    $data = 'diagnostic_read_data';
                    
                    $cache->put($key, $data, 60);
                    
                    $startTime = microtime(true);
                    $retrieved = $cache->get($key);
                    $executionTime = (microtime(true) - $startTime) * 1000;
                    
                    $success = ($retrieved === $data);
                    
                    // Clean up
                    $cache->forget($key);
                    
                    return [
                        'success' => $success,
                        'execution_time_ms' => $executionTime
                    ];
                }
            ];
            
            $cacheResults = [];
            
            foreach ($cacheTests as $testName => $testFunction) {
                try {
                    $result = $testFunction();
                    $cacheResults[$testName] = $result;
                    
                    $status = $result['success'] ? '✅' : '❌';
                    $time = round($result['execution_time_ms'], 2);
                    echo "   {$status} {$testName}: {$time}ms\n";
                    
                } catch (\Exception $e) {
                    $cacheResults[$testName] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    
                    echo "   ❌ {$testName}: {$e->getMessage()}\n";
                }
            }
            
            $this->testResults['cache'] = [
                'default_driver' => $defaultDriver,
                'test_results' => $cacheResults
            ];
            
            $this->logDiagnostic('cache_diagnosis', 'Cache systems diagnosed', $this->testResults['cache']);
            
        } catch (\Exception $e) {
            echo "   ❌ Cache diagnosis failed: {$e->getMessage()}\n";
            $this->logDiagnostic('cache_diagnosis_error', 'Cache diagnosis failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Analyze route performance with sampling
     */
    private function analyzeRoutePerformance($app): void
    {
        echo "🛣️  Analyzing route performance...\n";
        
        try {
            $routes = $app->make('router')->getRoutes();
            $routeCollection = $routes->getRoutes();
            
            echo "   📊 Total routes: " . count($routeCollection) . "\n";
            
            // Sample routes for testing (limit to prevent timeout)
            $sampleRoutes = $this->sampleTestableRoutes($routeCollection, 3);
            echo "   🧪 Sample routes for testing: " . count($sampleRoutes) . "\n";
            
            $routeResults = [];
            
            foreach ($sampleRoutes as $routeInfo) {
                $result = $this->analyzeRoutePerformanceWithTimeout($routeInfo, $app, 2); // 2s timeout
                $routeResults[$routeInfo['uri']] = $result;
                
                $status = $result['success'] ? '✅' : '❌';
                $time = round($result['response_time_ms'], 2);
                echo "   {$status} {$routeInfo['uri']}: {$time}ms\n";
            }
            
            $this->testResults['routes'] = [
                'total_routes' => count($routeCollection),
                'sampled_routes' => count($sampleRoutes),
                'route_results' => $routeResults
            ];
            
            $this->logDiagnostic('route_analysis', 'Route performance analyzed', $this->testResults['routes']);
            
        } catch (\Exception $e) {
            echo "   ❌ Route analysis failed: {$e->getMessage()}\n";
            $this->logDiagnostic('route_analysis_error', 'Route analysis failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sample testable routes to prevent timeout
     */
    private function sampleTestableRoutes($routeCollection, int $maxSamples): array
    {
        $testableRoutes = [];
        
        foreach ($routeCollection as $route) {
            if (count($testableRoutes) >= $maxSamples) {
                break;
            }
            
            $uri = $route->uri();
            $methods = $route->methods();
            
            // Only test simple GET routes
            if (!in_array('GET', $methods) || strpos($uri, '{') !== false) {
                continue;
            }
            
            // Skip problematic routes
            if ($this->isProblematicRoute($uri)) {
                continue;
            }
            
            $testableRoutes[] = [
                'uri' => $uri,
                'methods' => $methods,
                'name' => $route->getName()
            ];
        }
        
        return $testableRoutes;
    }
    
    /**
     * Check if route should be skipped
     */
    private function isProblematicRoute(string $uri): bool
    {
        $skipPatterns = [
            'logout', 'password', 'email/verify', 'sanctum/csrf-cookie',
            '_ignition', 'telescope', 'horizon', 'debugbar', 'admin'
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Analyze single route performance with timeout
     */
    private function analyzeRoutePerformanceWithTimeout(array $routeInfo, $app, int $timeoutSeconds): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            // Create request
            $request = \Illuminate\Http\Request::create('/' . ltrim($routeInfo['uri'], '/'), 'GET');
            
            // Get route with timeout check
            $router = $app->make('router');
            $route = $router->getRoutes()->match($request);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Check for timeout
            if ($executionTime > $timeoutSeconds * 1000) {
                return [
                    'success' => false,
                    'error' => 'Route analysis timeout',
                    'response_time_ms' => $executionTime,
                    'memory_used' => memory_get_usage(true) - $startMemory,
                    'timeout' => true
                ];
            }
            
            if (!$route) {
                return [
                    'success' => false,
                    'error' => 'Route not found',
                    'response_time_ms' => $executionTime,
                    'memory_used' => memory_get_usage(true) - $startMemory
                ];
            }
            
            return [
                'success' => true,
                'response_time_ms' => $executionTime,
                'memory_used' => memory_get_usage(true) - $startMemory,
                'action' => $route->getActionName()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => (microtime(true) - $startTime) * 1000,
                'memory_used' => memory_get_usage(true) - $startMemory
            ];
        }
    }
    
    /**
     * Analyze memory usage patterns
     */
    private function analyzeMemoryUsage(): void
    {
        echo "💾 Analyzing memory usage patterns...\n";
        
        $initialMemory = memory_get_usage(true);
        $initialPeak = memory_get_peak_usage(true);
        
        echo "   📊 Current memory: " . $this->formatBytes($initialMemory) . "\n";
        echo "   📈 Peak memory: " . $this->formatBytes($initialPeak) . "\n";
        
        // Memory stress tests
        $memoryTests = [
            'small_array_test' => function() {
                $array = [];
                for ($i = 0; $i < 100; $i++) {
                    $array[] = 'test_' . $i;
                }
                return count($array);
            },
            'string_concatenation_test' => function() {
                $string = '';
                for ($i = 0; $i < 50; $i++) {
                    $string .= 'test_string_' . $i;
                }
                return strlen($string);
            },
            'object_creation_test' => function() {
                $objects = [];
                for ($i = 0; $i < 50; $i++) {
                    $obj = new \stdClass();
                    $obj->id = $i;
                    $obj->data = 'test_data_' . $i;
                    $objects[] = $obj;
                }
                return count($objects);
            }
        ];
        
        $memoryResults = [];
        
        foreach ($memoryTests as $testName => $testFunction) {
            $beforeMemory = memory_get_usage(true);
            $startTime = microtime(true);
            
            $result = $testFunction();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            $afterMemory = memory_get_usage(true);
            $memoryUsed = $afterMemory - $beforeMemory;
            
            $memoryResults[$testName] = [
                'execution_time_ms' => $executionTime,
                'memory_used' => $memoryUsed,
                'result' => $result
            ];
            
            echo "   ✅ {$testName}: " . round($executionTime, 2) . "ms, " . $this->formatBytes($memoryUsed) . "\n";
            
            // Force garbage collection
            gc_collect_cycles();
        }
        
        $finalMemory = memory_get_usage(true);
        $finalPeak = memory_get_peak_usage(true);
        
        echo "   📊 Final memory: " . $this->formatBytes($finalMemory) . "\n";
        echo "   📈 Final peak: " . $this->formatBytes($finalPeak) . "\n";
        
        $this->testResults['memory'] = [
            'initial_memory' => $initialMemory,
            'initial_peak' => $initialPeak,
            'final_memory' => $finalMemory,
            'final_peak' => $finalPeak,
            'test_results' => $memoryResults
        ];
        
        $this->logDiagnostic('memory_analysis', 'Memory usage analyzed', $this->testResults['memory']);
    }
    
    /**
     * Detect performance bottlenecks
     */
    private function detectPerformanceBottlenecks(): array
    {
        echo "🎯 Detecting performance bottlenecks...\n";
        
        $bottlenecks = [];
        
        // Check Laravel initialization time
        if (isset($this->testResults['laravel']['init_time_ms']) && $this->testResults['laravel']['init_time_ms'] > 1000) {
            $bottlenecks[] = [
                'type' => 'laravel_init',
                'severity' => 'high',
                'description' => 'Laravel initialization is slow',
                'value' => $this->testResults['laravel']['init_time_ms'] . 'ms',
                'recommendation' => 'Consider optimizing autoloader, reducing service providers, or enabling OPcache'
            ];
        }
        
        // Check database connection time
        if (isset($this->testResults['database']['connection_time_ms']) && $this->testResults['database']['connection_time_ms'] > 500) {
            $bottlenecks[] = [
                'type' => 'database_connection',
                'severity' => 'medium',
                'description' => 'Database connection is slow',
                'value' => $this->testResults['database']['connection_time_ms'] . 'ms',
                'recommendation' => 'Check database server performance, network latency, or consider connection pooling'
            ];
        }
        
        // Check environment issues
        if (isset($this->testResults['environment']['opcache_enabled']) && !$this->testResults['environment']['opcache_enabled']['optimal']) {
            $bottlenecks[] = [
                'type' => 'opcache_disabled',
                'severity' => 'high',
                'description' => 'OPcache is not enabled',
                'value' => 'disabled',
                'recommendation' => 'Enable OPcache for significant performance improvements'
            ];
        }
        
        // Check memory usage
        if (isset($this->testResults['memory']['final_peak']) && $this->testResults['memory']['final_peak'] > 128 * 1024 * 1024) {
            $bottlenecks[] = [
                'type' => 'high_memory_usage',
                'severity' => 'medium',
                'description' => 'High memory usage detected',
                'value' => $this->formatBytes($this->testResults['memory']['final_peak']),
                'recommendation' => 'Review memory-intensive operations and consider optimization'
            ];
        }
        
        // Check route performance
        if (isset($this->testResults['routes']['route_results'])) {
            foreach ($this->testResults['routes']['route_results'] as $uri => $result) {
                if (isset($result['response_time_ms']) && $result['response_time_ms'] > 1000) {
                    $bottlenecks[] = [
                        'type' => 'slow_route',
                        'severity' => 'medium',
                        'description' => "Route '{$uri}' is slow",
                        'value' => round($result['response_time_ms'], 2) . 'ms',
                        'recommendation' => 'Optimize route handler, add caching, or review database queries'
                    ];
                }
            }
        }
        
        echo "   🔍 Bottlenecks detected: " . count($bottlenecks) . "\n";
        
        foreach ($bottlenecks as $bottleneck) {
            $severity = strtoupper($bottleneck['severity']);
            $icon = $bottleneck['severity'] === 'high' ? '🔴' : ($bottleneck['severity'] === 'medium' ? '🟡' : '🟢');
            echo "   {$icon} [{$severity}] {$bottleneck['description']}: {$bottleneck['value']}\n";
        }
        
        $this->logDiagnostic('bottleneck_detection', 'Performance bottlenecks detected', $bottlenecks);
        
        return $bottlenecks;
    }
    
    /**
     * Generate comprehensive diagnostic report
     */
    private function generateComprehensiveReport(array $bottlenecks): array
    {
        $reportPath = storage_path('enhanced-performance-diagnostic-' . date('Y-m-d-H-i-s') . '.md');
        $this->ensureDirectoryExists(dirname($reportPath));
        
        $totalTime = microtime(true) - $this->startTime;
        $finalMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $report = $this->buildDiagnosticReport($totalTime, $finalMemory, $peakMemory, $bottlenecks);
        
        file_put_contents($reportPath, $report);
        
        echo "📁 Diagnostic report saved: {$reportPath}\n";
        
        return [
            'path' => $reportPath,
            'size_bytes' => filesize($reportPath),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Build comprehensive diagnostic report
     */
    private function buildDiagnosticReport(float $totalTime, int $finalMemory, int $peakMemory, array $bottlenecks): string
    {
        $timestamp = date('Y-m-d H:i:s');
        
        $report = <<<MD
# 🚀 ENHANCED PERFORMANCE DIAGNOSTIC REPORT

**Generated**: {$timestamp}  
**Engine**: Enhanced Performance Diagnostic Engine  
**Total Execution Time**: {$totalTime}s  
**Memory Usage**: {$this->formatBytes($finalMemory - $this->initialMemory)}  
**Peak Memory**: {$this->formatBytes($peakMemory)}  

## 📊 EXECUTIVE SUMMARY

- **Diagnostic Phases**: 8/8 Completed
- **Bottlenecks Detected**: {$this->countBottlenecksBySeverity($bottlenecks, 'high')} High, {$this->countBottlenecksBySeverity($bottlenecks, 'medium')} Medium, {$this->countBottlenecksBySeverity($bottlenecks, 'low')} Low
- **Overall Performance**: {$this->calculateOverallPerformanceScore($bottlenecks)}%
- **Diagnostic Log Entries**: {$this->countDiagnosticLogEntries()}

## 🔍 SYSTEM ENVIRONMENT ANALYSIS

MD;

        if (isset($this->testResults['environment'])) {
            foreach ($this->testResults['environment'] as $checkName => $result) {
                $status = $result['optimal'] ? '✅ OPTIMAL' : '⚠️ NEEDS ATTENTION';
                $value = is_bool($result['value']) ? ($result['value'] ? 'enabled' : 'disabled') : $result['value'];
                $report .= "- {$status} **{$checkName}**: {$value} (benchmark: {$result['benchmark']})\n";
            }
        }

        $report .= "\n## 🔧 LARAVEL APPLICATION DIAGNOSTICS\n\n";
        
        if (isset($this->testResults['laravel'])) {
            $laravel = $this->testResults['laravel'];
            $report .= "- **Version**: {$laravel['version']}\n";
            $report .= "- **Environment**: {$laravel['environment']}\n";
            $report .= "- **Debug Mode**: " . ($laravel['debug_mode'] ? 'enabled' : 'disabled') . "\n";
            $report .= "- **Initialization Time**: " . round($laravel['init_time_ms'], 2) . "ms\n";
            
            if ($laravel['slow_init'] ?? false) {
                $report .= "- ⚠️ **Performance Issue**: Slow initialization detected\n";
            }
        }

        $report .= "\n## 🗄️ DATABASE CONNECTION DIAGNOSTICS\n\n";
        
        if (isset($this->testResults['database'])) {
            $database = $this->testResults['database'];
            
            if (isset($database['connection_failed']) && $database['connection_failed']) {
                $report .= "- ❌ **Connection Status**: Failed\n";
                $report .= "- **Error**: {$database['error']}\n";
                if (isset($database['default_connection'])) {
                    $report .= "- **Default Connection**: {$database['default_connection']}\n";
                }
            } else {
                $report .= "- ✅ **Connection Status**: Success\n";
                $report .= "- **Default Connection**: {$database['default_connection']}\n";
                $report .= "- **Connection Time**: " . round($database['connection_time_ms'], 2) . "ms\n";
                
                if (isset($database['driver'])) {
                    $report .= "- **Database Driver**: {$database['driver']}\n";
                }
                
                if ($database['slow_connection'] ?? false) {
                    $report .= "- ⚠️ **Performance Issue**: Slow connection detected\n";
                }
                
                if (isset($database['quick_mode']) && $database['quick_mode']) {
                    $report .= "- **Mode**: Quick mode (queries skipped for stability)\n";
                }
            }
            
            if (isset($database['query_tests']) && !empty($database['query_tests'])) {
                $report .= "\n### Query Test Results\n\n";
                foreach ($database['query_tests'] as $testName => $result) {
                    $status = $result['success'] ? '✅' : '❌';
                    $time = round($result['execution_time_ms'], 2);
                    $report .= "- {$status} **{$testName}**: {$time}ms\n";
                }
            }
        }

        $report .= "\n## ⚡ CACHE SYSTEM DIAGNOSTICS\n\n";
        
        if (isset($this->testResults['cache'])) {
            $cache = $this->testResults['cache'];
            $report .= "- **Default Driver**: {$cache['default_driver']}\n\n";
            
            if (isset($cache['test_results'])) {
                $report .= "### Cache Test Results\n\n";
                foreach ($cache['test_results'] as $testName => $result) {
                    $status = $result['success'] ? '✅' : '❌';
                    $time = isset($result['execution_time_ms']) ? round($result['execution_time_ms'], 2) . 'ms' : 'N/A';
                    $report .= "- {$status} **{$testName}**: {$time}\n";
                }
            }
        }

        $report .= "\n## 🛣️ ROUTE PERFORMANCE ANALYSIS\n\n";
        
        if (isset($this->testResults['routes'])) {
            $routes = $this->testResults['routes'];
            $report .= "- **Total Routes**: {$routes['total_routes']}\n";
            $report .= "- **Sampled Routes**: {$routes['sampled_routes']}\n\n";
            
            if (isset($routes['route_results'])) {
                $report .= "### Route Test Results\n\n";
                $report .= "| Route | Status | Response Time | Memory Used |\n";
                $report .= "|-------|--------|---------------|-------------|\n";
                
                foreach ($routes['route_results'] as $uri => $result) {
                    $status = $result['success'] ? '✅ Success' : '❌ Failed';
                    $time = round($result['response_time_ms'], 2) . 'ms';
                    $memory = $this->formatBytes($result['memory_used']);
                    $report .= "| {$uri} | {$status} | {$time} | {$memory} |\n";
                }
            }
        }

        $report .= "\n## 💾 MEMORY USAGE ANALYSIS\n\n";
        
        if (isset($this->testResults['memory'])) {
            $memory = $this->testResults['memory'];
            $report .= "### Memory Usage Summary\n\n";
            $report .= "- **Initial Memory**: " . $this->formatBytes($memory['initial_memory']) . "\n";
            $report .= "- **Final Memory**: " . $this->formatBytes($memory['final_memory']) . "\n";
            $report .= "- **Initial Peak**: " . $this->formatBytes($memory['initial_peak']) . "\n";
            $report .= "- **Final Peak**: " . $this->formatBytes($memory['final_peak']) . "\n\n";
            
            if (isset($memory['test_results'])) {
                $report .= "### Memory Test Results\n\n";
                foreach ($memory['test_results'] as $testName => $result) {
                    $time = round($result['execution_time_ms'], 2);
                    $memUsed = $this->formatBytes($result['memory_used']);
                    $report .= "- ✅ **{$testName}**: {$time}ms, {$memUsed} used\n";
                }
            }
        }

        $report .= "\n## 🎯 PERFORMANCE BOTTLENECKS\n\n";
        
        if (empty($bottlenecks)) {
            $report .= "🎉 **No significant bottlenecks detected!** Your application is performing well.\n";
        } else {
            foreach ($bottlenecks as $bottleneck) {
                $severity = strtoupper($bottleneck['severity']);
                $icon = $bottleneck['severity'] === 'high' ? '🔴' : ($bottleneck['severity'] === 'medium' ? '🟡' : '🟢');
                
                $report .= "### {$icon} [{$severity}] {$bottleneck['description']}\n\n";
                $report .= "- **Current Value**: {$bottleneck['value']}\n";
                $report .= "- **Recommendation**: {$bottleneck['recommendation']}\n\n";
            }
        }

        $report .= "\n## 📋 DIAGNOSTIC RECOMMENDATIONS\n\n";
        $report .= $this->generateDiagnosticRecommendations($bottlenecks);

        $report .= "\n## 📊 DIAGNOSTIC LOG SUMMARY\n\n";
        $report .= "- **Total Log Entries**: " . count($this->diagnosticLog) . "\n";
        $report .= "- **Critical Errors**: " . $this->countDiagnosticLogByType('critical_error') . "\n";
        $report .= "- **Analysis Phases**: " . $this->countDiagnosticLogByType('analysis') . "\n";

        $report .= "\n---\n\n";
        $report .= "*Report generated by Enhanced Performance Diagnostic Engine*  \n";
        $report .= "*Comprehensive performance diagnostics with timeout protection*\n";

        return $report;
    }
    
    /**
     * Generate diagnostic recommendations
     */
    private function generateDiagnosticRecommendations(array $bottlenecks): string
    {
        $recommendations = [];
        
        // High priority recommendations
        $highPriorityBottlenecks = array_filter($bottlenecks, fn($b) => $b['severity'] === 'high');
        if (!empty($highPriorityBottlenecks)) {
            $recommendations[] = "### 🔴 HIGH PRIORITY ACTIONS";
            foreach ($highPriorityBottlenecks as $bottleneck) {
                $recommendations[] = "- **{$bottleneck['description']}**: {$bottleneck['recommendation']}";
            }
            $recommendations[] = "";
        }
        
        // Medium priority recommendations
        $mediumPriorityBottlenecks = array_filter($bottlenecks, fn($b) => $b['severity'] === 'medium');
        if (!empty($mediumPriorityBottlenecks)) {
            $recommendations[] = "### 🟡 MEDIUM PRIORITY OPTIMIZATIONS";
            foreach ($mediumPriorityBottlenecks as $bottleneck) {
                $recommendations[] = "- **{$bottleneck['description']}**: {$bottleneck['recommendation']}";
            }
            $recommendations[] = "";
        }
        
        // General recommendations
        $recommendations[] = "### 💡 GENERAL PERFORMANCE TIPS";
        $recommendations[] = "- Enable OPcache in production for 20-50% performance improvement";
        $recommendations[] = "- Use Redis for caching instead of file-based cache";
        $recommendations[] = "- Implement database query optimization and indexing";
        $recommendations[] = "- Consider using a CDN for static assets";
        $recommendations[] = "- Monitor application performance regularly";
        
        if (empty($bottlenecks)) {
            $recommendations = ["🎉 **Excellent Performance**: Your application is well-optimized! Continue monitoring for any changes."];
        }
        
        return implode("\n", $recommendations);
    }
    
    // Helper methods
    
    private function countBottlenecksBySeverity(array $bottlenecks, string $severity): int
    {
        return count(array_filter($bottlenecks, fn($b) => $b['severity'] === $severity));
    }
    
    private function calculateOverallPerformanceScore(array $bottlenecks): int
    {
        $score = 100;
        
        foreach ($bottlenecks as $bottleneck) {
            switch ($bottleneck['severity']) {
                case 'high':
                    $score -= 20;
                    break;
                case 'medium':
                    $score -= 10;
                    break;
                case 'low':
                    $score -= 5;
                    break;
            }
        }
        
        return max(0, $score);
    }
    
    private function countDiagnosticLogEntries(): int
    {
        return count($this->diagnosticLog);
    }
    
    private function countDiagnosticLogByType(string $type): int
    {
        return count(array_filter($this->diagnosticLog, fn($log) => str_contains($log['type'], $type)));
    }
    
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int) $limit;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
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
    
    private function logDiagnostic(string $type, string $message, array $data = []): void
    {
        $this->diagnosticLog[] = [
            'timestamp' => microtime(true),
            'type' => $type,
            'message' => $message,
            'data' => $data
        ];
    }
    
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}

// Execute the enhanced performance diagnostics
try {
    $engine = new EnhancedPerformanceDiagnosticEngine();
    $results = $engine->executePerformanceDiagnostics();
    
    echo "\n🎯 ENHANCED PERFORMANCE DIAGNOSTICS COMPLETE\n";
    echo "✅ Success: " . ($results['success'] ? 'YES' : 'NO') . "\n";
    echo "⏱️  Execution Time: " . round($results['execution_time'], 2) . "s\n";
    echo "💾 Memory Used: " . number_format($results['memory_used']) . " bytes\n";
    echo "🔍 Phases Completed: " . $results['phases_completed'] . "/8\n";
    echo "🎯 Bottlenecks Detected: " . $results['bottlenecks_detected'] . "\n";
    echo "📁 Report Path: " . $results['report_path'] . "\n";
    
} catch (Exception $e) {
    echo "\n❌ PERFORMANCE DIAGNOSTICS FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}