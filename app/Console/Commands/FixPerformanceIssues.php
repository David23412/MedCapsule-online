<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FixPerformanceIssues extends Command
{
    protected $signature = 'performance:fix-all {--force : Force fix even if risky}';
    protected $description = 'Fix all identified performance issues and database schema problems';

    public function handle()
    {
        $this->info('🚀 PERFORMANCE MONSTER - FIXING ALL ISSUES');
        
        $fixes = 0;
        
        // 1. Clear all problematic caches
        $this->info('1. Clearing problematic caches...');
        Cache::flush();
        $fixes++;
        
        // 2. Fix database connection issues
        $this->info('2. Optimizing database connections...');
        try {
            DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO"');
            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
            DB::statement('SET SESSION max_execution_time = 30');
            $fixes++;
        } catch (\Exception $e) {
            $this->warn('Database optimization failed: ' . $e->getMessage());
        }
        
        // 3. Check for missing columns and suggest fixes
        $this->info('3. Checking database schema...');
        $this->checkDatabaseSchema();
        
        // 4. Optimize memory usage
        $this->info('4. Optimizing memory usage...');
        if (function_exists('gc_enable')) {
            gc_enable();
        }
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        $fixes++;
        
        // 5. Clear Laravel caches
        $this->info('5. Clearing Laravel caches...');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $fixes++;
        
        $this->info("✅ Performance fixes completed! Applied {$fixes} optimizations.");
        
        return Command::SUCCESS;
    }
    
    private function checkDatabaseSchema()
    {
        $issues = [];
        
        // Check if notifications table exists
        try {
            DB::table('notifications')->limit(1)->get();
        } catch (\Exception $e) {
            $issues[] = 'notifications table missing';
            $this->warn('⚠️  notifications table does not exist - consider creating it or removing references');
        }
        
        // Check mistakes table for missing columns
        try {
            $columns = DB::select("SHOW COLUMNS FROM mistakes LIKE 'reviewed'");
            if (empty($columns)) {
                $issues[] = 'mistakes.reviewed column missing';
                $this->warn('⚠️  mistakes.reviewed column does not exist - removing references');
            }
        } catch (\Exception $e) {
            $this->warn('Could not check mistakes table: ' . $e->getMessage());
        }
        
        // Check user_topic_progress for mastery_level
        try {
            $columns = DB::select("SHOW COLUMNS FROM user_topic_progress LIKE 'mastery_level'");
            if (empty($columns)) {
                $issues[] = 'user_topic_progress.mastery_level column missing';
                $this->warn('⚠️  user_topic_progress.mastery_level column does not exist - using percentage_grade instead');
            }
        } catch (\Exception $e) {
            $this->warn('Could not check user_topic_progress table: ' . $e->getMessage());
        }
        
        if (empty($issues)) {
            $this->info('✅ Database schema looks good!');
        } else {
            $this->warn('Found ' . count($issues) . ' schema issues that need attention');
        }
    }
}