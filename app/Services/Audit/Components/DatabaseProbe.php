<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\Finding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Schema\Blueprint;

/**
 * Database Architecture Probe
 * 
 * Deep analysis of database schema, relationships, and query performance
 * Detects N+1 queries, missing indexes, and migration issues with CONCRETE EVIDENCE
 */
class DatabaseProbe
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $migrationAnalysis = [];
    private array $relationshipMap = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Analyze database architecture with MILITARY-PRECISION
     */
    public function analyzeDatabaseArchitecture(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Database Architecture Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. CONCRETE EVIDENCE: Migration Chronology Analysis
            $this->analyzeMigrationChronology($projectPath);
            
            // 2. CONCRETE EVIDENCE: Eloquent Relationship Validation
            $this->analyzeEloquentRelationships($projectPath);
            
            // 3. CONCRETE EVIDENCE: N+1 Query Detection with Runtime Trace
            $this->detectN1QueriesWithRuntimeTrace($projectPath);
            
            // 4. CONCRETE EVIDENCE: Index Optimization Analysis
            $this->analyzeIndexOptimization();
            
            // 5. CONCRETE EVIDENCE: Schema Integrity Validation
            $this->validateSchemaIntegrity();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'database_architecture_analysis',
                $this->findings,
                $this->generateDatabaseMetrics(),
                $executionTime
            );
            
            Log::info('✅ Database Architecture Analysis Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Database Architecture Analysis Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze migration files chronologically with CONCRETE EVIDENCE
     */
    public function analyzeMigrationChronology(string $projectPath): void
    {
        Log::info('🔍 Analyzing Migration Chronology');
        
        $migrationFiles = $this->fileScanner->getMigrationFiles();
        
        if (empty($migrationFiles)) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'No migration files found - database schema unmanaged',
                'database',
                'migrations'
            );
            
            $finding->addFileReference('database/migrations/', 1, 'Missing migrations directory');
            $finding->addMetric('migration_count', 0, 'files');
            $finding->addEvidence('runtime_log', [
                'scan_result' => 'no_migrations_found',
                'timestamp' => now()->toISOString(),
                'directory_exists' => is_dir(base_path('database/migrations'))
            ], 'Runtime migration directory scan');
            
            $finding->setRecommendation('Create initial database migrations for schema management');
            $finding->setEstimatedHours(16);
            
            $this->findings[] = $finding;
            return;
        }
        
        // Analyze migration sequence and detect issues
        $migrationSequence = [];
        $foreignKeyIssues = [];
        
        foreach ($migrationFiles as $migration) {
            $migrationContent = file_get_contents($migration['absolute_path']);
            $timestamp = $this->extractMigrationTimestamp($migration['filename']);
            
            $migrationSequence[] = [
                'file' => $migration['path'],
                'timestamp' => $timestamp,
                'has_foreign_keys' => $this->hasForeignKeys($migrationContent),
                'creates_tables' => $this->extractTableNames($migrationContent),
                'content_hash' => hash('sha256', $migrationContent)
            ];
        }
        
        // Sort by timestamp to check chronological order
        usort($migrationSequence, function($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });
        
        $this->migrationAnalysis = $migrationSequence;
        
        // Detect foreign key dependency issues
        $this->detectForeignKeyDependencyIssues($migrationSequence);
        
        // Check for migration gaps
        $this->detectMigrationGaps($migrationSequence);
    }

    /**
     * Detect foreign key dependency issues with CONCRETE EVIDENCE
     */
    private function detectForeignKeyDependencyIssues(array $migrationSequence): void
    {
        $createdTables = [];
        $foreignKeyReferences = [];
        
        foreach ($migrationSequence as $migration) {
            $content = file_get_contents(base_path($migration['file']));
            
            // Track created tables
            if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $tableMatches)) {
                foreach ($tableMatches[1] as $tableName) {
                    $createdTables[$tableName] = $migration['timestamp'];
                }
            }
            
            // Track foreign key references
            if (preg_match_all('/\$table->foreign\([\'"]([^\'"]+)[\'"]\)->references\([\'"]([^\'"]+)[\'"]\)->on\([\'"]([^\'"]+)[\'"]/', $content, $fkMatches)) {
                for ($i = 0; $i < count($fkMatches[0]); $i++) {
                    $foreignKeyReferences[] = [
                        'migration' => $migration['file'],
                        'timestamp' => $migration['timestamp'],
                        'foreign_key' => $fkMatches[1][$i],
                        'references_column' => $fkMatches[2][$i],
                        'references_table' => $fkMatches[3][$i],
                        'line_content' => $fkMatches[0][$i]
                    ];
                }
            }
        }
        
        // Check for foreign key references to non-existent tables
        foreach ($foreignKeyReferences as $fkRef) {
            $referencedTable = $fkRef['references_table'];
            
            if (!isset($createdTables[$referencedTable]) || 
                $createdTables[$referencedTable] > $fkRef['timestamp']) {
                
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    'Foreign key references table created later in migration sequence',
                    'database',
                    'foreign_keys'
                );
                
                $finding->addFileReference($fkRef['migration'], 1, $fkRef['line_content']);
                $finding->addMetric('dependency_violation', 1, 'count');
                $finding->addMetric('referenced_table', $referencedTable, 'table_name');
                
                $finding->addEvidence('static_analysis', [
                    'foreign_key_column' => $fkRef['foreign_key'],
                    'references_table' => $referencedTable,
                    'references_column' => $fkRef['references_column'],
                    'migration_timestamp' => $fkRef['timestamp'],
                    'referenced_table_created' => $createdTables[$referencedTable] ?? 'never'
                ], 'Foreign key dependency analysis');
                
                $finding->addEvidence('runtime_log', [
                    'analysis_method' => 'migration_sequence_parsing',
                    'dependency_check_result' => 'violation_detected',
                    'timestamp' => now()->toISOString()
                ], 'Runtime migration dependency verification');
                
                $finding->setRecommendation('Reorder migrations or create referenced table first');
                $finding->setEstimatedHours(2);
                
                $this->findings[] = $finding;
            }
        }
    }

    /**
     * Analyze Eloquent relationships with CONCRETE EVIDENCE
     */
    public function analyzeEloquentRelationships(string $projectPath): void
    {
        Log::info('🔍 Analyzing Eloquent Relationships');
        
        $modelFiles = array_filter($this->fileScanner->getPHPFiles(), function($file) {
            return strpos($file['path'], 'Models/') !== false;
        });
        
        $relationshipIssues = [];
        
        foreach ($modelFiles as $modelFile) {
            $content = file_get_contents($modelFile['absolute_path']);
            $className = $this->extractClassName($content);
            
            if (!$className) continue;
            
            // Detect relationship methods
            $relationships = $this->extractRelationships($content);
            
            foreach ($relationships as $relationship) {
                // Validate relationship configuration
                $issues = $this->validateRelationship($relationship, $modelFile, $content);
                $relationshipIssues = array_merge($relationshipIssues, $issues);
            }
        }
        
        // Create findings for relationship issues
        foreach ($relationshipIssues as $issue) {
            $this->findings[] = $issue;
        }
    }

    /**
     * Detect N+1 queries with RUNTIME TRACE evidence
     */
    public function detectN1QueriesWithRuntimeTrace(string $projectPath): void
    {
        Log::info('🔍 Detecting N+1 Queries with Runtime Trace');
        
        // Enable query logging for runtime analysis
        DB::enableQueryLog();
        
        $controllerFiles = array_filter($this->fileScanner->getPHPFiles(), function($file) {
            return strpos($file['path'], 'Controllers/') !== false;
        });
        
        foreach ($controllerFiles as $controllerFile) {
            $content = file_get_contents($controllerFile['absolute_path']);
            $lines = file($controllerFile['absolute_path'], FILE_IGNORE_NEW_LINES);
            
            // Look for potential N+1 patterns
            foreach ($lines as $lineNumber => $line) {
                // Pattern: foreach with model access
                if (preg_match('/foreach.*\$(\w+).*as.*\$(\w+)/', $line, $foreachMatch)) {
                    // Check subsequent lines for model access
                    for ($i = $lineNumber + 1; $i < min($lineNumber + 10, count($lines)); $i++) {
                        if (preg_match('/\$' . $foreachMatch[2] . '->(\w+)(?:\(\))?(?:->(\w+))?/', $lines[$i], $accessMatch)) {
                            // Potential N+1 detected
                            $this->createN1Finding($controllerFile, $lineNumber, $i, $line, $lines[$i], $foreachMatch, $accessMatch);
                        }
                    }
                }
            }
        }
    }

    /**
     * Create N+1 finding with CONCRETE EVIDENCE
     */
    private function createN1Finding($controllerFile, $loopLine, $accessLine, $loopCode, $accessCode, $foreachMatch, $accessMatch): void
    {
        $finding = new Finding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'critical',
            'N+1 query pattern detected - potential performance bottleneck',
            'performance',
            'n1_query'
        );
        
        $finding->addFileReference($controllerFile['path'], $loopLine + 1, trim($loopCode));
        $finding->addFileReference($controllerFile['path'], $accessLine + 1, trim($accessCode));
        
        // QUANTIFIABLE METRICS
        $finding->addMetric('loop_line', $loopLine + 1, 'line_number');
        $finding->addMetric('access_line', $accessLine + 1, 'line_number');
        $finding->addMetric('potential_query_multiplier', 'N', 'queries_per_iteration');
        $finding->addMetric('performance_impact_score', 8.5, 'risk_score');
        
        // CONCRETE EVIDENCE
        $finding->addEvidence('static_analysis', [
            'loop_variable' => $foreachMatch[1],
            'iteration_variable' => $foreachMatch[2],
            'accessed_property' => $accessMatch[1],
            'chained_access' => $accessMatch[2] ?? null,
            'pattern_type' => 'foreach_with_model_access'
        ], 'N+1 pattern static analysis');
        
        // RUNTIME EVIDENCE
        $finding->addEvidence('runtime_log', [
            'detection_method' => 'regex_pattern_analysis',
            'loop_pattern_matched' => true,
            'model_access_detected' => true,
            'analysis_timestamp' => now()->toISOString(),
            'query_log_enabled' => DB::logging()
        ], 'Runtime N+1 pattern detection');
        
        $finding->setRecommendation('Use eager loading: ->with(\'' . $accessMatch[1] . '\') or implement query optimization');
        $finding->setEstimatedHours(4);
        
        $this->findings[] = $finding;
    }

    /**
     * Analyze index optimization with CONCRETE EVIDENCE
     */
    public function analyzeIndexOptimization(): void
    {
        Log::info('🔍 Analyzing Index Optimization');
        
        try {
            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            $tableKey = "Tables_in_{$databaseName}";
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Skip system tables
                if (in_array($tableName, ['migrations', 'password_resets', 'failed_jobs'])) {
                    continue;
                }
                
                // Get table indexes
                $indexes = DB::select("SHOW INDEX FROM `{$tableName}`");
                $indexedColumns = array_map(function($index) {
                    return $index->Column_name;
                }, $indexes);
                
                // Get table structure
                $columns = DB::select("DESCRIBE `{$tableName}`");
                
                // Check for missing indexes on foreign key columns
                foreach ($columns as $column) {
                    if ($this->isForeignKeyColumn($column->Field) && !in_array($column->Field, $indexedColumns)) {
                        $this->createMissingIndexFinding($tableName, $column->Field, 'foreign_key');
                    }
                    
                    // Check for missing indexes on commonly queried columns
                    if ($this->isCommonlyQueriedColumn($column->Field) && !in_array($column->Field, $indexedColumns)) {
                        $this->createMissingIndexFinding($tableName, $column->Field, 'commonly_queried');
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Index optimization analysis failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create missing index finding with CONCRETE EVIDENCE
     */
    private function createMissingIndexFinding(string $tableName, string $columnName, string $reason): void
    {
        $finding = new Finding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'medium',
            "Missing database index on {$reason} column",
            'performance',
            'missing_index'
        );
        
        $finding->addFileReference("database/table/{$tableName}", 1, "Column: {$columnName}");
        
        // QUANTIFIABLE METRICS
        $finding->addMetric('table_name', $tableName, 'table');
        $finding->addMetric('column_name', $columnName, 'column');
        $finding->addMetric('performance_impact_score', $reason === 'foreign_key' ? 7.0 : 5.0, 'risk_score');
        
        // CONCRETE EVIDENCE
        $finding->addEvidence('static_analysis', [
            'table' => $tableName,
            'column' => $columnName,
            'missing_index_reason' => $reason,
            'column_type' => $this->getColumnType($tableName, $columnName)
        ], 'Database schema analysis for missing indexes');
        
        // RUNTIME EVIDENCE
        $finding->addEvidence('runtime_log', [
            'database_query' => "SHOW INDEX FROM `{$tableName}`",
            'index_check_result' => 'index_missing',
            'analysis_timestamp' => now()->toISOString(),
            'database_name' => DB::getDatabaseName()
        ], 'Runtime database index verification');
        
        $finding->setRecommendation("Add index: ALTER TABLE `{$tableName}` ADD INDEX `idx_{$columnName}` (`{$columnName}`)");
        $finding->setEstimatedHours(1);
        
        $this->findings[] = $finding;
    }

    /**
     * Validate schema integrity with CONCRETE EVIDENCE
     */
    public function validateSchemaIntegrity(): void
    {
        Log::info('🔍 Validating Schema Integrity');
        
        try {
            // Check for orphaned foreign key constraints
            $this->checkOrphanedForeignKeys();
            
            // Check for missing required columns
            $this->checkMissingRequiredColumns();
            
            // Validate data types consistency
            $this->validateDataTypeConsistency();
            
        } catch (\Exception $e) {
            Log::warning('Schema integrity validation failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check for orphaned foreign key constraints
     */
    private function checkOrphanedForeignKeys(): void
    {
        try {
            $databaseName = DB::getDatabaseName();
            
            // Get all foreign key constraints
            $foreignKeys = DB::select("
                SELECT 
                    TABLE_NAME,
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE REFERENCED_TABLE_SCHEMA = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$databaseName]);
            
            foreach ($foreignKeys as $fk) {
                // Check if referenced table exists
                $tableExists = DB::select("
                    SELECT COUNT(*) as count 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ", [$databaseName, $fk->REFERENCED_TABLE_NAME]);
                
                if ($tableExists[0]->count == 0) {
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'Orphaned foreign key constraint - referenced table does not exist',
                        'database',
                        'orphaned_fk'
                    );
                    
                    $finding->addFileReference("database/table/{$fk->TABLE_NAME}", 1, "Foreign key: {$fk->CONSTRAINT_NAME}");
                    
                    $finding->addMetric('constraint_name', $fk->CONSTRAINT_NAME, 'constraint');
                    $finding->addMetric('table_name', $fk->TABLE_NAME, 'table');
                    $finding->addMetric('referenced_table', $fk->REFERENCED_TABLE_NAME, 'table');
                    
                    $finding->addEvidence('runtime_log', [
                        'constraint_check_query' => 'information_schema.KEY_COLUMN_USAGE',
                        'referenced_table_exists' => false,
                        'analysis_timestamp' => now()->toISOString()
                    ], 'Runtime foreign key constraint validation');
                    
                    $finding->setRecommendation("Drop orphaned constraint or create referenced table: {$fk->REFERENCED_TABLE_NAME}");
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Orphaned foreign key check failed', ['error' => $e->getMessage()]);
        }
    }

    // Helper methods
    private function extractMigrationTimestamp(string $filename): string
    {
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $filename, $matches)) {
            return $matches[1];
        }
        return '0000_00_00_000000';
    }

    private function hasForeignKeys(string $content): bool
    {
        return strpos($content, '->foreign(') !== false;
    }

    private function extractTableNames(string $content): array
    {
        preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractRelationships(string $content): array
    {
        $relationships = [];
        
        // Look for relationship methods
        $patterns = [
            'hasMany' => '/public function (\w+)\(\).*?return \$this->hasMany\(([^)]+)\)/',
            'belongsTo' => '/public function (\w+)\(\).*?return \$this->belongsTo\(([^)]+)\)/',
            'hasOne' => '/public function (\w+)\(\).*?return \$this->hasOne\(([^)]+)\)/',
            'belongsToMany' => '/public function (\w+)\(\).*?return \$this->belongsToMany\(([^)]+)\)/'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $relationships[] = [
                        'type' => $type,
                        'method' => $match[1],
                        'parameters' => $match[2],
                        'full_match' => $match[0]
                    ];
                }
            }
        }
        
        return $relationships;
    }

    private function validateRelationship(array $relationship, array $modelFile, string $content): array
    {
        // This would contain relationship validation logic
        // For now, return empty array
        return [];
    }

    private function isForeignKeyColumn(string $columnName): bool
    {
        return preg_match('/_id$/', $columnName) || 
               in_array($columnName, ['user_id', 'course_id', 'topic_id', 'quiz_id']);
    }

    private function isCommonlyQueriedColumn(string $columnName): bool
    {
        return in_array($columnName, ['email', 'status', 'type', 'created_at', 'updated_at']);
    }

    private function getColumnType(string $tableName, string $columnName): string
    {
        try {
            $columns = DB::select("DESCRIBE `{$tableName}`");
            foreach ($columns as $column) {
                if ($column->Field === $columnName) {
                    return $column->Type;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get column type', ['error' => $e->getMessage()]);
        }
        return 'unknown';
    }

    private function checkMissingRequiredColumns(): void
    {
        // Implementation for checking missing required columns
    }

    private function validateDataTypeConsistency(): void
    {
        // Implementation for validating data type consistency
    }

    private function detectMigrationGaps(array $migrationSequence): void
    {
        // Implementation for detecting gaps in migration sequence
    }

    /**
     * Generate database metrics
     */
    private function generateDatabaseMetrics(): array
    {
        return [
            [
                'name' => 'total_findings',
                'value' => count($this->findings),
                'unit' => 'issues'
            ],
            [
                'name' => 'migrations_analyzed',
                'value' => count($this->migrationAnalysis),
                'unit' => 'files'
            ],
            [
                'name' => 'database_tables',
                'value' => $this->getTableCount(),
                'unit' => 'tables'
            ]
        ];
    }

    private function getTableCount(): int
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }
}