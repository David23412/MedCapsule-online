<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Resilience\ConnectionManager;
use App\Services\Resilience\ConfigurationGuard;
use App\Services\Resilience\QueueIntegrityManager;
use App\Services\Resilience\NetworkResilienceManager;

/**
 * Resilience Check Command
 * Manual command to check and fix system resilience issues
 */
class ResilienceCheckCommand extends Command
{
    protected $signature = 'resilience:check 
                           {--fix : Automatically fix detected issues}
                           {--component= : Check specific component (db|config|queue|network|all)}';
    
    protected $description = 'Check system resilience and optionally fix issues';
    
    public function handle()
    {
        $this->info('🛡️ SYSTEM RESILIENCE CHECK INITIATED');
        $this->info('=====================================');
        
        $component = $this->option('component') ?? 'all';
        $autoFix = $this->option('fix');
        
        $results = [];
        
        if ($component === 'all' || $component === 'db') {
            $results['database'] = $this->checkDatabaseResilience($autoFix);
        }
        
        if ($component === 'all' || $component === 'config') {
            $results['configuration'] = $this->checkConfigurationResilience($autoFix);
        }
        
        if ($component === 'all' || $component === 'queue') {
            $results['queue'] = $this->checkQueueResilience($autoFix);
        }
        
        if ($component === 'all' || $component === 'network') {
            $results['network'] = $this->checkNetworkResilience($autoFix);
        }
        
        $this->displayResults($results);
        
        return $this->determineExitCode($results);
    }
    
    /**
     * Check database resilience
     */
    private function checkDatabaseResilience(bool $autoFix): array
    {
        $this->info('🔌 Checking Database Resilience...');
        
        $connectionManager = app(ConnectionManager::class);
        $results = [
            'component' => 'Database',
            'tests' => [],
            'overall_status' => 'pass'
        ];
        
        // Test 1: Database Connection
        $this->line('  Testing database connection...');
        $dbConnected = $connectionManager->testDatabaseConnection();
        $results['tests']['connection'] = [
            'name' => 'Database Connection',
            'status' => $dbConnected ? 'pass' : 'fail',
            'message' => $dbConnected ? 'Connection successful' : 'Connection failed'
        ];
        
        if (!$dbConnected) {
            $results['overall_status'] = 'fail';
            if ($autoFix) {
                $this->warn('  Attempting to fix database connection...');
                // Attempt reconnection
                try {
                    \DB::reconnect();
                    $this->info('  ✅ Database reconnection successful');
                } catch (\Exception $e) {
                    $this->error('  ❌ Database reconnection failed: ' . $e->getMessage());
                }
            }
        } else {
            $this->info('  ✅ Database connection healthy');
        }
        
        // Test 2: Redis Connection
        $this->line('  Testing Redis connection...');
        $redisConnected = $connectionManager->testRedisConnection();
        $results['tests']['redis'] = [
            'name' => 'Redis Connection',
            'status' => $redisConnected ? 'pass' : 'fail',
            'message' => $redisConnected ? 'Redis connection successful' : 'Redis connection failed'
        ];
        
        if (!$redisConnected) {
            $results['overall_status'] = 'fail';
            $this->warn('  ⚠️ Redis connection failed - using database cache fallback');
        } else {
            $this->info('  ✅ Redis connection healthy');
        }
        
        return $results;
    }
    
    /**
     * Check configuration resilience
     */
    private function checkConfigurationResilience(bool $autoFix): array
    {
        $this->info('🛡️ Checking Configuration Resilience...');
        
        $configGuard = app(ConfigurationGuard::class);
        $results = [
            'component' => 'Configuration',
            'tests' => [],
            'overall_status' => 'pass'
        ];
        
        // Test configuration integrity
        $this->line('  Verifying configuration integrity...');
        $integrity = $configGuard->verifyConfigurationIntegrity();
        
        $invalidConfigs = [];
        foreach ($integrity as $key => $config) {
            if (!$config['valid'] || !$config['has_value']) {
                $invalidConfigs[] = $key;
            }
        }
        
        $results['tests']['integrity'] = [
            'name' => 'Configuration Integrity',
            'status' => empty($invalidConfigs) ? 'pass' : 'fail',
            'message' => empty($invalidConfigs) 
                ? 'All configurations valid' 
                : 'Invalid configurations: ' . implode(', ', $invalidConfigs)
        ];
        
        if (!empty($invalidConfigs)) {
            $results['overall_status'] = 'fail';
            $this->error('  ❌ Configuration integrity issues detected:');
            foreach ($invalidConfigs as $config) {
                $this->line("    - {$config}");
            }
            
            if ($autoFix) {
                $this->warn('  Configuration issues require manual intervention');
            }
        } else {
            $this->info('  ✅ Configuration integrity verified');
        }
        
        return $results;
    }
    
    /**
     * Check queue resilience
     */
    private function checkQueueResilience(bool $autoFix): array
    {
        $this->info('🚦 Checking Queue Resilience...');
        
        $queueManager = app(QueueIntegrityManager::class);
        $results = [
            'component' => 'Queue',
            'tests' => [],
            'overall_status' => 'pass'
        ];
        
        // Capture current queue state
        $this->line('  Capturing queue state...');
        $queueState = $queueManager->captureQueueState();
        
        $results['tests']['state_capture'] = [
            'name' => 'Queue State Capture',
            'status' => !empty($queueState) ? 'pass' : 'fail',
            'message' => !empty($queueState) 
                ? "Captured {$queueState['total_pending']} pending jobs" 
                : 'Failed to capture queue state'
        ];
        
        if (empty($queueState)) {
            $results['overall_status'] = 'fail';
            $this->error('  ❌ Failed to capture queue state');
        } else {
            $this->info("  ✅ Queue state captured: {$queueState['total_pending']} pending, {$queueState['total_failed']} failed");
        }
        
        // Test coordinated restart (simulation)
        if ($autoFix && !empty($queueState)) {
            $this->line('  Testing coordinated restart...');
            $restartSuccess = $queueManager->coordinatedRestart(['queue']);
            
            $results['tests']['restart'] = [
                'name' => 'Coordinated Restart',
                'status' => $restartSuccess ? 'pass' : 'fail',
                'message' => $restartSuccess ? 'Restart successful' : 'Restart failed'
            ];
            
            if (!$restartSuccess) {
                $results['overall_status'] = 'fail';
                $this->error('  ❌ Coordinated restart failed');
            } else {
                $this->info('  ✅ Coordinated restart successful');
            }
        }
        
        return $results;
    }
    
    /**
     * Check network resilience
     */
    private function checkNetworkResilience(bool $autoFix): array
    {
        $this->info('🌐 Checking Network Resilience...');
        
        $networkManager = app(NetworkResilienceManager::class);
        $results = [
            'component' => 'Network',
            'tests' => [],
            'overall_status' => 'pass'
        ];
        
        // Test network connectivity
        $this->line('  Testing network connectivity...');
        $connectivity = $networkManager->testNetworkConnectivity();
        
        $connectedServices = 0;
        $totalServices = count($connectivity);
        
        foreach ($connectivity as $service => $result) {
            if ($result['status'] === 'connected') {
                $connectedServices++;
                $this->info("    ✅ {$service}: Connected ({$result['response_time_ms']}ms)");
            } else {
                $this->error("    ❌ {$service}: {$result['status']} - {$result['error']}");
            }
        }
        
        $connectivityRate = ($connectedServices / $totalServices) * 100;
        
        $results['tests']['connectivity'] = [
            'name' => 'Network Connectivity',
            'status' => $connectivityRate >= 80 ? 'pass' : 'fail',
            'message' => "{$connectedServices}/{$totalServices} services connected ({$connectivityRate}%)"
        ];
        
        if ($connectivityRate < 80) {
            $results['overall_status'] = 'fail';
        }
        
        // Test network recovery simulation
        if ($autoFix) {
            $this->line('  Testing network recovery...');
            $recovery = $networkManager->simulateNetworkRecovery();
            
            $recoveredServices = 0;
            foreach ($recovery as $service => $result) {
                if ($result['recovered']) {
                    $recoveredServices++;
                    $this->info("    ✅ {$service}: Recovered ({$result['recovery_time_ms']}ms)");
                } else {
                    $this->error("    ❌ {$service}: Recovery failed");
                }
            }
            
            $recoveryRate = (count($recovery) > 0) ? ($recoveredServices / count($recovery)) * 100 : 0;
            
            $results['tests']['recovery'] = [
                'name' => 'Network Recovery',
                'status' => $recoveryRate >= 80 ? 'pass' : 'fail',
                'message' => "{$recoveredServices}/" . count($recovery) . " services recovered ({$recoveryRate}%)"
            ];
            
            if ($recoveryRate < 80) {
                $results['overall_status'] = 'fail';
            }
        }
        
        return $results;
    }
    
    /**
     * Display check results
     */
    private function displayResults(array $results): void
    {
        $this->info('');
        $this->info('📊 RESILIENCE CHECK RESULTS');
        $this->info('===========================');
        
        foreach ($results as $component => $result) {
            $status = $result['overall_status'] === 'pass' ? '✅' : '❌';
            $this->line("{$status} {$result['component']}: " . strtoupper($result['overall_status']));
            
            foreach ($result['tests'] as $test) {
                $testStatus = $test['status'] === 'pass' ? '✅' : '❌';
                $this->line("  {$testStatus} {$test['name']}: {$test['message']}");
            }
            $this->line('');
        }
    }
    
    /**
     * Determine command exit code
     */
    private function determineExitCode(array $results): int
    {
        foreach ($results as $result) {
            if ($result['overall_status'] === 'fail') {
                return 1; // Failure
            }
        }
        
        return 0; // Success
    }
}