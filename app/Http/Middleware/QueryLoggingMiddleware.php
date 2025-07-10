<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryLoggingMiddleware
{
    /**
     * Handle an incoming request.
     * OPTIMIZATION: Monitor query performance and detect N+1 queries
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Get query information
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = collect($queries)->sum('time');
        
        // Performance thresholds
        $highQueryCount = 10;
        $slowQueryThreshold = 100; // 100ms
        $highTotalTime = 500; // 500ms
        
        // Log performance metrics
        $performanceData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'query_count' => $queryCount,
            'total_query_time' => round($totalQueryTime, 2),
            'total_execution_time' => round($executionTime, 2),
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
        ];
        
        // Detect potential N+1 queries
        $n1Queries = $this->detectN1Queries($queries);
        if (!empty($n1Queries)) {
            $performanceData['n1_queries'] = $n1Queries;
            Log::warning('🚨 Potential N+1 queries detected', $performanceData);
        }
        
        // Log slow requests
        if ($queryCount > $highQueryCount || $totalQueryTime > $highTotalTime || $executionTime > $slowQueryThreshold) {
            Log::warning('⚠️ High query count or slow request detected', $performanceData);
        }
        
        // Log all requests in debug mode
        if (config('app.debug')) {
            Log::info('📊 Request performance metrics', $performanceData);
        }
        
        // Disable query logging to free memory
        DB::disableQueryLog();
        
        return $response;
    }
    
    /**
     * Detect potential N+1 queries by analyzing query patterns
     */
    private function detectN1Queries(array $queries): array
    {
        $n1Queries = [];
        $queryPatterns = [];
        
        foreach ($queries as $query) {
            $sql = $query['sql'];
            $table = $this->extractTableFromSql($sql);
            
            if ($table) {
                if (!isset($queryPatterns[$table])) {
                    $queryPatterns[$table] = [];
                }
                $queryPatterns[$table][] = $query;
            }
        }
        
        // Check for repeated queries to the same table
        foreach ($queryPatterns as $table => $tableQueries) {
            if (count($tableQueries) > 5) {
                $n1Queries[] = [
                    'table' => $table,
                    'count' => count($tableQueries),
                    'queries' => array_slice($tableQueries, 0, 3) // Show first 3 examples
                ];
            }
        }
        
        return $n1Queries;
    }
    
    /**
     * Extract table name from SQL query
     */
    private function extractTableFromSql(string $sql): ?string
    {
        // Simple regex to extract table name
        if (preg_match('/FROM\s+`?(\w+)`?\s/i', $sql, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/JOIN\s+`?(\w+)`?\s/i', $sql, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
} 