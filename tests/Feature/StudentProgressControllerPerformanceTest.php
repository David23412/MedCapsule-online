<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Http\Controllers\Tutor\StudentProgressController;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LIGHTNING-FAST STUDENT PROGRESS CONTROLLER PERFORMANCE TEST
 * Verifying 100% optimization and F-16 Raptor speed
 */
class StudentProgressControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private $tutor;
    private $student;
    private $course;
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->course = Course::factory()->create();
        
        // Create quiz attempts for testing
        QuizAttempt::factory()->count(10)->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'score' => rand(70, 100)
        ]);

        $this->controller = new StudentProgressController(new StudentProgressService());
        $this->service = new StudentProgressService();
    }

    /**
     * @test
     * Test F-16 Raptor speed performance - must complete under 100ms
     */
    public function it_achieves_f16_raptor_speed_performance()
    {
        $this->actingAs($this->tutor);
        
        $startTime = microtime(true);
        
        // Test comprehensive progress retrieval
        $progressData = $this->service->getComprehensiveProgress($this->student->id, [
            'date_range' => 'month',
            'include_details' => true
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Assert F-16 Raptor speed (under 100ms)
        $this->assertLessThan(100, $executionTime, "Performance test failed! Execution time: {$executionTime}ms (should be < 100ms)");
        
        // Verify data integrity
        $this->assertArrayHasKey('student_info', $progressData);
        $this->assertArrayHasKey('performance_metrics', $progressData);
        $this->assertEquals($this->student->id, $progressData['student_info']['id']);
    }

    /**
     * @test
     * Test real-time updates performance - must complete under 50ms
     */
    public function it_achieves_lightning_fast_realtime_updates()
    {
        $this->actingAs($this->tutor);
        
        $startTime = microtime(true);
        
        // Test real-time updates
        $realtimeData = $this->service->getRealtimeUpdates($this->student->id, [
            'date_range' => 'week'
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Assert lightning speed (under 50ms)
        $this->assertLessThan(50, $executionTime, "Real-time updates too slow! Execution time: {$executionTime}ms (should be < 50ms)");
        
        // Verify data structure
        $this->assertArrayHasKey('recent_activity', $realtimeData);
        $this->assertArrayHasKey('last_updated', $realtimeData);
        $this->assertArrayHasKey('activity_count', $realtimeData);
    }

    /**
     * @test
     * Test performance analytics speed - must complete under 75ms
     */
    public function it_delivers_ultra_fast_performance_analytics()
    {
        $this->actingAs($this->tutor);
        
        $startTime = microtime(true);
        
        // Test performance analytics
        $analyticsData = $this->service->getPerformanceAnalytics($this->student->id, [
            'date_range' => 'quarter'
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Assert ultra-fast speed (under 75ms)
        $this->assertLessThan(75, $executionTime, "Analytics too slow! Execution time: {$executionTime}ms (should be < 75ms)");
        
        // Verify analytics structure
        $this->assertArrayHasKey('daily_analytics', $analyticsData);
        $this->assertArrayHasKey('trends', $analyticsData);
        $this->assertArrayHasKey('generated_at', $analyticsData);
    }

    /**
     * @test
     * Test caching effectiveness - second call should be under 10ms
     */
    public function it_uses_aggressive_caching_for_maximum_speed()
    {
        $this->actingAs($this->tutor);
        
        // First call to populate cache
        $this->service->getComprehensiveProgress($this->student->id, [
            'date_range' => 'month',
            'include_details' => true
        ]);
        
        // Second call should hit cache
        $startTime = microtime(true);
        
        $cachedData = $this->service->getComprehensiveProgress($this->student->id, [
            'date_range' => 'month',
            'include_details' => true
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Assert cache hit speed (under 10ms)
        $this->assertLessThan(10, $executionTime, "Cache hit too slow! Execution time: {$executionTime}ms (should be < 10ms)");
        
        // Verify cached data integrity
        $this->assertNotEmpty($cachedData);
        $this->assertArrayHasKey('student_info', $cachedData);
    }

    /**
     * @test
     * Test bulk operations performance - must handle 100 students under 500ms
     */
    public function it_handles_bulk_operations_at_lightning_speed()
    {
        $this->actingAs($this->tutor);
        
        // Create 100 students for bulk testing
        $students = User::factory()->count(100)->create(['role' => 'student']);
        $studentIds = $students->pluck('id')->toArray();
        
        $startTime = microtime(true);
        
        // Test bulk progress update
        $bulkResults = $this->service->bulkUpdateProgress($studentIds, [
            'date_range' => 'week'
        ]);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Assert bulk operation speed (under 500ms for 100 students)
        $this->assertLessThan(500, $executionTime, "Bulk operations too slow! Execution time: {$executionTime}ms (should be < 500ms for 100 students)");
        
        // Verify bulk results
        $this->assertCount(100, $bulkResults);
        $this->assertArrayHasKey($studentIds[0], $bulkResults);
    }

    /**
     * @test
     * Test database query optimization - should use minimal queries
     */
    public function it_uses_optimized_database_queries()
    {
        $this->actingAs($this->tutor);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Execute comprehensive progress retrieval
        $this->service->getComprehensiveProgress($this->student->id, [
            'date_range' => 'month',
            'include_details' => true
        ]);
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        // Assert minimal query count (should be under 5 queries)
        $this->assertLessThan(5, $queryCount, "Too many database queries! Query count: {$queryCount} (should be < 5)");
        
        DB::disableQueryLog();
    }

    /**
     * @test
     * Test memory usage optimization
     */
    public function it_maintains_optimal_memory_usage()
    {
        $this->actingAs($this->tutor);
        
        $memoryBefore = memory_get_usage(true);
        
        // Execute multiple operations
        for ($i = 0; $i < 10; $i++) {
            $this->service->getComprehensiveProgress($this->student->id, [
                'date_range' => 'month'
            ]);
            
            $this->service->getRealtimeUpdates($this->student->id, [
                'date_range' => 'week'
            ]);
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Assert memory usage under 10MB
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, "Memory usage too high! Used: " . ($memoryUsed / 1024 / 1024) . "MB (should be < 10MB)");
    }

    /**
     * @test
     * Test overall system performance score - must achieve 100%
     */
    public function it_achieves_100_percent_performance_score()
    {
        $this->actingAs($this->tutor);
        
        $performanceScores = [];
        
        // Test 1: Speed test (40% weight)
        $startTime = microtime(true);
        $this->service->getComprehensiveProgress($this->student->id, ['date_range' => 'month']);
        $speedTime = (microtime(true) - $startTime) * 1000;
        $speedScore = max(0, 100 - ($speedTime * 2)); // Lose 2 points per ms over 0
        $performanceScores['speed'] = min(100, $speedScore) * 0.4;
        
        // Test 2: Cache efficiency (30% weight)
        Cache::flush();
        $startTime = microtime(true);
        $this->service->getComprehensiveProgress($this->student->id, ['date_range' => 'month']);
        $firstCall = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        $this->service->getComprehensiveProgress($this->student->id, ['date_range' => 'month']);
        $secondCall = microtime(true) - $startTime;
        
        $cacheEfficiency = ($firstCall / max($secondCall, 0.001)) * 10; // Should be much faster
        $performanceScores['cache'] = min(100, $cacheEfficiency) * 0.3;
        
        // Test 3: Query optimization (30% weight)
        DB::enableQueryLog();
        $this->service->getComprehensiveProgress($this->student->id, ['date_range' => 'month', 'include_details' => true]);
        $queryCount = count(DB::getQueryLog());
        $queryScore = max(0, 100 - ($queryCount * 10)); // Lose 10 points per query over 0
        $performanceScores['queries'] = min(100, $queryScore) * 0.3;
        
        $totalScore = array_sum($performanceScores);
        
        // Assert 100% performance score
        $this->assertGreaterThanOrEqual(95, $totalScore, 
            "Performance score too low! Score: {$totalScore}% (should be >= 95%)\n" .
            "Speed: " . round($performanceScores['speed'] / 0.4, 2) . "%\n" .
            "Cache: " . round($performanceScores['cache'] / 0.3, 2) . "%\n" .
            "Queries: " . round($performanceScores['queries'] / 0.3, 2) . "%"
        );
        
        echo "\n🚀 PERFORMANCE ACHIEVED: {$totalScore}% - LIGHTNING FAST! ⚡\n";
    }
}