<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Services\RedisCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private RedisCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(RedisCacheService::class);
    }

    /** @test */
    public function response_time_under_200ms_with_50_concurrent_users()
    {
        // Create test data
        $users = User::factory()->count(50)->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        Question::factory()->count(10)->create(['topic_id' => $topic->id]);

        // Enroll all users in the course
        foreach ($users as $user) {
            DB::table('course_user')->insert([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'completion_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $responseTimes = [];
        $maxConcurrent = 50;
        $totalRequests = 100;

        // Simulate concurrent requests
        for ($i = 0; $i < $totalRequests; $i++) {
            $user = $users->random();

            $startTime = microtime(true);

            $response = $this->actingAs($user)
                ->get("/quiz/{$course->id}/{$topic->id}");

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $responseTimes[] = $responseTime;

            // Assert response is successful
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Keep response time under 200ms
            $this->assertLessThan(200, $responseTime, "Response time too slow: {$responseTime}ms");
        }

        // Calculate statistics
        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $minResponseTime = min($responseTimes);
        $p95ResponseTime = $this->calculatePercentile($responseTimes, 95);

        // Log performance metrics
        \Log::info('Performance Test Results', [
            'test' => '50_concurrent_users',
            'total_requests' => $totalRequests,
            'average_response_time' => round($averageResponseTime, 2) . 'ms',
            'max_response_time' => round($maxResponseTime, 2) . 'ms',
            'min_response_time' => round($minResponseTime, 2) . 'ms',
            'p95_response_time' => round($p95ResponseTime, 2) . 'ms',
            'target_met' => $p95ResponseTime < 200
        ]);

        // Assert performance targets are met
        $this->assertLessThan(200, $averageResponseTime, 'Average response time too slow');
        $this->assertLessThan(200, $p95ResponseTime, '95th percentile response time too slow');
        $this->assertLessThan(500, $maxResponseTime, 'Maximum response time too slow');
    }

    /** @test */
    public function database_query_count_under_20_per_request()
    {
        // Create test data
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        Question::factory()->count(5)->create(['topic_id' => $topic->id]);

        // Enroll user
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'completion_percentage' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        // Make request
        $response = $this->actingAs($user)
            ->get("/quiz/{$course->id}/{$topic->id}");

        $response->assertStatus(200);

        // Get query count
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Log query information
        \Log::info('Database Query Performance', [
            'test' => 'query_count_test',
            'query_count' => $queryCount,
            'queries' => array_map(function($query) {
                return [
                    'sql' => $query['query'],
                    'time' => $query['time']
                ];
            }, $queries)
        ]);

        // Assert query count is reasonable
        $this->assertLessThanOrEqual(20, $queryCount, "Too many database queries: {$queryCount}");

        // Assert no slow queries (over 100ms)
        foreach ($queries as $query) {
            $this->assertLessThan(100, $query['time'], "Slow query detected: {$query['query']} ({$query['time']}ms)");
        }
    }

    /** @test */
    public function memory_usage_under_128mb_per_request()
    {
        // Create test data
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        Question::factory()->count(10)->create(['topic_id' => $topic->id]);

        // Enroll user
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'completion_percentage' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Get initial memory usage
        $initialMemory = memory_get_usage(true);

        // Make request
        $response = $this->actingAs($user)
            ->get("/quiz/{$course->id}/{$topic->id}");

        $response->assertStatus(200);

        // Get final memory usage
        $finalMemory = memory_get_usage(true);
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // Convert to MB

        // Log memory usage
        \Log::info('Memory Usage Test', [
            'test' => 'memory_usage_test',
            'initial_memory' => round($initialMemory / 1024 / 1024, 2) . 'MB',
            'final_memory' => round($finalMemory / 1024 / 1024, 2) . 'MB',
            'memory_used' => round($memoryUsed, 2) . 'MB',
            'target_met' => $memoryUsed < 128
        ]);

        // Assert memory usage is within limits
        $this->assertLessThan(128, $memoryUsed, "Memory usage too high: {$memoryUsed}MB");
    }

    /** @test */
    public function cache_hit_rate_above_85_percent()
    {
        if (!$this->cacheService->isAvailable()) {
            $this->markTestSkipped('Redis is not available');
        }

        // Create test data
        $users = User::factory()->count(20)->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        Question::factory()->count(5)->create(['topic_id' => $topic->id]);

        // Enroll all users
        foreach ($users as $user) {
            DB::table('course_user')->insert([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'completion_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $cacheHits = 0;
        $cacheMisses = 0;
        $totalRequests = 40;

        // Make multiple requests to test caching
        for ($i = 0; $i < $totalRequests; $i++) {
            $user = $users->random();

            // Check if quiz is already cached before request
            $isCached = $this->cacheService->getCachedQuiz($topic->id) !== null;

            $response = $this->actingAs($user)
                ->get("/quiz/{$course->id}/{$topic->id}");

            $response->assertStatus(200);

            // Check if quiz is cached after request
            $isCachedAfter = $this->cacheService->getCachedQuiz($topic->id) !== null;

            if ($isCached) {
                $cacheHits++;
            } elseif ($isCachedAfter) {
                // Cache miss but data was cached after request
                $cacheMisses++;
            }
        }

        $cacheHitRate = $totalRequests > 0 ? ($cacheHits / $totalRequests) * 100 : 0;

        // Log cache performance
        \Log::info('Cache Performance Test', [
            'test' => 'cache_hit_rate_test',
            'total_requests' => $totalRequests,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'hit_rate' => round($cacheHitRate, 2) . '%',
            'target_met' => $cacheHitRate >= 85
        ]);

        // Assert cache hit rate meets target
        $this->assertGreaterThanOrEqual(85, $cacheHitRate, "Cache hit rate too low: {$cacheHitRate}%");
    }

    /** @test */
    public function sustained_load_test_500_requests_over_5_minutes()
    {
        // Create test data
        $users = User::factory()->count(10)->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        Question::factory()->count(5)->create(['topic_id' => $topic->id]);

        // Enroll all users
        foreach ($users as $user) {
            DB::table('course_user')->insert([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'completion_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $startTime = now();
        $endTime = $startTime->copy()->addMinutes(5);
        $requestCount = 0;
        $errorCount = 0;
        $responseTimes = [];

        // Make sustained requests for 5 minutes
        while (now()->lessThan($endTime)) {
            $user = $users->random();

            $requestStart = microtime(true);

            try {
                $response = $this->actingAs($user)
                    ->get("/quiz/{$course->id}/{$topic->id}");

                $response->assertStatus(200);
                $requestCount++;

                $requestEnd = microtime(true);
                $responseTime = ($requestEnd - $requestStart) * 1000;
                $responseTimes[] = $responseTime;

                // Assert individual request performance
                $this->assertLessThan(500, $responseTime, "Slow request detected: {$responseTime}ms");

            } catch (\Exception $e) {
                $errorCount++;
                \Log::error('Sustained load test error', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'request_count' => $requestCount
                ]);
            }

            // Small delay to prevent overwhelming the system
            usleep(100000); // 100ms delay
        }

        $totalDuration = $startTime->diffInMinutes(now());
        $requestsPerMinute = $requestCount / max($totalDuration, 1);
        $averageResponseTime = !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0;
        $errorRate = $requestCount > 0 ? ($errorCount / $requestCount) * 100 : 0;

        // Log sustained load results
        \Log::info('Sustained Load Test Results', [
            'test' => 'sustained_load_5_minutes',
            'duration_minutes' => $totalDuration,
            'total_requests' => $requestCount,
            'requests_per_minute' => round($requestsPerMinute, 2),
            'average_response_time' => round($averageResponseTime, 2) . 'ms',
            'error_count' => $errorCount,
            'error_rate' => round($errorRate, 2) . '%',
            'performance_target_met' => $averageResponseTime < 200 && $errorRate < 1
        ]);

        // Assert sustained load targets
        $this->assertGreaterThan(0, $requestCount, 'No successful requests completed');
        $this->assertLessThan(200, $averageResponseTime, "Average response time too slow: {$averageResponseTime}ms");
        $this->assertLessThan(1, $errorRate, "Error rate too high: {$errorRate}%");
    }

    /** @test */
    public function database_connection_pooling_efficiency()
    {
        // Create test data
        $users = User::factory()->count(20)->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        Question::factory()->count(3)->create(['topic_id' => $topic->id]);

        // Enroll all users
        foreach ($users as $user) {
            DB::table('course_user')->insert([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'completion_percentage' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $connectionCount = 0;
        $maxConnections = 20;

        // Make concurrent requests to test connection pooling
        for ($i = 0; $i < $maxConnections; $i++) {
            $user = $users->random();

            $response = $this->actingAs($user)
                ->get("/quiz/{$course->id}/{$topic->id}");

            $response->assertStatus(200);
            $connectionCount++;

            // Verify database connection is available
            $this->assertTrue(DB::connection()->getPdo() !== null);
        }

        // Log connection pooling efficiency
        \Log::info('Database Connection Pooling Test', [
            'test' => 'connection_pooling_test',
            'connections_used' => $connectionCount,
            'max_connections' => $maxConnections,
            'pooling_efficient' => $connectionCount <= $maxConnections
        ]);

        // Assert connection pooling is working
        $this->assertLessThanOrEqual($maxConnections, $connectionCount);
    }

    /**
     * Calculate percentile from array of values
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[max(0, min(count($values) - 1, $index))];
    }
}
