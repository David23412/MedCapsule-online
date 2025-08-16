<?php

namespace Tests\Integration\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

trait PerformanceTestHelpers
{
    /**
     * Assert that query count is within threshold for an endpoint
     */
    protected function assertQueryCountWithinThreshold(string $endpoint, array $queries, string $context = ''): void
    {
        $threshold = Config::get("performance.query_thresholds.{$endpoint}");
        $buffer = Config::get('performance.testing.performance_buffer', 2);
        $maxAllowed = $threshold + $buffer;
        $queryCount = count($queries);
        
        $this->assertLessThanOrEqual(
            $maxAllowed,
            $queryCount,
            $this->buildQueryCountFailureMessage($endpoint, $queryCount, $threshold, $queries, $context)
        );
    }

    /**
     * Build detailed failure message for query count violations
     */
    protected function buildQueryCountFailureMessage(
        string $endpoint, 
        int $actualCount, 
        int $threshold, 
        array $queries, 
        string $context = ''
    ): string {
        $message = "❌ N+1 DETECTED in {$endpoint} — {$actualCount} queries > {$threshold} limit.";
        
        if ($context) {
            $message .= " Context: {$context}";
        }
        
        $message .= "\n\n🔧 Fix eager loading in the controller!";
        $message .= "\n📊 Query breakdown:\n";
        
        if (Config::get('performance.testing.log_query_details', false)) {
            $message .= $this->formatQueriesForDebugging($queries);
        } else {
            $message .= "Set PERFORMANCE_LOG_QUERY_DETAILS=true to see query details";
        }
        
        $message .= "\n\n💡 Common fixes:";
        $message .= "\n- Add ->with(['relationship']) to eager load";
        $message .= "\n- Use explicit methods instead of accessors";
        $message .= "\n- Check for loops calling model methods";
        
        return $message;
    }

    /**
     * Format queries for debugging output
     */
    protected function formatQueriesForDebugging(array $queries): string
    {
        $formatted = [];
        $queryTypes = [];
        
        foreach ($queries as $i => $query) {
            $sql = $query['query'];
            $time = isset($query['time']) ? round($query['time'], 2) . 'ms' : 'N/A';
            
            // Categorize query types
            if (preg_match('/^SELECT.*FROM\s+(\w+)/', $sql, $matches)) {
                $table = $matches[1];
                $queryTypes[$table] = ($queryTypes[$table] ?? 0) + 1;
            }
            
            $formatted[] = sprintf(
                "%2d. [%s] %s",
                $i + 1,
                $time,
                $this->simplifyQuery($sql)
            );
        }
        
        $result = implode("\n", $formatted);
        
        if (!empty($queryTypes)) {
            $result .= "\n\n📈 Query distribution:\n";
            foreach ($queryTypes as $table => $count) {
                $result .= "- {$table}: {$count} queries\n";
            }
        }
        
        return $result;
    }

    /**
     * Simplify query for readability
     */
    protected function simplifyQuery(string $sql): string
    {
        // Remove parameter bindings for readability
        $sql = preg_replace('/\?/', '?', $sql);
        
        // Truncate very long queries
        if (strlen($sql) > 100) {
            $sql = substr($sql, 0, 97) . '...';
        }
        
        return $sql;
    }

    /**
     * Measure query count for a callable
     */
    protected function measureQueryCount(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $result = $callback();
        $endTime = microtime(true);
        
        $queries = DB::getQueryLog();
        
        return [
            'result' => $result,
            'queries' => $queries,
            'query_count' => count($queries),
            'execution_time' => round(($endTime - $startTime) * 1000, 2), // milliseconds
        ];
    }

    /**
     * Assert response time is within acceptable limits
     */
    protected function assertResponseTimeAcceptable(float $responseTimeMs, int $maxMs = 1000): void
    {
        $this->assertLessThan(
            $maxMs,
            $responseTimeMs,
            "❌ SLOW RESPONSE — {$responseTimeMs}ms > {$maxMs}ms limit. Optimize queries or add caching!"
        );
    }

    /**
     * Create realistic test data for performance testing
     */
    protected function createPerformanceTestData(): array
    {
        $users = \App\Models\User::factory()->count(5)->create(['role' => \App\Models\User::ROLE_STUDENT]);
        $courses = \App\Models\Course::factory()->count(10)->create();
        $topics = [];
        
        foreach ($courses as $course) {
            $courseTopics = \App\Models\Topic::factory()->count(3)->for($course)->create();
            $topics = array_merge($topics, $courseTopics->toArray());
        }
        
        // Enroll users in random courses
        foreach ($users as $user) {
            foreach ($courses->random(3) as $course) {
                $user->enrollInCourse($course->id);
            }
        }
        
        return [
            'users' => $users,
            'courses' => $courses,
            'topics' => collect($topics),
        ];
    }

    /**
     * Verify that query count doesn't scale exponentially with data size
     */
    protected function assertLinearScaling(string $endpoint, callable $testCallback): void
    {
        // Test with small dataset
        $smallDataMetrics = $this->measureQueryCount($testCallback);
        $smallQueryCount = $smallDataMetrics['query_count'];
        
        // Add more data (simulate growth)
        $this->createPerformanceTestData();
        
        // Test with larger dataset
        $largeDataMetrics = $this->measureQueryCount($testCallback);
        $largeQueryCount = $largeDataMetrics['query_count'];
        
        // Query count should not increase significantly
        $maxAllowedIncrease = 5; // Allow some variance
        
        $this->assertLessThanOrEqual(
            $smallQueryCount + $maxAllowedIncrease,
            $largeQueryCount,
            "❌ EXPONENTIAL SCALING DETECTED in {$endpoint} — " .
            "Small dataset: {$smallQueryCount} queries, Large dataset: {$largeQueryCount} queries. " .
            "This indicates N+1 queries are still present!"
        );
    }
}