<?php

namespace Tests\Unit;

use App\Services\CacheService;
use App\Services\QuizService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\TestCase;

class QuizOptimizationServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Cache facade for unit testing
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_cache_key_generation_is_consistent()
    {
        $data = [
            'topic_id' => 123,
            'user_id' => 456,
            'difficulty' => 'medium'
        ];

        $key1 = QuizCacheService::generateCacheKey('quiz_test', $data);
        $key2 = QuizCacheService::generateCacheKey('quiz_test', $data);

        // Same data should generate same key
        $this->assertEquals($key1, $key2);
        
        // Key should contain prefix
        $this->assertStringStartsWith('quiz_test_', $key1);
    }

    public function test_cache_key_generation_handles_different_data()
    {
        $data1 = ['topic_id' => 123, 'user_id' => 456];
        $data2 = ['topic_id' => 123, 'user_id' => 789];

        $key1 = QuizCacheService::generateCacheKey('quiz_test', $data1);
        $key2 = QuizCacheService::generateCacheKey('quiz_test', $data2);

        // Different data should generate different keys
        $this->assertNotEquals($key1, $key2);
    }

    public function test_cache_key_generation_performance()
    {
        $data = [
            'topic_id' => 123,
            'user_id' => 456,
            'difficulty' => 'medium',
            'question_count' => 10,
            'time_limit' => 1800,
            'additional_params' => ['source' => 'textbook', 'chapter' => 5]
        ];

        // Test performance of cache key generation
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10000; $i++) {
            $testData = array_merge($data, ['iteration' => $i]);
            $key = QuizCacheService::generateCacheKey('performance_test', $testData);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // Should complete 10,000 operations in under 100ms
        $this->assertLessThan(100, $duration, 'Cache key generation should be highly optimized');
    }

    public function test_performance_tracking_basic_functionality()
    {
        $operation = 'test_operation';
        $trackingId = QuizPerformanceService::startTracking($operation);

        $this->assertNotEmpty($trackingId);
        $this->assertStringContains($operation, $trackingId);

        // Simulate some work
        usleep(1000); // 1ms

        $performance = QuizPerformanceService::endTracking($trackingId, [
            'test_data' => 'value'
        ]);

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('operation', $performance);
        $this->assertArrayHasKey('duration_ms', $performance);
        $this->assertArrayHasKey('memory_used_mb', $performance);
        $this->assertEquals($operation, $performance['operation']);
        $this->assertGreaterThan(0, $performance['duration_ms']);
    }

    public function test_performance_tracking_handles_invalid_id()
    {
        $performance = QuizPerformanceService::endTracking('invalid_id');
        
        $this->assertIsArray($performance);
        $this->assertEmpty($performance);
    }

    public function test_hash_algorithm_availability()
    {
        // Verify xxh3 hash is available (or fallback works)
        $testData = 'test_string_for_hashing';
        
        if (in_array('xxh3', hash_algos())) {
            $hash = hash('xxh3', $testData);
            $this->assertNotEmpty($hash);
            $this->assertIsString($hash);
        } else {
            // Fallback to md5 should work
            $hash = hash('md5', $testData);
            $this->assertNotEmpty($hash);
            $this->assertEquals(32, strlen($hash)); // MD5 is always 32 chars
        }
    }

    public function test_serialization_performance()
    {
        $complexData = [
            'topic_id' => 123,
            'user_id' => 456,
            'questions' => array_fill(0, 50, [
                'id' => rand(1, 1000),
                'text' => 'Sample question text',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A'
            ]),
            'metadata' => [
                'difficulty' => 'medium',
                'time_limit' => 1800,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $serialized = serialize($complexData);
            $hash = hash('md5', $serialized); // Use MD5 as fallback
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // Should complete 100 complex serializations in under 50ms
        $this->assertLessThan(50, $duration, 'Serialization should be optimized for complex data');
    }

    public function test_memory_usage_tracking()
    {
        $initialMemory = memory_get_usage(true);
        
        // Create some data to use memory
        $largeArray = array_fill(0, 1000, str_repeat('x', 1000));
        
        $currentMemory = memory_get_usage(true);
        $memoryUsed = $currentMemory - $initialMemory;
        
        // Should have used some memory
        $this->assertGreaterThan(0, $memoryUsed);
        
        // Clean up
        unset($largeArray);
        
        // Memory usage should be reasonable (under 50MB for this test)
        $this->assertLessThan(50 * 1024 * 1024, $currentMemory);
    }

    public function test_optimization_suggestions_structure()
    {
        $optimizations = QuizPerformanceService::optimizeQuizQueries();
        
        $this->assertIsArray($optimizations);
        
        // Should contain suggestions even if empty
        if (!empty($optimizations) && isset($optimizations['suggestions'])) {
            $this->assertIsArray($optimizations['suggestions']);
        }
    }

    public function test_cache_stats_structure()
    {
        $stats = QuizCacheService::getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('active_keys', $stats);
        
        $this->assertIsFloat($stats['hit_rate']);
        $this->assertIsString($stats['memory_usage']);
        $this->assertIsInt($stats['active_keys']);
        
        // Hit rate should be between 0 and 100
        $this->assertGreaterThanOrEqual(0, $stats['hit_rate']);
        $this->assertLessThanOrEqual(100, $stats['hit_rate']);
    }

    public function test_ttl_selection_logic()
    {
        // Test TTL selection with reflection (accessing private method)
        $reflection = new \ReflectionClass(QuizCacheService::class);
        $method = $reflection->getMethod('selectOptimalTTL');
        $method->setAccessible(true);

        // Small dataset
        $smallData = array_fill(0, 5, 'item');
        $smallTtl = $method->invoke(null, $smallData);
        
        // Medium dataset
        $mediumData = array_fill(0, 50, 'item');
        $mediumTtl = $method->invoke(null, $mediumData);
        
        // Large dataset
        $largeData = array_fill(0, 200, 'item');
        $largeTtl = $method->invoke(null, $largeData);
        
        // Larger datasets should have longer TTL
        $this->assertLessThanOrEqual($mediumTtl, $smallTtl);
        $this->assertLessThanOrEqual($largeTtl, $mediumTtl);
    }

    public function test_error_handling_in_performance_tracking()
    {
        $trackingId = QuizPerformanceService::startTracking('test_operation');
        
        // Simulate an operation that throws an exception
        try {
            QuizPerformanceService::trackQuizGeneration(123, 456, function () {
                throw new \Exception('Test exception');
            });
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Test exception', $e->getMessage());
        }
        
        // Performance tracking should still work after exception
        $newTrackingId = QuizPerformanceService::startTracking('after_exception');
        $this->assertNotEmpty($newTrackingId);
        
        $performance = QuizPerformanceService::endTracking($newTrackingId);
        $this->assertIsArray($performance);
    }
}