<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizeDatabaseTables extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:optimize-tables 
                          {--tables= : Comma-separated list of specific tables to optimize}
                          {--analyze-only : Only run ANALYZE TABLE without OPTIMIZE}
                          {--force : Force optimization even in production}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize database tables for better performance and analyze table statistics';

    /**
     * Default tables to optimize (high-traffic tables)
     */
    protected $defaultTables = [
        'users',
        'courses', 
        'topics',
        'questions',
        'quiz_attempts',
        'mistakes',
        'notifications',
        'payments',
        'course_user',
        'user_topic_progress'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('You are running this in PRODUCTION. Continue?')) {
                $this->error('Optimization cancelled.');
                return 1;
            }
        }

        $tables = $this->getTablesToOptimize();
        $analyzeOnly = $this->option('analyze-only');
        
        $this->info('🚀 Starting database optimization...');
        $this->info('Mode: ' . ($analyzeOnly ? 'ANALYZE ONLY' : 'FULL OPTIMIZATION'));
        $this->info('Tables to process: ' . count($tables));
        
        $startTime = microtime(true);
        $successCount = 0;
        $errorCount = 0;

        foreach ($tables as $table) {
            $this->optimizeTable($table, $analyzeOnly, $successCount, $errorCount);
        }

        $totalTime = round((microtime(true) - $startTime), 2);
        
        $this->newLine();
        $this->info("✅ Database optimization completed in {$totalTime}s");
        $this->info("📊 Results: {$successCount} successful, {$errorCount} errors");
        
        // Clear relevant caches
        $this->clearOptimizationCaches();
        
        return 0;
    }

    /**
     * Get the list of tables to optimize
     */
    protected function getTablesToOptimize(): array
    {
        if ($customTables = $this->option('tables')) {
            return array_map('trim', explode(',', $customTables));
        }

        // Verify that tables exist
        $existingTables = [];
        foreach ($this->defaultTables as $table) {
            if ($this->tableExists($table)) {
                $existingTables[] = $table;
            } else {
                $this->warn("⚠️  Table '{$table}' does not exist, skipping.");
            }
        }

        return $existingTables;
    }

    /**
     * Check if a table exists
     */
    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize a single table
     */
    protected function optimizeTable(string $table, bool $analyzeOnly, int &$successCount, int &$errorCount): void
    {
        $this->line("Processing table: {$table}");
        
        try {
            // Get table info before optimization
            $sizeBefore = $this->getTableSize($table);
            
            if (!$analyzeOnly) {
                // Run OPTIMIZE TABLE
                $this->info("  🔧 Optimizing table...");
                $optimizeStart = microtime(true);
                
                DB::statement("OPTIMIZE TABLE `{$table}`");
                
                $optimizeTime = round((microtime(true) - $optimizeStart) * 1000, 2);
                $this->info("  ✅ OPTIMIZE completed in {$optimizeTime}ms");
            }
            
            // Run ANALYZE TABLE
            $this->info("  📊 Analyzing table statistics...");
            $analyzeStart = microtime(true);
            
            DB::statement("ANALYZE TABLE `{$table}`");
            
            $analyzeTime = round((microtime(true) - $analyzeStart) * 1000, 2);
            $this->info("  ✅ ANALYZE completed in {$analyzeTime}ms");
            
            // Get table info after optimization
            $sizeAfter = $this->getTableSize($table);
            
            if ($sizeBefore && $sizeAfter) {
                $sizeDiff = $sizeBefore - $sizeAfter;
                $percentSaved = $sizeBefore > 0 ? round(($sizeDiff / $sizeBefore) * 100, 2) : 0;
                
                if ($sizeDiff > 0) {
                    $this->info("  💾 Space saved: " . $this->formatBytes($sizeDiff) . " ({$percentSaved}%)");
                } else {
                    $this->info("  💾 Table size: " . $this->formatBytes($sizeAfter));
                }
            }
            
            $successCount++;
            
        } catch (\Exception $e) {
            $this->error("  ❌ Error optimizing table '{$table}': " . $e->getMessage());
            $errorCount++;
        }
        
        $this->newLine();
    }

    /**
     * Get table size in bytes
     */
    protected function getTableSize(string $table): ?int
    {
        try {
            $result = DB::select("
                SELECT 
                    data_length + index_length as size_bytes
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table]);
            
            return $result[0]->size_bytes ?? null;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Clear optimization-related caches
     */
    protected function clearOptimizationCaches(): void
    {
        $this->info('🧹 Clearing optimization caches...');
        
        try {
            // Clear specific cache tags
            Cache::tags(['courses', 'users', 'questions'])->flush();
            
            // Clear Laravel framework caches
            \Artisan::call('config:cache');
            \Artisan::call('route:cache');
            
            $this->info('✅ Caches cleared successfully');
            
        } catch (\Exception $e) {
            $this->warn('⚠️  Cache clearing failed: ' . $e->getMessage());
        }
    }

    /**
     * Display database statistics
     */
    protected function showDatabaseStats(): void
    {
        try {
            $this->info('📊 Database Statistics:');
            
            $stats = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            
            $this->table(
                ['Table', 'Rows', 'Size (MB)'],
                array_map(function($stat) {
                    return [$stat->table_name, number_format($stat->table_rows), $stat->size_mb];
                }, $stats)
            );
            
        } catch (\Exception $e) {
            $this->warn('Could not retrieve database statistics: ' . $e->getMessage());
        }
    }
}