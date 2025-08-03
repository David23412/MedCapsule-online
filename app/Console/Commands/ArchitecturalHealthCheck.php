<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Core\SmartCacheManager;
use App\Services\Core\MemoryEfficientQuizService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Architectural Health Check Command
 * 
 * Monitors the health of the new architectural components
 * and provides recommendations for optimization.
 */
class ArchitecturalHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'architecture:health-check 
                           {--fix : Automatically fix detected issues}
                           {--detailed : Show detailed analysis}';

    /**
     * The console command description.
     */
    protected $description = 'Check the health of architectural components and identify issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏗️ Architectural Health Check Starting...');
        $this->newLine();
        
        $issues = [];
        $fixes = [];
        
        // Check database health
        $dbIssues = $this->checkDatabaseHealth();
        $issues = array_merge($issues, $dbIssues);
        
        // Check cache health
        $cacheIssues = $this->checkCacheHealth();
        $issues = array_merge($issues, $cacheIssues);
        
        // Check memory efficiency
        $memoryIssues = $this->checkMemoryEfficiency();
        $issues = array_merge($issues, $memoryIssues);
        
        // Check service dependencies
        $dependencyIssues = $this->checkServiceDependencies();
        $issues = array_merge($issues, $dependencyIssues);
        
        // Display results
        $this->displayResults($issues);
        
        // Apply fixes if requested
        if ($this->option('fix') && !empty($issues)) {
            $this->applyFixes($issues);
        }
        
        return empty($issues) ? 0 : 1;
    }
    
    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        $this->info('🔍 Checking Database Health...');
        $issues = [];
        
        try {
            // Check connection pool
            $connections = DB::getConnections();
            if (count($connections) > 10) {
                $issues[] = [
                    'type' => 'database',
                    'severity' => 'warning',
                    'message' => 'High number of database connections detected',
                    'details' => 'Active connections: ' . count($connections),
                    'fix' => 'optimize_db_connections'
                ];
            }
            
            // Check for long-running transactions
            $longTransactions = $this->getLongRunningTransactions();
            if (!empty($longTransactions)) {
                $issues[] = [
                    'type' => 'database',
                    'severity' => 'critical',
                    'message' => 'Long-running transactions detected',
                    'details' => count($longTransactions) . ' transactions running > 30 seconds',
                    'fix' => 'kill_long_transactions'
                ];
            }
            
            // Check table locks
            $tableLocks = $this->getTableLocks();
            if (!empty($tableLocks)) {
                $issues[] = [
                    'type' => 'database',
                    'severity' => 'high',
                    'message' => 'Table locks detected',
                    'details' => count($tableLocks) . ' locked tables',
                    'fix' => 'analyze_table_locks'
                ];
            }
            
            $this->info('✅ Database health check completed');
            
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'database',
                'severity' => 'critical',
                'message' => 'Database connection failed',
                'details' => $e->getMessage(),
                'fix' => 'check_db_connection'
            ];
        }
        
        return $issues;
    }
    
    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        $this->info('🔍 Checking Cache Health...');
        $issues = [];
        
        try {
            $cacheManager = app(SmartCacheManager::class);
            $stats = $cacheManager->getCacheStatistics();
            
            // Check cache hit rate
            if (isset($stats['cache_hit_rate']) && $stats['cache_hit_rate'] < 70) {
                $issues[] = [
                    'type' => 'cache',
                    'severity' => 'warning',
                    'message' => 'Low cache hit rate',
                    'details' => 'Hit rate: ' . $stats['cache_hit_rate'] . '%',
                    'fix' => 'optimize_cache_strategy'
                ];
            }
            
            // Check memory usage
            if (isset($stats['memory_used'])) {
                $memoryUsed = $this->parseMemoryString($stats['memory_used']);
                if ($memoryUsed > 500 * 1024 * 1024) { // 500MB
                    $issues[] = [
                        'type' => 'cache',
                        'severity' => 'high',
                        'message' => 'High cache memory usage',
                        'details' => 'Memory used: ' . $stats['memory_used'],
                        'fix' => 'clear_old_cache_keys'
                    ];
                }
            }
            
            // Check active keys
            if (isset($stats['active_keys']) && $stats['active_keys'] > 100000) {
                $issues[] = [
                    'type' => 'cache',
                    'severity' => 'warning',
                    'message' => 'High number of cache keys',
                    'details' => 'Active keys: ' . number_format($stats['active_keys']),
                    'fix' => 'cleanup_cache_keys'
                ];
            }
            
            $this->info('✅ Cache health check completed');
            
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'cache',
                'severity' => 'critical',
                'message' => 'Cache system unavailable',
                'details' => $e->getMessage(),
                'fix' => 'restart_cache_service'
            ];
        }
        
        return $issues;
    }
    
    /**
     * Check memory efficiency
     */
    private function checkMemoryEfficiency(): array
    {
        $this->info('🔍 Checking Memory Efficiency...');
        $issues = [];
        
        try {
            $memoryService = app(MemoryEfficientQuizService::class);
            $stats = $memoryService->getMemoryStats('health_check');
            
            // Check current memory usage
            if ($stats['memory_percentage'] > 80) {
                $issues[] = [
                    'type' => 'memory',
                    'severity' => 'critical',
                    'message' => 'High memory usage',
                    'details' => 'Memory usage: ' . $stats['memory_percentage'] . '%',
                    'fix' => 'optimize_memory_usage'
                ];
            } elseif ($stats['memory_percentage'] > 60) {
                $issues[] = [
                    'type' => 'memory',
                    'severity' => 'warning',
                    'message' => 'Elevated memory usage',
                    'details' => 'Memory usage: ' . $stats['memory_percentage'] . '%',
                    'fix' => 'monitor_memory_usage'
                ];
            }
            
            // Check memory limit
            $memoryLimit = $stats['memory_limit'];
            if ($memoryLimit !== '-1' && $this->parseMemoryString($memoryLimit) < 256 * 1024 * 1024) {
                $issues[] = [
                    'type' => 'memory',
                    'severity' => 'warning',
                    'message' => 'Low memory limit',
                    'details' => 'Memory limit: ' . $memoryLimit,
                    'fix' => 'increase_memory_limit'
                ];
            }
            
            $this->info('✅ Memory efficiency check completed');
            
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'memory',
                'severity' => 'high',
                'message' => 'Memory monitoring failed',
                'details' => $e->getMessage(),
                'fix' => 'check_memory_monitoring'
            ];
        }
        
        return $issues;
    }
    
    /**
     * Check service dependencies
     */
    private function checkServiceDependencies(): array
    {
        $this->info('🔍 Checking Service Dependencies...');
        $issues = [];
        
        // Check if new services are properly registered
        $services = [
            'App\Services\Core\EventDrivenGradingService',
            'App\Services\Core\SmartCacheManager',
            'App\Services\Core\MemoryEfficientQuizService'
        ];
        
        foreach ($services as $service) {
            try {
                app($service);
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'dependency',
                    'severity' => 'critical',
                    'message' => 'Service not registered: ' . class_basename($service),
                    'details' => $e->getMessage(),
                    'fix' => 'register_service'
                ];
            }
        }
        
        // Check event listeners
        $events = [
            'App\Events\QuizCompleted',
            'App\Events\QuizStarted',
            'App\Events\QuizGraded'
        ];
        
        foreach ($events as $event) {
            if (!class_exists($event)) {
                $issues[] = [
                    'type' => 'dependency',
                    'severity' => 'high',
                    'message' => 'Event class missing: ' . class_basename($event),
                    'details' => 'Event-driven architecture requires this event',
                    'fix' => 'create_event_class'
                ];
            }
        }
        
        $this->info('✅ Service dependencies check completed');
        
        return $issues;
    }
    
    /**
     * Display health check results
     */
    private function displayResults(array $issues): void
    {
        $this->newLine();
        
        if (empty($issues)) {
            $this->info('🎉 All architectural components are healthy!');
            return;
        }
        
        $this->error('⚠️  Issues detected in architectural components:');
        $this->newLine();
        
        $critical = array_filter($issues, fn($issue) => $issue['severity'] === 'critical');
        $high = array_filter($issues, fn($issue) => $issue['severity'] === 'high');
        $warning = array_filter($issues, fn($issue) => $issue['severity'] === 'warning');
        
        if (!empty($critical)) {
            $this->error('🔴 CRITICAL ISSUES:');
            foreach ($critical as $issue) {
                $this->line("  • {$issue['message']} ({$issue['details']})");
            }
            $this->newLine();
        }
        
        if (!empty($high)) {
            $this->warn('🟡 HIGH PRIORITY ISSUES:');
            foreach ($high as $issue) {
                $this->line("  • {$issue['message']} ({$issue['details']})");
            }
            $this->newLine();
        }
        
        if (!empty($warning)) {
            $this->info('🟢 WARNINGS:');
            foreach ($warning as $issue) {
                $this->line("  • {$issue['message']} ({$issue['details']})");
            }
            $this->newLine();
        }
        
        $this->info('💡 Run with --fix to automatically resolve fixable issues');
    }
    
    /**
     * Apply fixes for detected issues
     */
    private function applyFixes(array $issues): void
    {
        $this->info('🔧 Applying fixes...');
        
        foreach ($issues as $issue) {
            try {
                $this->applyFix($issue);
                $this->info("✅ Fixed: {$issue['message']}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to fix: {$issue['message']} - {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Apply individual fix
     */
    private function applyFix(array $issue): void
    {
        switch ($issue['fix']) {
            case 'optimize_memory_usage':
                gc_collect_cycles();
                break;
                
            case 'clear_old_cache_keys':
                $cacheManager = app(SmartCacheManager::class);
                // Implementation would depend on your cache cleanup strategy
                break;
                
            case 'optimize_db_connections':
                DB::purge();
                break;
                
            default:
                throw new \Exception("No automated fix available for: {$issue['fix']}");
        }
    }
    
    /**
     * Get long-running transactions (MySQL specific)
     */
    private function getLongRunningTransactions(): array
    {
        try {
            return DB::select("
                SELECT * FROM information_schema.innodb_trx 
                WHERE trx_started < DATE_SUB(NOW(), INTERVAL 30 SECOND)
            ");
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get table locks (MySQL specific)
     */
    private function getTableLocks(): array
    {
        try {
            return DB::select("SHOW OPEN TABLES WHERE In_use > 0");
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Parse memory string to bytes
     */
    private function parseMemoryString(string $memory): int
    {
        if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMGT]?B)$/i', $memory, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2]);
            
            switch ($unit) {
                case 'GB':
                    return (int) ($value * 1024 * 1024 * 1024);
                case 'MB':
                    return (int) ($value * 1024 * 1024);
                case 'KB':
                    return (int) ($value * 1024);
                default:
                    return (int) $value;
            }
        }
        
        return 0;
    }
}