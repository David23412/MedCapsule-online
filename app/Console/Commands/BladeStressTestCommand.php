<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\BladeStressTestSeeder;
use Illuminate\Support\Facades\Artisan;

class BladeStressTestCommand extends Command
{
    protected $signature = 'blade:stress-test 
                           {--multiplier=1 : Stress test multiplier (default: 1, use higher values for more aggressive testing)}
                           {--memory-limit=512 : Memory limit in MB (default: 512)}
                           {--render-timeout=50 : Maximum render time in milliseconds (default: 50)}
                           {--cache-target=95 : Cache hit ratio target percentage (default: 95)}
                           {--report-only : Only generate report from existing data}';

    protected $description = '🔥 LETHAL BLADE STRESS TESTING ENGINE - Destroy your view system under extreme load';

    public function handle(): int
    {
        $this->info('');
        $this->info('🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥');
        $this->info('🔥                                                                🔥');
        $this->info('🔥           LETHAL BLADE STRESS TESTING ENGINE                   🔥');
        $this->info('🔥                                                                🔥');
        $this->info('🔥    ⚠️  WARNING: THIS WILL DESTROY YOUR VIEW SYSTEM ⚠️         🔥');
        $this->info('🔥                UNDER EXTREME LOAD CONDITIONS                   🔥');
        $this->info('🔥                                                                🔥');
        $this->info('🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥');
        $this->info('');

        if (!$this->option('report-only')) {
            $this->warn('⚠️  This stress test will:');
            $this->warn('   • Generate MILLIONS of view renders');
            $this->warn('   • Consume significant system resources');
            $this->warn('   • Test memory leak scenarios');
            $this->warn('   • Simulate failure conditions');
            $this->warn('   • Push your system to its limits');
            $this->info('');

            if (!$this->confirm('Are you absolutely sure you want to proceed with this LETHAL stress test?')) {
                $this->info('💀 Stress test aborted. Your system lives another day.');
                return 0;
            }

            $this->info('');
            $this->info('🚀 Initiating LETHAL Blade stress testing sequence...');
            $this->info('');

            // Display configuration
            $this->displayConfiguration();

            // Run the stress test seeder
            $this->runStressTest();
        } else {
            $this->info('📊 Generating report from existing stress test data...');
            $this->generateReportOnly();
        }

        return 0;
    }

    private function displayConfiguration(): void
    {
        $this->info('⚙️  STRESS TEST CONFIGURATION:');
        $this->info('   • Multiplier: ' . $this->option('multiplier') . 'x');
        $this->info('   • Memory Limit: ' . $this->option('memory-limit') . 'MB');
        $this->info('   • Render Timeout: ' . $this->option('render-timeout') . 'ms');
        $this->info('   • Cache Target: ' . $this->option('cache-target') . '%');
        $this->info('');
    }

    private function runStressTest(): void
    {
        try {
            // Set memory limit
            $memoryLimit = $this->option('memory-limit') . 'M';
            ini_set('memory_limit', $memoryLimit);
            
            // Set time limit to unlimited for stress testing
            set_time_limit(0);

            $this->info('🔥 Starting Blade stress test seeder...');
            
            // Create and run the seeder
            $seeder = new BladeStressTestSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->info('');
            $this->info('✅ BLADE STRESS TEST COMPLETED SUCCESSFULLY!');
            $this->info('');
            $this->displayResults();

        } catch (\Throwable $e) {
            $this->error('💀 CRITICAL FAILURE DURING STRESS TEST:');
            $this->error('   Error: ' . $e->getMessage());
            $this->error('   File: ' . $e->getFile() . ':' . $e->getLine());
            
            if ($this->option('verbose')) {
                $this->error('   Stack Trace:');
                $this->error($e->getTraceAsString());
            }
            
            $this->info('');
            $this->warn('🔧 RECOVERY RECOMMENDATIONS:');
            $this->warn('   • Check system resources (memory, CPU)');
            $this->warn('   • Reduce stress test multiplier');
            $this->warn('   • Increase memory limit');
            $this->warn('   • Check application logs for details');
            
            return;
        }
    }

    private function generateReportOnly(): void
    {
        $this->info('📊 Scanning for existing stress test reports...');
        
        $logPath = storage_path('logs');
        $reportFiles = glob($logPath . '/blade_stress_test_report_*.json');
        
        if (empty($reportFiles)) {
            $this->warn('⚠️  No existing stress test reports found.');
            $this->info('   Run the full stress test first: php artisan blade:stress-test');
            return;
        }
        
        // Get the most recent report
        $latestReport = max($reportFiles);
        $this->info('📄 Found latest report: ' . basename($latestReport));
        
        $reportData = json_decode(file_get_contents($latestReport), true);
        
        if (!$reportData) {
            $this->error('❌ Failed to parse report data');
            return;
        }
        
        $this->displayReportSummary($reportData);
    }

    private function displayResults(): void
    {
        $logPath = storage_path('logs');
        $reportFiles = glob($logPath . '/blade_stress_test_report_*.json');
        $summaryFiles = glob($logPath . '/blade_stress_test_summary_*.md');
        
        if (!empty($reportFiles)) {
            $latestReport = max($reportFiles);
            $latestSummary = !empty($summaryFiles) ? max($summaryFiles) : null;
            
            $this->info('📊 STRESS TEST RESULTS:');
            $this->info('   📄 Full Report: ' . $latestReport);
            if ($latestSummary) {
                $this->info('   📋 Summary: ' . $latestSummary);
            }
            $this->info('');
            
            // Display quick summary
            $reportData = json_decode(file_get_contents($latestReport), true);
            if ($reportData) {
                $this->displayReportSummary($reportData);
            }
        }
    }

    private function displayReportSummary(array $reportData): void
    {
        $summary = $reportData['test_summary'] ?? [];
        
        $this->info('🎯 QUICK SUMMARY:');
        if (isset($summary['total_duration'])) {
            $this->info('   ⏱️  Duration: ' . round($summary['total_duration'], 2) . ' seconds');
        }
        if (isset($summary['total_memory_used'])) {
            $this->info('   💾 Memory Used: ' . $this->formatBytes($summary['total_memory_used']));
        }
        if (isset($summary['total_renders'])) {
            $this->info('   🎨 Total Renders: ' . number_format($summary['total_renders']));
        }
        if (isset($summary['avg_render_time'])) {
            $this->info('   ⚡ Avg Render Time: ' . round($summary['avg_render_time'] * 1000, 2) . 'ms');
        }
        if (isset($summary['total_errors'])) {
            $this->info('   ❌ Total Errors: ' . number_format($summary['total_errors']));
        }
        
        $this->info('');
        
        // Display top performance issues
        $recommendations = $reportData['recommendations'] ?? [];
        $highPriorityRecs = array_filter($recommendations, function($rec) {
            return $rec['priority'] === 'high';
        });
        
        if (!empty($highPriorityRecs)) {
            $this->warn('🚨 HIGH PRIORITY ISSUES FOUND:');
            foreach (array_slice($highPriorityRecs, 0, 5) as $rec) {
                $this->warn('   • ' . ($rec['test'] ?? $rec['view'] ?? 'Unknown') . ': ' . $rec['issue']);
            }
            $this->info('');
        }
        
        // Display view inventory summary
        $viewInventory = $reportData['view_inventory'] ?? [];
        if (!empty($viewInventory)) {
            $totalViews = count($viewInventory);
            $unusedViews = count(array_filter($viewInventory, function($view) {
                return ($view['usage_count'] ?? 1) === 0;
            }));
            $complexViews = count(array_filter($viewInventory, function($view) {
                return ($view['complexity'] ?? 0) > 10;
            }));
            
            $this->info('📋 VIEW INVENTORY:');
            $this->info('   📄 Total Views: ' . $totalViews);
            $this->info('   🗑️  Unused Views: ' . $unusedViews);
            $this->info('   🧩 Complex Views: ' . $complexViews);
            $this->info('');
        }
        
        // Display stress test results summary
        $stressResults = $reportData['stress_test_results'] ?? [];
        if (!empty($stressResults)) {
            $this->info('🔥 TOP STRESS TEST RESULTS:');
            
            $sortedResults = $stressResults;
            uasort($sortedResults, function($a, $b) {
                return ($b['success_rate'] ?? 0) <=> ($a['success_rate'] ?? 0);
            });
            
            $count = 0;
            foreach ($sortedResults as $testName => $result) {
                if ($count >= 5) break;
                
                $successRate = round($result['success_rate'] ?? 0, 1);
                $duration = round($result['duration'] ?? 0, 2);
                $iterations = number_format($result['iterations'] ?? 0);
                
                $status = $successRate >= 95 ? '✅' : ($successRate >= 80 ? '⚠️' : '❌');
                $this->info("   {$status} {$testName}: {$successRate}% success, {$iterations} iterations, {$duration}s");
                
                $count++;
            }
        }
        
        $this->info('');
        $this->info('🎯 For detailed analysis, check the full report files above.');
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * LETHAL CACHE CLEAR: Force clear all quiz caches to prevent deleted questions
     */
    private function lethalCacheClear(): void
    {
        try {
            $this->info('💀 LETHAL FIX: Starting nuclear cache invalidation...');
            
            // Clear all Redis caches
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $pipeline = $redis->pipeline();
            
            // LETHAL CLEAR: Clear ALL quiz-related patterns
            $patterns = [
                'quiz_questions_*',
                'random_quiz_*',
                'question:*',
                'available_sources_*',
                'quiz_stats_*',
                'tutor_progress_*'
            ];
            
            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                foreach ($keys as $key) {
                    $pipeline->del($key);
                }
            }
            
            // Execute all deletions atomically
            $pipeline->exec();
            
            // NUCLEAR CLEAR: Clear all Laravel caches
            \Illuminate\Support\Facades\Cache::flush();
            
            // LETHAL CLEAR: Clear all sessions
            \Illuminate\Support\Facades\Session::flush();
            
            $this->info('💀 LETHAL FIX: Nuclear cache invalidation completed!');
            $this->info('💀 LETHAL FIX: All quiz caches cleared!');
            $this->info('💀 LETHAL FIX: Deleted questions will NEVER appear again!');
            
        } catch (\Exception $e) {
            $this->error('💀 LETHAL FIX: Error during nuclear cache invalidation: ' . $e->getMessage());
        }
    }
}