<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class QueryMonitoringService
{
    private array $queries = [];
    private array $slowQueries = [];
    private float $startTime;
    private int $queryCount = 0;
    private float $totalQueryTime = 0;
    
    // Performance thresholds
    private const SLOW_QUERY_THRESHOLD = 100; // 100ms
    private const MAX_QUERIES_THRESHOLD = 10; // 10 queries per request
    private const TOTAL_TIME_THRESHOLD = 500; // 500ms total query time
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->enableQueryLogging();
    }
    
    /**
     * Enable query logging for monitoring
     */
    private function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            $this->logQuery($query);
        });
    }
    
    /**
     * Log individual query for analysis
     */
    private function logQuery($query): void
    {
        $executionTime = $query->time;
        $sql = $query->sql;
        $bindings = $query->bindings;
        
        $this->queryCount++;
        $this->totalQueryTime += $executionTime;
        
        $queryData = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $executionTime,
            'timestamp' => now(),
            'trace' => $this->getCallerTrace()
        ];
        
        $this->queries[] = $queryData;
        
        // Track slow queries
        if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
            $this->slowQueries[] = $queryData;
            $this->alertSlowQuery($queryData);
        }
    }
    
    /**
     * Get caller trace for query analysis
     */
    private function getCallerTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $relevantTrace = [];
        
        foreach ($trace as $frame) {
            if (isset($frame['file']) && 
                strpos($frame['file'], 'app/') !== false && 
                strpos($frame['file'], 'vendor/') === false) {
                $relevantTrace[] = [
                    'file' => $frame['file'],
                    'line' => $frame['line'],
                    'function' => $frame['function'] ?? '',
                    'class' => $frame['class'] ?? ''
                ];
            }
        }
        
        return array_slice($relevantTrace, 0, 5);
    }
    
    /**
     * Alert on slow queries
     */
    private function alertSlowQuery(array $queryData): void
    {
        Log::warning('Slow query detected', [
            'execution_time' => $queryData['time'],
            'sql' => $queryData['sql'],
            'bindings' => $queryData['bindings'],
            'trace' => $queryData['trace'],
            'threshold' => self::SLOW_QUERY_THRESHOLD
        ]);
        
        // Cache slow query for analysis
        $this->cacheSlowQuery($queryData);
    }
    
    /**
     * Cache slow query for later analysis
     */
    private function cacheSlowQuery(array $queryData): void
    {
        $key = 'slow_queries_' . date('Y-m-d');
        $slowQueries = Cache::get($key, []);
        $slowQueries[] = $queryData;
        
        // Keep only last 100 slow queries
        if (count($slowQueries) > 100) {
            $slowQueries = array_slice($slowQueries, -100);
        }
        
        Cache::put($key, $slowQueries, 86400); // 24 hours
    }
    
    /**
     * Get query performance summary
     */
    public function getPerformanceSummary(): array
    {
        $endTime = microtime(true);
        $totalRequestTime = ($endTime - $this->startTime) * 1000;
        
        return [
            'query_count' => $this->queryCount,
            'total_query_time' => $this->totalQueryTime,
            'total_request_time' => $totalRequestTime,
            'slow_query_count' => count($this->slowQueries),
            'average_query_time' => $this->queryCount > 0 ? $this->totalQueryTime / $this->queryCount : 0,
            'query_percentage' => $this->queryCount > 0 ? ($this->totalQueryTime / $totalRequestTime) * 100 : 0,
            'thresholds_exceeded' => $this->getThresholdViolations()
        ];
    }
    
    /**
     * Check for threshold violations
     */
    private function getThresholdViolations(): array
    {
        $violations = [];
        
        if ($this->queryCount > self::MAX_QUERIES_THRESHOLD) {
            $violations[] = "Query count ({$this->queryCount}) exceeds threshold (" . self::MAX_QUERIES_THRESHOLD . ")";
        }
        
        if ($this->totalQueryTime > self::TOTAL_TIME_THRESHOLD) {
            $violations[] = "Total query time ({$this->totalQueryTime}ms) exceeds threshold (" . self::TOTAL_TIME_THRESHOLD . "ms)";
        }
        
        if (count($this->slowQueries) > 0) {
            $violations[] = "Slow queries detected: " . count($this->slowQueries);
        }
        
        return $violations;
    }
    
    /**
     * Get all queries for analysis
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
    
    /**
     * Get slow queries for analysis
     */
    public function getSlowQueries(): array
    {
        return $this->slowQueries;
    }
    
    /**
     * Analyze query patterns for optimization opportunities
     */
    public function analyzeQueryPatterns(): array
    {
        $patterns = [];
        
        foreach ($this->queries as $query) {
            $normalizedSql = $this->normalizeSql($query['sql']);
            
            if (!isset($patterns[$normalizedSql])) {
                $patterns[$normalizedSql] = [
                    'count' => 0,
                    'total_time' => 0,
                    'avg_time' => 0,
                    'max_time' => 0,
                    'min_time' => PHP_FLOAT_MAX,
                    'examples' => []
                ];
            }
            
            $patterns[$normalizedSql]['count']++;
            $patterns[$normalizedSql]['total_time'] += $query['time'];
            $patterns[$normalizedSql]['max_time'] = max($patterns[$normalizedSql]['max_time'], $query['time']);
            $patterns[$normalizedSql]['min_time'] = min($patterns[$normalizedSql]['min_time'], $query['time']);
            
            if (count($patterns[$normalizedSql]['examples']) < 3) {
                $patterns[$normalizedSql]['examples'][] = $query;
            }
        }
        
        // Calculate averages
        foreach ($patterns as &$pattern) {
            $pattern['avg_time'] = $pattern['total_time'] / $pattern['count'];
        }
        
        return $patterns;
    }
    
    /**
     * Normalize SQL for pattern matching
     */
    private function normalizeSql(string $sql): string
    {
        // Remove specific values but keep structure
        $normalized = preg_replace('/\d+/', '?', $sql);
        $normalized = preg_replace('/\'[^\']*\'/', '?', $normalized);
        $normalized = preg_replace('/"[^"]*"/', '?', $normalized);
        
        return trim($normalized);
    }
    
    /**
     * Get optimization recommendations
     */
    public function getOptimizationRecommendations(): array
    {
        $recommendations = [];
        $patterns = $this->analyzeQueryPatterns();
        
        foreach ($patterns as $sql => $pattern) {
            if ($pattern['count'] > 3 && $pattern['avg_time'] > 50) {
                $recommendations[] = [
                    'type' => 'frequent_slow_query',
                    'sql' => $sql,
                    'count' => $pattern['count'],
                    'avg_time' => $pattern['avg_time'],
                    'suggestion' => 'Consider adding index or caching for this query pattern'
                ];
            }
            
            if ($pattern['count'] > 10) {
                $recommendations[] = [
                    'type' => 'frequent_query',
                    'sql' => $sql,
                    'count' => $pattern['count'],
                    'suggestion' => 'Consider caching this frequently executed query'
                ];
            }
        }
        
        if ($this->queryCount > self::MAX_QUERIES_THRESHOLD) {
            $recommendations[] = [
                'type' => 'n_plus_one',
                'query_count' => $this->queryCount,
                'suggestion' => 'Potential N+1 query problem detected. Consider eager loading.'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Log performance summary
     */
    public function logPerformanceSummary(): void
    {
        $summary = $this->getPerformanceSummary();
        $recommendations = $this->getOptimizationRecommendations();
        
        Log::info('Query performance summary', [
            'summary' => $summary,
            'recommendations' => $recommendations,
            'url' => request()->url(),
            'method' => request()->method()
        ]);
        
        // Alert if thresholds exceeded
        if (!empty($summary['thresholds_exceeded'])) {
            Log::warning('Query performance thresholds exceeded', [
                'violations' => $summary['thresholds_exceeded'],
                'summary' => $summary
            ]);
        }
    }
} 