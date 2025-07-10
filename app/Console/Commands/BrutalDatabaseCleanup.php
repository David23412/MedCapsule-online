<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BrutalDatabaseCleanup extends Command
{
    protected $signature = 'db:brutal-cleanup 
                          {--force : Execute cleanup without confirmation}
                          {--analyze-only : Only analyze, don\'t execute}
                          {--remove-dead-columns : Remove identified dead columns}
                          {--extreme-mode : Enable most aggressive optimizations}';

    protected $description = 'BRUTAL database cleanup - removes dead columns, optimizes schema with ZERO MERCY';

    /**
     * Potentially dead columns identified through code analysis
     */
    protected $deadColumnCandidates = [
        'users' => [
            'last_study_date' => 'Potentially redundant with quiz_attempts.created_at',
        ],
        'questions' => [
            'last_attempted_at' => 'May be redundant with quiz_attempts.created_at',
        ],
        'topics' => [
            'last_attempt_date' => 'Potentially redundant with user_topic_progress.last_attempt_date',
        ],
    ];

    /**
     * Tables to analyze for unused data
     */
    protected $tableAnalysis = [
        'notifications' => [
            'check' => 'read_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)',
            'description' => 'Old unread notifications (90+ days)',
        ],
        'quiz_attempts' => [
            'check' => 'created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)',
            'description' => 'Quiz attempts older than 1 year',
        ],
        'mistakes' => [
            'check' => 'mastered = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)',
            'description' => 'Mastered mistakes older than 6 months',
        ],
    ];

    public function handle()
    {
        $this->info('🔥 BRUTAL DATABASE CLEANUP - NO MERCY MODE');
        $this->info('=========================================');
        
        if (!$this->option('force') && !$this->option('analyze-only')) {
            if (!$this->confirm('⚠️  WARNING: This will BRUTALLY optimize your database. Continue?')) {
                $this->error('Cleanup cancelled.');
                return 1;
            }
        }

        $analyzeOnly = $this->option('analyze-only');
        $removeDead = $this->option('remove-dead-columns');
        $extremeMode = $this->option('extreme-mode');

        $this->newLine();
        $this->info('🔍 ANALYZING DATABASE FOR OPTIMIZATION OPPORTUNITIES...');
        $this->newLine();

        // 1. Analyze table sizes and identify candidates for cleanup
        $this->analyzeTableSizes();
        
        // 2. Analyze dead columns
        $this->analyzeDeadColumns();
        
        // 3. Analyze unused data
        $this->analyzeUnusedData();
        
        // 4. Check for missing indexes
        $this->analyzeMissingIndexes();

        if (!$analyzeOnly) {
            $this->newLine();
            $this->info('🔥 EXECUTING BRUTAL OPTIMIZATIONS...');
            
            // Execute optimizations
            if ($removeDead) {
                $this->executeDeadColumnRemoval();
            }
            
            $this->executeDataCleanup();
            $this->executeBrutalOptimizations();
            
            if ($extremeMode) {
                $this->executeExtremeOptimizations();
            }
        }

        return 0;
    }

    protected function analyzeTableSizes()
    {
        $this->info('📊 TABLE SIZE ANALYSIS:');
        
        try {
            $tables = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                    ROUND((index_length / 1024 / 1024), 2) AS index_mb,
                    ROUND((index_length / (data_length + index_length)) * 100, 2) AS index_ratio
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ");

            $this->table(
                ['Table', 'Rows', 'Total MB', 'Data MB', 'Index MB', 'Index %'],
                array_map(function($table) {
                    return [
                        $table->table_name,
                        number_format($table->table_rows),
                        $table->size_mb,
                        $table->data_mb,
                        $table->index_mb,
                        $table->index_ratio . '%'
                    ];
                }, $tables)
            );

            // Flag tables with high index ratio
            foreach ($tables as $table) {
                if ($table->index_ratio > 50) {
                    $this->warn("⚠️  {$table->table_name} has high index ratio ({$table->index_ratio}%) - may be over-indexed");
                }
            }

        } catch (\Exception $e) {
            $this->error('Failed to analyze table sizes: ' . $e->getMessage());
        }
    }

    protected function analyzeDeadColumns()
    {
        $this->info('🔍 DEAD COLUMN ANALYSIS:');
        
        foreach ($this->deadColumnCandidates as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $this->line("Table: {$table}");
            
            foreach ($columns as $column => $reason) {
                if (Schema::hasColumn($table, $column)) {
                    // Check if column has any non-null values
                    try {
                        $nonNullCount = DB::table($table)
                            ->whereNotNull($column)
                            ->count();
                        
                        $totalCount = DB::table($table)->count();
                        $usage = $totalCount > 0 ? round(($nonNullCount / $totalCount) * 100, 2) : 0;
                        
                        $status = $usage < 5 ? '🔥 DEAD' : ($usage < 20 ? '⚠️  SPARSE' : '✅ USED');
                        
                        $this->line("  {$column}: {$status} ({$usage}% populated) - {$reason}");
                        
                    } catch (\Exception $e) {
                        $this->line("  {$column}: ❌ ERROR analyzing - {$reason}");
                    }
                } else {
                    $this->line("  {$column}: ❌ MISSING");
                }
            }
        }
    }

    protected function analyzeUnusedData()
    {
        $this->info('🗑️  UNUSED DATA ANALYSIS:');
        
        foreach ($this->tableAnalysis as $table => $config) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $count = DB::table($table)
                    ->whereRaw($config['check'])
                    ->count();
                
                if ($count > 0) {
                    $this->warn("🗑️  {$table}: {$count} rows of {$config['description']}");
                } else {
                    $this->info("✅ {$table}: No unused data found");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to analyze {$table}: " . $e->getMessage());
            }
        }
    }

    protected function analyzeMissingIndexes()
    {
        $this->info('🔍 MISSING INDEX ANALYSIS:');
        
        // Check for foreign keys without indexes
        try {
            $foreignKeys = DB::select("
                SELECT 
                    TABLE_NAME,
                    COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                AND TABLE_SCHEMA = DATABASE()
            ");

            foreach ($foreignKeys as $fk) {
                $hasIndex = DB::select("
                    SHOW INDEX FROM {$fk->TABLE_NAME} 
                    WHERE Column_name = ?
                ", [$fk->COLUMN_NAME]);

                if (empty($hasIndex)) {
                    $this->warn("⚠️  Missing index on foreign key: {$fk->TABLE_NAME}.{$fk->COLUMN_NAME}");
                }
            }

        } catch (\Exception $e) {
            $this->error('Failed to analyze missing indexes: ' . $e->getMessage());
        }
    }

    protected function executeDeadColumnRemoval()
    {
        $this->info('🔥 REMOVING DEAD COLUMNS (NO MERCY)...');
        
        foreach ($this->deadColumnCandidates as $table => $columns) {
            foreach ($columns as $column => $reason) {
                if (Schema::hasColumn($table, $column)) {
                    try {
                        // Double-check usage before removal
                        $usage = DB::table($table)
                            ->whereNotNull($column)
                            ->count();
                        
                        if ($usage < 10) { // Less than 10 records use this column
                            $this->line("🗑️  Removing {$table}.{$column} ({$usage} non-null values)");
                            
                            Schema::table($table, function($table) use ($column) {
                                $table->dropColumn($column);
                            });
                            
                            $this->info("✅ Removed {$table}.{$column}");
                        } else {
                            $this->warn("⚠️  Skipping {$table}.{$column} - too much usage ({$usage} records)");
                        }
                        
                    } catch (\Exception $e) {
                        $this->error("❌ Failed to remove {$table}.{$column}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function executeDataCleanup()
    {
        $this->info('🧹 CLEANING UNUSED DATA...');
        
        foreach ($this->tableAnalysis as $table => $config) {
            try {
                $deletedCount = DB::table($table)
                    ->whereRaw($config['check'])
                    ->delete();
                
                if ($deletedCount > 0) {
                    $this->info("🗑️  Deleted {$deletedCount} rows from {$table} ({$config['description']})");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to clean {$table}: " . $e->getMessage());
            }
        }
    }

    protected function executeBrutalOptimizations()
    {
        $this->info('🔥 EXECUTING BRUTAL TABLE OPTIMIZATIONS...');
        
        $tables = ['users', 'courses', 'topics', 'questions', 'quiz_attempts', 'mistakes'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    $this->line("🔧 Optimizing {$table}...");
                    
                    // OPTIMIZE and ANALYZE for maximum performance
                    DB::statement("OPTIMIZE TABLE `{$table}`");
                    DB::statement("ANALYZE TABLE `{$table}`");
                    
                    $this->info("✅ {$table} optimized");
                    
                } catch (\Exception $e) {
                    $this->error("❌ Failed to optimize {$table}: " . $e->getMessage());
                }
            }
        }
    }

    protected function executeExtremeOptimizations()
    {
        $this->info('🔥🔥🔥 EXTREME MODE - MAXIMUM BRUTALITY 🔥🔥🔥');
        
        try {
            // Rebuild all statistics
            $this->line('📊 Rebuilding table statistics...');
            $tables = DB::select("SHOW TABLES");
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                DB::statement("ANALYZE TABLE `{$tableName}`");
            }
            
            // Defragment tables
            $this->line('🔧 Defragmenting tables...');
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                try {
                    DB::statement("ALTER TABLE `{$tableName}` ENGINE=InnoDB");
                } catch (\Exception $e) {
                    // Some tables might not support this
                }
            }
            
            $this->info('✅ EXTREME optimizations completed');
            
        } catch (\Exception $e) {
            $this->error('❌ Extreme optimizations failed: ' . $e->getMessage());
        }
    }
}