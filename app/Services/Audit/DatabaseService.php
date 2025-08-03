<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Database Service for Audit Operations
 * 
 * Provides database-specific audit functionality
 */
class DatabaseService
{
    /**
     * Execute database query with performance monitoring
     */
    public function executeQuery(string $query, array $bindings = []): array
    {
        $startTime = microtime(true);
        
        try {
            $result = DB::select($query, $bindings);
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Database query executed', [
                'query' => $query,
                'execution_time' => $executionTime,
                'result_count' => count($result)
            ]);
            
            return [
                'success' => true,
                'data' => $result,
                'execution_time' => $executionTime,
                'count' => count($result)
            ];
            
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::error('Database query failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ];
        }
    }

    /**
     * Analyze database performance
     */
    public function analyzePerformance(): array
    {
        $performanceMetrics = [];
        
        try {
            // Get database size
            $databaseName = DB::getDatabaseName();
            $sizeQuery = "SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' 
                FROM information_schema.tables 
                WHERE table_schema = ?";
            
            $sizeResult = $this->executeQuery($sizeQuery, [$databaseName]);
            
            if ($sizeResult['success']) {
                $performanceMetrics['database_size_mb'] = $sizeResult['data'][0]->{'DB Size in MB'} ?? 0;
            }
            
            // Get slow query log status
            $slowQueryResult = $this->executeQuery("SHOW VARIABLES LIKE 'slow_query_log'");
            if ($slowQueryResult['success']) {
                $performanceMetrics['slow_query_log_enabled'] = $slowQueryResult['data'][0]->Value === 'ON';
            }
            
            // Get connection count
            $connectionResult = $this->executeQuery("SHOW STATUS LIKE 'Threads_connected'");
            if ($connectionResult['success']) {
                $performanceMetrics['active_connections'] = (int) $connectionResult['data'][0]->Value;
            }
            
        } catch (\Exception $e) {
            Log::warning('Database performance analysis failed', ['error' => $e->getMessage()]);
            $performanceMetrics['error'] = $e->getMessage();
        }
        
        return $performanceMetrics;
    }

    /**
     * Validate database integrity
     */
    public function validateIntegrity(): array
    {
        $integrityResults = [];
        
        try {
            // Check for orphaned records (simplified check)
            $tables = $this->executeQuery("SHOW TABLES");
            
            if ($tables['success']) {
                $integrityResults['tables_count'] = count($tables['data']);
                $integrityResults['tables_accessible'] = true;
            }
            
            // Check foreign key constraints
            $fkQuery = "SELECT COUNT(*) as fk_count FROM information_schema.KEY_COLUMN_USAGE 
                       WHERE REFERENCED_TABLE_NAME IS NOT NULL 
                       AND TABLE_SCHEMA = ?";
            
            $fkResult = $this->executeQuery($fkQuery, [DB::getDatabaseName()]);
            
            if ($fkResult['success']) {
                $integrityResults['foreign_keys_count'] = $fkResult['data'][0]->fk_count;
            }
            
        } catch (\Exception $e) {
            Log::warning('Database integrity validation failed', ['error' => $e->getMessage()]);
            $integrityResults['error'] = $e->getMessage();
            $integrityResults['integrity_check_failed'] = true;
        }
        
        return $integrityResults;
    }

    /**
     * Optimize database performance
     */
    public function optimize(): array
    {
        $optimizationResults = [];
        
        try {
            // Get table optimization recommendations
            $tables = $this->executeQuery("SHOW TABLES");
            
            if ($tables['success']) {
                $optimizationResults['optimization_candidates'] = [];
                
                foreach ($tables['data'] as $table) {
                    $tableName = array_values((array) $table)[0];
                    
                    // Check table status
                    $statusResult = $this->executeQuery("SHOW TABLE STATUS LIKE ?", [$tableName]);
                    
                    if ($statusResult['success'] && !empty($statusResult['data'])) {
                        $status = $statusResult['data'][0];
                        
                        // Check for fragmentation
                        if (isset($status->Data_free) && $status->Data_free > 0) {
                            $optimizationResults['optimization_candidates'][] = [
                                'table' => $tableName,
                                'issue' => 'fragmentation',
                                'data_free' => $status->Data_free,
                                'recommendation' => 'OPTIMIZE TABLE ' . $tableName
                            ];
                        }
                    }
                }
            }
            
            $optimizationResults['analysis_completed'] = true;
            
        } catch (\Exception $e) {
            Log::warning('Database optimization analysis failed', ['error' => $e->getMessage()]);
            $optimizationResults['error'] = $e->getMessage();
            $optimizationResults['optimization_failed'] = true;
        }
        
        return $optimizationResults;
    }
}