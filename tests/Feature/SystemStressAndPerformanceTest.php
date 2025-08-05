<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SessionSecurityLog;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SystemStressAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $securityService;
    protected $testUsers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SessionSecurityService();
        
        // Create 100 test users for stress testing
        for ($i = 1; $i <= 100; $i++) {
            $this->testUsers[] = User::factory()->create([
                'email' => "stress{$i}@test.com",
                'password' => Hash::make('password123'),
                'role' => 'student'
            ]);
        }
    }

    /** @test */
    public function it_handles_extreme_concurrent_user_load()
    {
        echo "\n⚡ EXTREME CONCURRENT USER LOAD TEST\n";
        echo "===================================\n";
        
        $concurrentUsers = 100;
        $requestsPerUser = 50;
        $totalRequests = $concurrentUsers * $requestsPerUser;
        
        $startTime = microtime(true);
        $results = [];
        $errors = 0;
        $memoryUsage = [];
        
        echo "Simulating {$concurrentUsers} concurrent users, {$requestsPerUser} requests each\n";
        echo "Total requests: {$totalRequests}\n\n";
        
        // Simulate concurrent users
        for ($userIndex = 0; $userIndex < $concurrentUsers; $userIndex++) {
            $user = $this->testUsers[$userIndex];
            
            // Login the user first
            $user->update([
                'active_session_token' => 'session_' . $userIndex . '_' . time(),
                'session_created_at' => now(),
                'device_fingerprint' => hash('sha256', 'device_' . $userIndex),
                'last_login_ip' => '192.168.' . ($userIndex % 255) . '.' . (($userIndex / 255) % 255),
                'last_user_agent' => 'TestBrowser/' . $userIndex,
                'last_security_check' => now()
            ]);
            
            // Simulate user activity
            for ($requestIndex = 0; $requestIndex < $requestsPerUser; $requestIndex++) {
                $requestStart = microtime(true);
                $memoryBefore = memory_get_usage(true);
                
                try {
                    $request = Request::create('/courses', 'GET', [], [], [], [
                        'HTTP_USER_AGENT' => 'TestBrowser/' . $userIndex,
                        'REMOTE_ADDR' => '192.168.' . ($userIndex % 255) . '.' . (($userIndex / 255) % 255)
                    ]);
                    
                    // Security validation
                    $validation = $this->securityService->validateSessionSecurity($user, $request);
                    
                    // Track activity
                    $this->securityService->trackActivity($user->id, $request->ip());
                    
                    $requestTime = (microtime(true) - $requestStart) * 1000;
                    $memoryAfter = memory_get_usage(true);
                    
                    $results[] = [
                        'user_id' => $user->id,
                        'request_time' => $requestTime,
                        'memory_used' => $memoryAfter - $memoryBefore,
                        'valid' => $validation['valid']
                    ];
                    
                    $memoryUsage[] = $memoryAfter;
                    
                } catch (\Exception $e) {
                    $errors++;
                    echo "Error for user {$userIndex}, request {$requestIndex}: " . $e->getMessage() . "\n";
                }
            }
            
            // Progress indicator
            if (($userIndex + 1) % 10 === 0) {
                $progress = (($userIndex + 1) / $concurrentUsers) * 100;
                echo "Progress: " . number_format($progress, 1) . "%\n";
            }
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Analyze results
        $successfulRequests = count(array_filter($results, fn($r) => $r['valid']));
        $avgRequestTime = array_sum(array_column($results, 'request_time')) / count($results);
        $maxRequestTime = max(array_column($results, 'request_time'));
        $minRequestTime = min(array_column($results, 'request_time'));
        $requestsPerSecond = ($totalRequests / ($totalTime / 1000));
        
        $avgMemoryUsage = array_sum($memoryUsage) / count($memoryUsage) / 1024 / 1024; // MB
        $peakMemoryUsage = max($memoryUsage) / 1024 / 1024; // MB
        
        // Performance assertions
        $this->assertGreaterThan($totalRequests * 0.95, $successfulRequests, 'At least 95% of requests should succeed');
        $this->assertLessThan(100, $avgRequestTime, 'Average request time should be under 100ms');
        $this->assertLessThan(1000, $maxRequestTime, 'Max request time should be under 1 second');
        $this->assertGreaterThan(100, $requestsPerSecond, 'Should handle at least 100 requests per second');
        $this->assertLessThan(500, $peakMemoryUsage, 'Peak memory usage should be under 500MB');
        
        echo "\nEXTREME LOAD TEST RESULTS:\n";
        echo "=========================\n";
        echo "Total Requests: {$totalRequests}\n";
        echo "Successful Requests: {$successfulRequests}\n";
        echo "Errors: {$errors}\n";
        echo "Success Rate: " . number_format(($successfulRequests/$totalRequests)*100, 2) . "%\n";
        echo "Total Time: " . number_format($totalTime, 2) . "ms\n";
        echo "Requests/Second: " . number_format($requestsPerSecond, 2) . "\n";
        echo "Average Request Time: " . number_format($avgRequestTime, 3) . "ms\n";
        echo "Min Request Time: " . number_format($minRequestTime, 3) . "ms\n";
        echo "Max Request Time: " . number_format($maxRequestTime, 3) . "ms\n";
        echo "Average Memory Usage: " . number_format($avgMemoryUsage, 2) . "MB\n";
        echo "Peak Memory Usage: " . number_format($peakMemoryUsage, 2) . "MB\n\n";
    }

    /** @test */
    public function it_maintains_database_performance_under_stress()
    {
        echo "🗄️ DATABASE PERFORMANCE STRESS TEST\n";
        echo "==================================\n";
        
        $iterations = 1000;
        $batchSize = 50;
        $queryTimes = [];
        $insertTimes = [];
        $updateTimes = [];
        
        echo "Testing {$iterations} database operations in batches of {$batchSize}\n\n";
        
        // Enable query logging
        DB::enableQueryLog();
        
        for ($i = 0; $i < $iterations; $i += $batchSize) {
            $batchStart = microtime(true);
            
            // Batch insert security logs
            $insertStart = microtime(true);
            $logData = [];
            for ($j = 0; $j < $batchSize && ($i + $j) < $iterations; $j++) {
                $user = $this->testUsers[($i + $j) % count($this->testUsers)];
                $logData[] = [
                    'user_id' => $user->id,
                    'event_type' => 'stress_test',
                    'ip_address' => '192.168.' . (($i + $j) % 255) . '.' . ((($i + $j) / 255) % 255),
                    'user_agent' => 'StressTest/' . ($i + $j),
                    'device_fingerprint' => hash('sha256', 'device_' . ($i + $j)),
                    'security_data' => json_encode(['test' => true, 'iteration' => $i + $j]),
                    'risk_level' => ['low', 'medium', 'high'][($i + $j) % 3],
                    'action_taken' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            SessionSecurityLog::insert($logData);
            $insertTimes[] = (microtime(true) - $insertStart) * 1000;
            
            // Batch update users
            $updateStart = microtime(true);
            foreach (array_slice($this->testUsers, $i % count($this->testUsers), min($batchSize, count($this->testUsers))) as $index => $user) {
                $user->update([
                    'last_security_check' => now(),
                    'concurrent_login_attempts' => ($i + $index) % 10
                ]);
            }
            $updateTimes[] = (microtime(true) - $updateStart) * 1000;
            
            // Query performance test
            $queryStart = microtime(true);
            
            // Complex query with joins and aggregations
            $results = DB::table('session_security_logs')
                ->join('users', 'session_security_logs.user_id', '=', 'users.id')
                ->select('users.id', 'users.email', DB::raw('COUNT(*) as log_count'))
                ->where('session_security_logs.created_at', '>=', now()->subHour())
                ->groupBy('users.id', 'users.email')
                ->having('log_count', '>', 0)
                ->orderBy('log_count', 'desc')
                ->limit(10)
                ->get();
            
            $queryTimes[] = (microtime(true) - $queryStart) * 1000;
            
            // Progress indicator
            if (($i + $batchSize) % 200 === 0) {
                $progress = (($i + $batchSize) / $iterations) * 100;
                echo "Progress: " . number_format($progress, 1) . "%\n";
            }
        }
        
        $queries = DB::getQueryLog();
        
        // Analyze database performance
        $avgInsertTime = array_sum($insertTimes) / count($insertTimes);
        $avgUpdateTime = array_sum($updateTimes) / count($updateTimes);
        $avgQueryTime = array_sum($queryTimes) / count($queryTimes);
        $totalQueries = count($queries);
        
        // Performance assertions
        $this->assertLessThan(500, $avgInsertTime, 'Average batch insert should be under 500ms');
        $this->assertLessThan(200, $avgUpdateTime, 'Average batch update should be under 200ms');
        $this->assertLessThan(100, $avgQueryTime, 'Average complex query should be under 100ms');
        $this->assertLessThan($iterations * 5, $totalQueries, 'Query count should be reasonable');
        
        echo "DATABASE STRESS TEST RESULTS:\n";
        echo "============================\n";
        echo "Total Operations: {$iterations}\n";
        echo "Total Queries Executed: {$totalQueries}\n";
        echo "Average Insert Time: " . number_format($avgInsertTime, 2) . "ms\n";
        echo "Average Update Time: " . number_format($avgUpdateTime, 2) . "ms\n";
        echo "Average Query Time: " . number_format($avgQueryTime, 2) . "ms\n";
        echo "Max Insert Time: " . number_format(max($insertTimes), 2) . "ms\n";
        echo "Max Update Time: " . number_format(max($updateTimes), 2) . "ms\n";
        echo "Max Query Time: " . number_format(max($queryTimes), 2) . "ms\n\n";
    }

    /** @test */
    public function it_handles_cache_system_under_extreme_load()
    {
        echo "🚀 CACHE SYSTEM EXTREME LOAD TEST\n";
        echo "=================================\n";
        
        $cacheOperations = 10000;
        $keyVariations = 1000;
        $cacheResults = [];
        
        echo "Testing {$cacheOperations} cache operations with {$keyVariations} key variations\n\n";
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $cacheOperations; $i++) {
            $operationStart = microtime(true);
            $keyIndex = $i % $keyVariations;
            $userId = $this->testUsers[$keyIndex % count($this->testUsers)]->id;
            
            try {
                // Mix of different cache operations
                switch ($i % 4) {
                    case 0: // Set cache
                        $key = "login_attempts_{$userId}";
                        $value = rand(1, 10);
                        Cache::put($key, $value, 900);
                        break;
                        
                    case 1: // Get cache
                        $key = "activity_count_{$userId}";
                        $value = Cache::get($key, 0);
                        break;
                        
                    case 2: // Increment cache
                        $key = "request_count_{$userId}";
                        $current = Cache::get($key, 0);
                        Cache::put($key, $current + 1, 300);
                        break;
                        
                    case 3: // Complex cache operation
                        $key = "recent_ips_{$userId}";
                        $ips = Cache::get($key, []);
                        $ips[] = '192.168.' . rand(1, 255) . '.' . rand(1, 255);
                        $ips = array_unique(array_slice($ips, -5)); // Keep last 5 unique IPs
                        Cache::put($key, $ips, 300);
                        break;
                }
                
                $operationTime = (microtime(true) - $operationStart) * 1000;
                $cacheResults[] = $operationTime;
                
            } catch (\Exception $e) {
                echo "Cache error at operation {$i}: " . $e->getMessage() . "\n";
            }
            
            // Progress indicator
            if (($i + 1) % 1000 === 0) {
                $progress = (($i + 1) / $cacheOperations) * 100;
                echo "Progress: " . number_format($progress, 1) . "%\n";
            }
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Analyze cache performance
        $avgCacheTime = array_sum($cacheResults) / count($cacheResults);
        $maxCacheTime = max($cacheResults);
        $minCacheTime = min($cacheResults);
        $operationsPerSecond = ($cacheOperations / ($totalTime / 1000));
        
        // Test cache hit rate
        $hitTests = 100;
        $hits = 0;
        for ($i = 0; $i < $hitTests; $i++) {
            $userId = $this->testUsers[$i % count($this->testUsers)]->id;
            $key = "login_attempts_{$userId}";
            if (Cache::has($key)) {
                $hits++;
            }
        }
        $hitRate = ($hits / $hitTests) * 100;
        
        // Performance assertions
        $this->assertLessThan(10, $avgCacheTime, 'Average cache operation should be under 10ms');
        $this->assertLessThan(100, $maxCacheTime, 'Max cache operation should be under 100ms');
        $this->assertGreaterThan(1000, $operationsPerSecond, 'Should handle at least 1000 cache operations per second');
        $this->assertGreaterThan(50, $hitRate, 'Cache hit rate should be above 50%');
        
        echo "CACHE SYSTEM STRESS TEST RESULTS:\n";
        echo "=================================\n";
        echo "Total Operations: {$cacheOperations}\n";
        echo "Total Time: " . number_format($totalTime, 2) . "ms\n";
        echo "Operations/Second: " . number_format($operationsPerSecond, 2) . "\n";
        echo "Average Operation Time: " . number_format($avgCacheTime, 3) . "ms\n";
        echo "Min Operation Time: " . number_format($minCacheTime, 3) . "ms\n";
        echo "Max Operation Time: " . number_format($maxCacheTime, 3) . "ms\n";
        echo "Cache Hit Rate: " . number_format($hitRate, 1) . "%\n\n";
    }

    /** @test */
    public function it_survives_memory_pressure_scenarios()
    {
        echo "🧠 MEMORY PRESSURE SURVIVAL TEST\n";
        echo "===============================\n";
        
        $initialMemory = memory_get_usage(true);
        $peakMemory = $initialMemory;
        $memorySnapshots = [];
        $operations = 0;
        $errors = 0;
        
        echo "Initial Memory: " . number_format($initialMemory / 1024 / 1024, 2) . "MB\n\n";
        
        // Create memory pressure by generating large amounts of data
        for ($i = 0; $i < 5000; $i++) {
            try {
                $currentMemory = memory_get_usage(true);
                $memorySnapshots[] = $currentMemory;
                $peakMemory = max($peakMemory, $currentMemory);
                
                // Generate large security data
                $largeSecurityData = [];
                for ($j = 0; $j < 100; $j++) {
                    $largeSecurityData[] = [
                        'timestamp' => microtime(true),
                        'data' => str_repeat('X', 1000), // 1KB of data
                        'fingerprint' => hash('sha256', 'data_' . $i . '_' . $j),
                        'metadata' => array_fill(0, 50, 'metadata_' . rand(1000, 9999))
                    ];
                }
                
                // Simulate security operations with large data
                $user = $this->testUsers[$i % count($this->testUsers)];
                $request = Request::create('/test', 'GET', [], [], [], [
                    'HTTP_USER_AGENT' => 'MemoryTest/' . $i . ' ' . str_repeat('A', 500),
                    'REMOTE_ADDR' => '192.168.' . ($i % 255) . '.' . (($i / 255) % 255)
                ]);
                
                // Security operations
                $fingerprint = $this->securityService->generateDeviceFingerprint($request);
                $analysis = $this->securityService->analyzeLoginAttempt($user, $request);
                
                // Store in cache (simulating real usage)
                $cacheKey = "memory_test_{$i}";
                Cache::put($cacheKey, $largeSecurityData, 60);
                
                $operations++;
                
                // Force garbage collection every 100 operations
                if ($i % 100 === 0) {
                    gc_collect_cycles();
                    
                    $memoryAfterGC = memory_get_usage(true);
                    $memoryIncrease = ($memoryAfterGC - $initialMemory) / 1024 / 1024;
                    
                    echo "Operation {$i}: Memory = " . number_format($memoryAfterGC / 1024 / 1024, 2) . "MB ";
                    echo "(+" . number_format($memoryIncrease, 2) . "MB)\n";
                    
                    // Break if memory usage becomes excessive
                    if ($memoryIncrease > 200) { // 200MB limit
                        echo "Memory limit reached, stopping test\n";
                        break;
                    }
                }
                
            } catch (\Exception $e) {
                $errors++;
                if ($errors > 10) {
                    echo "Too many errors, stopping test\n";
                    break;
                }
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;
        $peakIncrease = ($peakMemory - $initialMemory) / 1024 / 1024;
        
        // Clean up cache to test memory release
        Cache::flush();
        gc_collect_cycles();
        $memoryAfterCleanup = memory_get_usage(true);
        $memoryReleased = ($finalMemory - $memoryAfterCleanup) / 1024 / 1024;
        
        // Memory management assertions
        $this->assertLessThan(300, $peakIncrease, 'Peak memory increase should be under 300MB');
        $this->assertGreaterThan(1000, $operations, 'Should complete at least 1000 operations');
        $this->assertLessThan(0.05, $errors / $operations, 'Error rate should be under 5%');
        $this->assertGreaterThan(10, $memoryReleased, 'Should release significant memory after cleanup');
        
        echo "\nMEMORY PRESSURE TEST RESULTS:\n";
        echo "============================\n";
        echo "Operations Completed: {$operations}\n";
        echo "Errors: {$errors}\n";
        echo "Initial Memory: " . number_format($initialMemory / 1024 / 1024, 2) . "MB\n";
        echo "Final Memory: " . number_format($finalMemory / 1024 / 1024, 2) . "MB\n";
        echo "Peak Memory: " . number_format($peakMemory / 1024 / 1024, 2) . "MB\n";
        echo "Memory Increase: " . number_format($memoryIncrease, 2) . "MB\n";
        echo "Peak Increase: " . number_format($peakIncrease, 2) . "MB\n";
        echo "Memory After Cleanup: " . number_format($memoryAfterCleanup / 1024 / 1024, 2) . "MB\n";
        echo "Memory Released: " . number_format($memoryReleased, 2) . "MB\n";
        echo "Error Rate: " . number_format(($errors / $operations) * 100, 2) . "%\n\n";
    }

    /** @test */
    public function it_maintains_system_stability_during_chaos()
    {
        echo "🌪️ CHAOS ENGINEERING STABILITY TEST\n";
        echo "===================================\n";
        
        $chaosEvents = [
            'database_slow_query',
            'cache_failure',
            'memory_spike',
            'high_cpu_load',
            'network_latency',
            'concurrent_writes',
            'large_payload',
            'invalid_data'
        ];
        
        $chaosResults = [];
        $totalChaosEvents = 100;
        $systemFailures = 0;
        
        echo "Injecting {$totalChaosEvents} chaos events\n\n";
        
        for ($i = 0; $i < $totalChaosEvents; $i++) {
            $chaosType = $chaosEvents[array_rand($chaosEvents)];
            $eventStart = microtime(true);
            
            try {
                switch ($chaosType) {
                    case 'database_slow_query':
                        $this->simulateSlowDatabaseQuery();
                        break;
                    case 'cache_failure':
                        $this->simulateCacheFailure();
                        break;
                    case 'memory_spike':
                        $this->simulateMemorySpike();
                        break;
                    case 'high_cpu_load':
                        $this->simulateHighCPULoad();
                        break;
                    case 'network_latency':
                        $this->simulateNetworkLatency();
                        break;
                    case 'concurrent_writes':
                        $this->simulateConcurrentWrites();
                        break;
                    case 'large_payload':
                        $this->simulateLargePayload();
                        break;
                    case 'invalid_data':
                        $this->simulateInvalidData();
                        break;
                }
                
                $eventTime = (microtime(true) - $eventStart) * 1000;
                $chaosResults[] = [
                    'type' => $chaosType,
                    'time' => $eventTime,
                    'success' => true
                ];
                
            } catch (\Exception $e) {
                $systemFailures++;
                $chaosResults[] = [
                    'type' => $chaosType,
                    'time' => (microtime(true) - $eventStart) * 1000,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
            
            // Progress indicator
            if (($i + 1) % 10 === 0) {
                $progress = (($i + 1) / $totalChaosEvents) * 100;
                echo "Chaos Progress: " . number_format($progress, 1) . "%\n";
            }
        }
        
        // Analyze chaos results
        $successfulEvents = count(array_filter($chaosResults, fn($r) => $r['success']));
        $avgEventTime = array_sum(array_column($chaosResults, 'time')) / count($chaosResults);
        $stabilityRate = ($successfulEvents / $totalChaosEvents) * 100;
        
        // Group by chaos type
        $chaosTypeStats = [];
        foreach ($chaosResults as $result) {
            $type = $result['type'];
            if (!isset($chaosTypeStats[$type])) {
                $chaosTypeStats[$type] = ['total' => 0, 'success' => 0];
            }
            $chaosTypeStats[$type]['total']++;
            if ($result['success']) {
                $chaosTypeStats[$type]['success']++;
            }
        }
        
        // System should maintain high stability
        $this->assertGreaterThan(80, $stabilityRate, 'System stability should be above 80% during chaos');
        $this->assertLessThan(500, $avgEventTime, 'Average event handling should be under 500ms');
        $this->assertLessThan(20, $systemFailures, 'System failures should be minimal');
        
        echo "\nCHAOS ENGINEERING RESULTS:\n";
        echo "=========================\n";
        echo "Total Chaos Events: {$totalChaosEvents}\n";
        echo "Successful Events: {$successfulEvents}\n";
        echo "System Failures: {$systemFailures}\n";
        echo "Stability Rate: " . number_format($stabilityRate, 1) . "%\n";
        echo "Average Event Time: " . number_format($avgEventTime, 2) . "ms\n\n";
        
        echo "Chaos Type Breakdown:\n";
        foreach ($chaosTypeStats as $type => $stats) {
            $typeRate = ($stats['success'] / $stats['total']) * 100;
            echo "  {$type}: {$stats['success']}/{$stats['total']} (" . number_format($typeRate, 1) . "%)\n";
        }
        echo "\n";
    }

    private function simulateSlowDatabaseQuery()
    {
        // Simulate slow query with complex joins
        DB::table('users')
            ->join('session_security_logs', 'users.id', '=', 'session_security_logs.user_id')
            ->select('users.*', DB::raw('COUNT(*) as log_count'))
            ->groupBy('users.id')
            ->having('log_count', '>', 0)
            ->orderBy('log_count', 'desc')
            ->get();
    }

    private function simulateCacheFailure()
    {
        // Simulate cache operations that might fail
        try {
            Cache::put('chaos_test', str_repeat('X', 100000), 60);
            Cache::get('non_existent_key');
            Cache::forget('chaos_test');
        } catch (\Exception $e) {
            // Handle cache failure gracefully
        }
    }

    private function simulateMemorySpike()
    {
        // Create temporary memory spike
        $largeArray = array_fill(0, 10000, str_repeat('X', 1000));
        unset($largeArray);
        gc_collect_cycles();
    }

    private function simulateHighCPULoad()
    {
        // Simulate CPU intensive operation
        $result = 0;
        for ($i = 0; $i < 100000; $i++) {
            $result += hash('sha256', 'cpu_load_' . $i);
        }
    }

    private function simulateNetworkLatency()
    {
        // Simulate network delay
        usleep(rand(10000, 50000)); // 10-50ms delay
    }

    private function simulateConcurrentWrites()
    {
        // Simulate concurrent database writes
        $user = $this->testUsers[array_rand($this->testUsers)];
        $user->update(['last_security_check' => now()]);
    }

    private function simulateLargePayload()
    {
        // Simulate processing large request payload
        $request = Request::create('/test', 'POST', [
            'large_data' => str_repeat('X', 50000)
        ], [], [], [
            'HTTP_USER_AGENT' => str_repeat('A', 1000),
            'REMOTE_ADDR' => '192.168.1.100'
        ]);
        
        $this->securityService->generateDeviceFingerprint($request);
    }

    private function simulateInvalidData()
    {
        // Simulate handling invalid/malicious data
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => null,
            'REMOTE_ADDR' => 'invalid.ip.address'
        ]);
        
        $this->securityService->generateDeviceFingerprint($request);
    }

    protected function tearDown(): void
    {
        SessionSecurityLog::truncate();
        Cache::flush();
        gc_collect_cycles();
        parent::tearDown();
    }
}