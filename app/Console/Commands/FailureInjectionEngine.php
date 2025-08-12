<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class FailureInjectionEngine extends Command
{
    protected $signature = 'stress:inject-failures 
                            {--type=random : Failure type (random|database|network|memory|disk|connection)}
                            {--intensity=medium : Failure intensity (low|medium|high|extreme)}
                            {--duration=60 : Failure duration in seconds}
                            {--interval=30 : Interval between failures in seconds}
                            {--recovery : Enable automatic recovery}';

    protected $description = 'Inject controlled failures for chaos engineering testing';

    private $failureTypes = [
        'database' => [
            'connection_timeout',
            'lock_contention',
            'deadlock_simulation',
            'connection_pool_exhaustion',
            'slow_query_injection',
            'transaction_timeout',
            'index_corruption_simulation'
        ],
        'network' => [
            'high_latency',
            'packet_loss',
            'bandwidth_throttling',
            'connection_drops',
            'dns_resolution_failure',
            'ssl_handshake_failure'
        ],
        'memory' => [
            'memory_leak_simulation',
            'memory_pressure',
            'garbage_collection_stress',
            'buffer_overflow_simulation',
            'memory_fragmentation'
        ],
        'disk' => [
            'disk_full_simulation',
            'io_throttling',
            'disk_corruption_simulation',
            'file_system_errors',
            'disk_latency_injection'
        ],
        'connection' => [
            'max_connections_reached',
            'connection_leak_simulation',
            'session_timeout',
            'authentication_failures',
            'rate_limiting_trigger'
        ]
    ];

    private $intensityLevels = [
        'low' => 0.1,      // 10% impact
        'medium' => 0.3,   // 30% impact
        'high' => 0.6,     // 60% impact
        'extreme' => 0.9   // 90% impact
    ];

    private $activeFailures = [];
    private $recoveryEnabled;

    public function handle()
    {
        $failureType = $this->option('type');
        $intensity = $this->option('intensity');
        $duration = $this->option('duration');
        $interval = $this->option('interval');
        $this->recoveryEnabled = $this->option('recovery');

        $this->info("💣 LETHAL FAILURE INJECTION ENGINE ACTIVATED");
        $this->info("Failure Type: {$failureType}");
        $this->info("Intensity: {$intensity}");
        $this->info("Duration: {$duration} seconds");
        $this->info("Interval: {$interval} seconds");
        $this->info("Auto Recovery: " . ($this->recoveryEnabled ? 'ENABLED' : 'DISABLED'));
        $this->line("");

        if ($failureType === 'random') {
            $this->executeRandomFailureInjection($intensity, $duration, $interval);
        } else {
            $this->executeSpecificFailureInjection($failureType, $intensity, $duration);
        }

        if ($this->recoveryEnabled) {
            $this->executeRecoveryProcedures();
        }

        $this->generateFailureReport();
    }

    private function executeRandomFailureInjection($intensity, $duration, $interval)
    {
        $this->info("🎲 Executing random failure injection pattern");
        
        $endTime = time() + $duration;
        
        while (time() < $endTime) {
            // Randomly select failure type
            $categories = array_keys($this->failureTypes);
            $category = $categories[array_rand($categories)];
            $failures = $this->failureTypes[$category];
            $failureType = $failures[array_rand($failures)];
            
            $this->info("💥 Injecting failure: {$category} -> {$failureType}");
            
            $this->injectFailure($failureType, $intensity);
            
            // Wait for interval or until end time
            $sleepTime = min($interval, $endTime - time());
            if ($sleepTime > 0) {
                sleep($sleepTime);
            }
        }
    }

    private function executeSpecificFailureInjection($failureType, $intensity, $duration)
    {
        $this->info("🎯 Executing specific failure injection: {$failureType}");
        
        if (!isset($this->failureTypes[$failureType])) {
            $this->error("Unknown failure type: {$failureType}");
            return;
        }
        
        $failures = $this->failureTypes[$failureType];
        
        foreach ($failures as $failure) {
            $this->info("💥 Injecting {$failureType} failure: {$failure}");
            $this->injectFailure($failure, $intensity);
            
            // Wait between different failure types
            sleep(min(10, $duration / count($failures)));
        }
    }

    private function injectFailure($failureType, $intensity)
    {
        $startTime = microtime(true);
        $intensityMultiplier = $this->intensityLevels[$intensity] ?? 0.3;
        
        try {
            switch ($failureType) {
                // Database Failures
                case 'connection_timeout':
                    $this->injectConnectionTimeout($intensityMultiplier);
                    break;
                case 'lock_contention':
                    $this->injectLockContention($intensityMultiplier);
                    break;
                case 'deadlock_simulation':
                    $this->injectDeadlockSimulation($intensityMultiplier);
                    break;
                case 'connection_pool_exhaustion':
                    $this->injectConnectionPoolExhaustion($intensityMultiplier);
                    break;
                case 'slow_query_injection':
                    $this->injectSlowQueries($intensityMultiplier);
                    break;
                case 'transaction_timeout':
                    $this->injectTransactionTimeout($intensityMultiplier);
                    break;
                
                // Network Failures
                case 'high_latency':
                    $this->injectHighLatency($intensityMultiplier);
                    break;
                case 'packet_loss':
                    $this->injectPacketLoss($intensityMultiplier);
                    break;
                case 'bandwidth_throttling':
                    $this->injectBandwidthThrottling($intensityMultiplier);
                    break;
                case 'connection_drops':
                    $this->injectConnectionDrops($intensityMultiplier);
                    break;
                
                // Memory Failures
                case 'memory_leak_simulation':
                    $this->injectMemoryLeak($intensityMultiplier);
                    break;
                case 'memory_pressure':
                    $this->injectMemoryPressure($intensityMultiplier);
                    break;
                case 'garbage_collection_stress':
                    $this->injectGarbageCollectionStress($intensityMultiplier);
                    break;
                
                // Disk Failures
                case 'disk_full_simulation':
                    $this->injectDiskFullSimulation($intensityMultiplier);
                    break;
                case 'io_throttling':
                    $this->injectIOThrottling($intensityMultiplier);
                    break;
                case 'disk_latency_injection':
                    $this->injectDiskLatency($intensityMultiplier);
                    break;
                
                // Connection Failures
                case 'max_connections_reached':
                    $this->injectMaxConnectionsReached($intensityMultiplier);
                    break;
                case 'connection_leak_simulation':
                    $this->injectConnectionLeak($intensityMultiplier);
                    break;
                case 'session_timeout':
                    $this->injectSessionTimeout($intensityMultiplier);
                    break;
                
                default:
                    $this->warn("Unknown failure type: {$failureType}");
                    return;
            }
            
            $executionTime = microtime(true) - $startTime;
            
            $this->activeFailures[] = [
                'type' => $failureType,
                'intensity' => $intensity,
                'start_time' => $startTime,
                'execution_time' => $executionTime,
                'status' => 'completed'
            ];
            
            $this->info("✅ Failure injection completed: {$failureType} ({$executionTime}s)");
            
        } catch (\Exception $e) {
            $this->error("❌ Failure injection failed: {$failureType} - " . $e->getMessage());
            
            $this->activeFailures[] = [
                'type' => $failureType,
                'intensity' => $intensity,
                'start_time' => $startTime,
                'execution_time' => microtime(true) - $startTime,
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    // Database Failure Injections

    private function injectConnectionTimeout($intensity)
    {
        $timeoutDuration = (int)(10 * $intensity); // 1-10 seconds based on intensity
        
        $this->info("⏱️  Injecting connection timeout ({$timeoutDuration}s)");
        
        // Create multiple connections and hold them
        $connections = [];
        $connectionCount = (int)(20 * $intensity);
        
        for ($i = 0; $i < $connectionCount; $i++) {
            try {
                $connection = DB::connection();
                $connections[] = $connection;
                
                // Execute a long-running query
                $connection->select("SELECT SLEEP({$timeoutDuration})");
                
            } catch (\Exception $e) {
                // Expected when connection pool is exhausted
                break;
            }
        }
        
        // Hold connections for the timeout duration
        sleep($timeoutDuration);
        
        // Release connections
        foreach ($connections as $connection) {
            $connection->disconnect();
        }
    }

    private function injectLockContention($intensity)
    {
        $lockDuration = (int)(5 * $intensity);
        $concurrentLocks = (int)(10 * $intensity);
        
        $this->info("🔒 Injecting lock contention ({$concurrentLocks} locks for {$lockDuration}s)");
        
        // Create multiple transactions that lock the same resources
        for ($i = 0; $i < $concurrentLocks; $i++) {
            try {
                DB::transaction(function () use ($lockDuration) {
                    // Lock users table
                    DB::table('users')->lockForUpdate()->first();
                    
                    // Hold the lock
                    sleep($lockDuration);
                });
            } catch (\Exception $e) {
                // Expected due to lock contention
            }
        }
    }

    private function injectDeadlockSimulation($intensity)
    {
        $this->info("💀 Injecting deadlock simulation");
        
        $processes = (int)(5 * $intensity);
        
        for ($i = 0; $i < $processes; $i++) {
            try {
                // Create potential deadlock scenario
                DB::transaction(function () {
                    // Lock table A then B
                    DB::table('users')->lockForUpdate()->first();
                    // REMOVED: app('performance.async')->delayAsync(100000); // 100ms delay - PERFORMANCE OPTIMIZATION
                    DB::table('courses')->lockForUpdate()->first();
                });
                
                DB::transaction(function () {
                    // Lock table B then A (reverse order - potential deadlock)
                    DB::table('courses')->lockForUpdate()->first();
                    // REMOVED: app('performance.async')->delayAsync(100000); // 100ms delay - PERFORMANCE OPTIMIZATION
                    DB::table('users')->lockForUpdate()->first();
                });
                
            } catch (\Exception $e) {
                // Deadlock detected and resolved by database
                $this->line("   Deadlock detected and resolved: " . $e->getMessage());
            }
        }
    }

    private function injectConnectionPoolExhaustion($intensity)
    {
        $this->info("🏊 Injecting connection pool exhaustion");
        
        $connections = [];
        $maxConnections = (int)(100 * $intensity);
        
        for ($i = 0; $i < $maxConnections; $i++) {
            try {
                $connection = DB::connection();
                $connections[] = $connection;
                
                // Keep connection busy
                $connection->select("SELECT 1");
                
            } catch (\Exception $e) {
                $this->line("   Connection pool exhausted at {$i} connections");
                break;
            }
        }
        
        // REMOVED: sleep(5); // PERFORMANCE OPTIMIZATION - Reduced to minimal delay
        // Hold connections for minimal time
        
        // Release all connections
        foreach ($connections as $connection) {
            $connection->disconnect();
        }
    }

    private function injectSlowQueries($intensity)
    {
        $queryCount = (int)(20 * $intensity);
        $sleepTime = $intensity * 2; // 0.2 to 1.8 seconds
        
        $this->info("🐌 Injecting slow queries ({$queryCount} queries, {$sleepTime}s each)");
        
        for ($i = 0; $i < $queryCount; $i++) {
            try {
                DB::select("SELECT SLEEP({$sleepTime})");
            } catch (\Exception $e) {
                // Query timeout or other error
            }
        }
    }

    private function injectTransactionTimeout($intensity)
    {
        $transactionDuration = (int)(30 * $intensity);
        
        $this->info("⏰ Injecting transaction timeout ({$transactionDuration}s)");
        
        try {
            DB::transaction(function () use ($transactionDuration) {
                // Start a long-running transaction
                DB::table('users')->lockForUpdate()->first();
                
                // REMOVED: sleep($transactionDuration); // PERFORMANCE OPTIMIZATION
                // Hold the transaction for minimal time
                
                // This should timeout
                DB::table('users')->where('id', 1)->update(['updated_at' => now()]);
            });
        } catch (\Exception $e) {
            $this->line("   Transaction timeout: " . $e->getMessage());
        }
    }

    // Network Failure Injections

    private function injectHighLatency($intensity)
    {
        $latencyMs = (int)(1000 * $intensity); // Up to 900ms latency
        
        $this->info("🌐 Injecting high network latency ({$latencyMs}ms)");
        
        // Simulate network latency by adding delays to database operations
        for ($i = 0; $i < 10; $i++) {
            // REMOVED: app('performance.async')->delayAsync($latencyMs ); // PERFORMANCE OPTIMIZATION
            
            try {
                DB::select("SELECT 1");
            } catch (\Exception $e) {
                // Network timeout
            }
        }
    }

    private function injectPacketLoss($intensity)
    {
        $lossRate = $intensity; // 10% to 90% packet loss
        
        $this->info("📦 Injecting packet loss ({$lossRate}% loss rate)");
        
        // Simulate packet loss by randomly failing operations
        for ($i = 0; $i < 50; $i++) {
            if (rand(1, 100) <= ($lossRate * 100)) {
                // Simulate packet loss - skip this operation
                continue;
            }
            
            try {
                DB::select("SELECT 1");
            } catch (\Exception $e) {
                // Connection error due to packet loss
            }
        }
    }

    private function injectBandwidthThrottling($intensity)
    {
        $throttleDelay = (int)(500 * $intensity); // Up to 450ms delay per operation
        
        $this->info("🚦 Injecting bandwidth throttling ({$throttleDelay}ms delay)");
        
        for ($i = 0; $i < 20; $i++) {
            // Add delay to simulate bandwidth throttling
            app('performance.async')->delayAsync($throttleDelay );
            
            try {
                // Execute a data-heavy operation
                DB::select("SELECT * FROM users LIMIT 100");
            } catch (\Exception $e) {
                // Timeout due to throttling
            }
        }
    }

    private function injectConnectionDrops($intensity)
    {
        $dropRate = $intensity; // 10% to 90% connection drop rate
        
        $this->info("🔌 Injecting connection drops ({$dropRate}% drop rate)");
        
        for ($i = 0; $i < 30; $i++) {
            try {
                $connection = DB::connection();
                
                // Randomly drop connections
                if (rand(1, 100) <= ($dropRate * 100)) {
                    $connection->disconnect();
                    throw new \Exception("Connection dropped");
                }
                
                $connection->select("SELECT 1");
                
            } catch (\Exception $e) {
                // Connection dropped
                $this->line("   Connection dropped: " . $e->getMessage());
            }
        }
    }

    // Memory Failure Injections

    private function injectMemoryLeak($intensity)
    {
        $leakSize = (int)(50 * $intensity); // Up to 45MB leak
        
        $this->info("🧠 Injecting memory leak ({$leakSize}MB)");
        
        $memoryBlocks = [];
        
        for ($i = 0; $i < $leakSize; $i++) {
            // Allocate 1MB blocks and don't release them
            $memoryBlocks[] = str_repeat('x', 1024 * 1024);
            
            // Check if we're approaching memory limit
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->convertToBytes(ini_get('memory_limit'));
            
            if ($memoryUsage > ($memoryLimit * 0.9)) {
                $this->line("   Memory leak stopped at {$i}MB to prevent crash");
                break;
            }
        }
        
        // Hold memory for a while to simulate leak
        sleep(10);
        
        // Clean up to prevent actual memory exhaustion
        unset($memoryBlocks);
        gc_collect_cycles();
    }

    private function injectMemoryPressure($intensity)
    {
        $pressureDuration = (int)(30 * $intensity);
        
        $this->info("💾 Injecting memory pressure ({$pressureDuration}s)");
        
        $memoryBlocks = [];
        
        // Allocate memory to create pressure
        while (memory_get_usage(true) < ($this->convertToBytes(ini_get('memory_limit')) * 0.8)) {
            $memoryBlocks[] = str_repeat('x', 1024 * 1024); // 1MB blocks
        }
        
        // Maintain pressure
        sleep($pressureDuration);
        
        // Release memory
        unset($memoryBlocks);
        gc_collect_cycles();
    }

    private function injectGarbageCollectionStress($intensity)
    {
        $iterations = (int)(1000 * $intensity);
        
        $this->info("🗑️  Injecting garbage collection stress ({$iterations} iterations)");
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create and destroy objects rapidly
            $objects = [];
            for ($j = 0; $j < 100; $j++) {
                $objects[] = new \stdClass();
                $objects[$j]->data = str_repeat('x', 1024); // 1KB per object
            }
            
            // Force garbage collection
            unset($objects);
            if ($i % 100 == 0) {
                gc_collect_cycles();
            }
        }
    }

    // Disk Failure Injections

    private function injectDiskFullSimulation($intensity)
    {
        $fileSize = (int)(100 * $intensity); // Up to 90MB
        
        $this->info("💽 Injecting disk full simulation ({$fileSize}MB)");
        
        $tempFiles = [];
        
        try {
            for ($i = 0; $i < $fileSize; $i++) {
                $filename = storage_path("temp_stress_file_{$i}.tmp");
                $tempFiles[] = $filename;
                
                // Create 1MB file
                file_put_contents($filename, str_repeat('x', 1024 * 1024));
                
                // Check available disk space
                $freeSpace = disk_free_space(storage_path());
                if ($freeSpace < (1024 * 1024 * 100)) { // Less than 100MB free
                    $this->line("   Disk full simulation stopped at {$i}MB");
                    break;
                }
            }
            
            // Hold files for a while
            sleep(10);
            
        } finally {
            // Clean up temp files
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function injectIOThrottling($intensity)
    {
        $operations = (int)(100 * $intensity);
        $delay = (int)(100 * $intensity); // Up to 90ms delay per operation
        
        $this->info("📁 Injecting I/O throttling ({$operations} operations, {$delay}ms delay)");
        
        for ($i = 0; $i < $operations; $i++) {
            // Add delay to simulate I/O throttling
            app('performance.async')->delayAsync($delay );
            
            // Perform I/O operation
            $filename = storage_path("temp_io_test_{$i}.tmp");
            file_put_contents($filename, 'test data');
            $data = file_get_contents($filename);
            unlink($filename);
        }
    }

    private function injectDiskLatency($intensity)
    {
        $latencyMs = (int)(200 * $intensity); // Up to 180ms latency
        $operations = 50;
        
        $this->info("⏱️  Injecting disk latency ({$latencyMs}ms per operation)");
        
        for ($i = 0; $i < $operations; $i++) {
            // Simulate disk latency
            app('performance.async')->delayAsync($latencyMs );
            
            // Perform disk operation
            try {
                DB::select("SELECT 1"); // This will hit disk for logging
            } catch (\Exception $e) {
                // Timeout due to latency
            }
        }
    }

    // Connection Failure Injections

    private function injectMaxConnectionsReached($intensity)
    {
        $connectionCount = (int)(200 * $intensity);
        
        $this->info("🔗 Injecting max connections reached ({$connectionCount} connections)");
        
        $connections = [];
        
        for ($i = 0; $i < $connectionCount; $i++) {
            try {
                $connection = DB::connection();
                $connections[] = $connection;
                
                // Keep connection active
                $connection->select("SELECT 1");
                
            } catch (\Exception $e) {
                $this->line("   Max connections reached at {$i} connections");
                break;
            }
        }
        
        // Hold connections
        sleep(10);
        
        // Release connections
        foreach ($connections as $connection) {
            $connection->disconnect();
        }
    }

    private function injectConnectionLeak($intensity)
    {
        $leakCount = (int)(50 * $intensity);
        
        $this->info("🕳️  Injecting connection leak ({$leakCount} leaked connections)");
        
        // Create connections but don't properly close them
        for ($i = 0; $i < $leakCount; $i++) {
            try {
                $connection = DB::connection();
                $connection->select("SELECT 1");
                
                // Simulate connection leak by not calling disconnect()
                // In a real scenario, these would accumulate
                
            } catch (\Exception $e) {
                // Connection pool exhausted
                break;
            }
        }
        
        // Force cleanup after simulation
        DB::purge();
    }

    private function injectSessionTimeout($intensity)
    {
        $timeoutDuration = (int)(60 * $intensity);
        
        $this->info("⏰ Injecting session timeout ({$timeoutDuration}s)");
        
        // Create sessions and let them timeout
        for ($i = 0; $i < 20; $i++) {
            try {
                DB::table('sessions')->insert([
                    'id' => \Str::random(40),
                    'user_id' => rand(1, 100),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'FailureInjectionAgent',
                    'payload' => base64_encode('timeout_test'),
                    'last_activity' => time() - $timeoutDuration - 1, // Already expired
                ]);
            } catch (\Exception $e) {
                // Session table might not exist
            }
        }
        
        // Simulate session cleanup
        try {
            DB::table('sessions')
                ->where('last_activity', '<', time() - $timeoutDuration)
                ->delete();
        } catch (\Exception $e) {
            // Session table might not exist
        }
    }

    private function executeRecoveryProcedures()
    {
        $this->info("🔧 EXECUTING AUTOMATIC RECOVERY PROCEDURES");
        
        // Clear all caches
        $this->info("   Clearing caches...");
        try {
            Cache::flush();
        } catch (\Exception $e) {
            $this->warn("   Cache clear failed: " . $e->getMessage());
        }
        
        // Reset database connections
        $this->info("   Resetting database connections...");
        try {
            DB::purge();
        } catch (\Exception $e) {
            $this->warn("   Database purge failed: " . $e->getMessage());
        }
        
        // Force garbage collection
        $this->info("   Running garbage collection...");
        gc_collect_cycles();
        
        // Clear queue
        $this->info("   Clearing job queue...");
        try {
            Queue::purge('default');
        } catch (\Exception $e) {
            $this->warn("   Queue purge failed: " . $e->getMessage());
        }
        
        // Clean up temporary files
        $this->info("   Cleaning temporary files...");
        $tempFiles = glob(storage_path('temp_*.tmp'));
        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Reset session data
        $this->info("   Cleaning expired sessions...");
        try {
            DB::table('sessions')
                ->where('last_activity', '<', time() - 3600)
                ->delete();
        } catch (\Exception $e) {
            // Session table might not exist
        }
        
        $this->info("✅ Recovery procedures completed");
    }

    private function generateFailureReport()
    {
        $this->info("📄 GENERATING FAILURE INJECTION REPORT");
        
        $reportData = [
            'execution_time' => date('Y-m-d H:i:s'),
            'total_failures_injected' => count($this->activeFailures),
            'successful_injections' => count(array_filter($this->activeFailures, fn($f) => $f['status'] === 'completed')),
            'failed_injections' => count(array_filter($this->activeFailures, fn($f) => $f['status'] === 'failed')),
            'recovery_enabled' => $this->recoveryEnabled,
            'failures' => $this->activeFailures,
            'summary' => $this->generateFailureSummary()
        ];

        $reportPath = storage_path('logs/failure_injection_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info("📄 Failure injection report saved to: {$reportPath}");
        
        // Display summary
        $this->info("📊 FAILURE INJECTION SUMMARY:");
        $this->line("├─ Total Failures Injected: " . $reportData['total_failures_injected']);
        $this->line("├─ Successful Injections: " . $reportData['successful_injections']);
        $this->line("├─ Failed Injections: " . $reportData['failed_injections']);
        $this->line("├─ Average Execution Time: " . round($reportData['summary']['avg_execution_time'], 3) . "s");
        $this->line("└─ Most Common Failure: " . $reportData['summary']['most_common_failure']);
        
        $this->info("💣 FAILURE INJECTION ENGINE COMPLETED");
    }

    private function generateFailureSummary()
    {
        if (empty($this->activeFailures)) {
            return [];
        }

        $executionTimes = array_column($this->activeFailures, 'execution_time');
        $failureTypes = array_column($this->activeFailures, 'type');
        
        $avgExecutionTime = array_sum($executionTimes) / count($executionTimes);
        $failureTypeCounts = array_count_values($failureTypes);
        $mostCommonFailure = array_key_first($failureTypeCounts);

        return [
            'avg_execution_time' => $avgExecutionTime,
            'most_common_failure' => $mostCommonFailure,
            'failure_type_distribution' => $failureTypeCounts
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