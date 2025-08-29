<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use App\Services\AdvancedCacheService;
use App\Services\PerformanceMonitoringService;

class SimpleIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $cacheService;
    protected $performanceService;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize services
        $this->cacheService = app(AdvancedCacheService::class);
        $this->performanceService = app(PerformanceMonitoringService::class);
    }

    /**
     * Test that all services are properly integrated
     */
    public function test_services_are_properly_integrated()
    {
        // Test that services are properly instantiated
        $this->assertInstanceOf(AdvancedCacheService::class, $this->cacheService);
        $this->assertInstanceOf(PerformanceMonitoringService::class, $this->performanceService);

        // Test basic service functionality
        $this->assertTrue(method_exists($this->cacheService, 'smartGet'));
        $this->assertTrue(method_exists($this->performanceService, 'startOperation'));
    }

    /**
     * Test cache service functionality
     */
    public function test_cache_service_functionality()
    {
        // Test cache key generation
        $key = AdvancedCacheService::generateCacheKey('user', 'test_123');
        $this->assertIsString($key);
        $this->assertStringStartsWith('usr_', $key);

        // Test smart caching
        $testData = ['message' => 'Hello World', 'timestamp' => now()->toISOString()];

        $cachedResult = $this->cacheService->smartGet(
            'test_integration_key',
            function () use ($testData) {
                return $testData;
            },
            300
        );

        $this->assertEquals($testData, $cachedResult);
        $this->assertTrue(Cache::has('test_integration_key'));
    }

    /**
     * Test performance monitoring functionality
     */
    public function test_performance_monitoring_functionality()
    {
        // Start operation monitoring
        $operationId = $this->performanceService->startOperation('integration_test', [
            'test_type' => 'simple_integration'
        ]);

        // Simulate some work
        usleep(50000); // 50ms delay

        // End operation
        $metrics = $this->performanceService->endOperation($operationId, [
            'test_completed' => true
        ]);

        // Verify metrics
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('duration_ms', $metrics);
        $this->assertGreaterThan(40, $metrics['duration_ms']); // At least 40ms due to usleep

        // Test system health
        $health = $this->performanceService->getSystemHealth();
        $this->assertIsArray($health);
        $this->assertArrayHasKey('database_connections', $health);
    }

    /**
     * Test cache metrics
     */
    public function test_cache_metrics_functionality()
    {
        // Test cache metrics
        $cacheMetrics = $this->cacheService->getCacheMetrics();
        $this->assertIsArray($cacheMetrics);
        $this->assertArrayHasKey('hit_rate', $cacheMetrics);
        $this->assertArrayHasKey('memory_usage', $cacheMetrics);
        $this->assertArrayHasKey('compression_savings', $cacheMetrics);
    }

    /**
     * Test that services work together
     */
    public function test_services_work_together()
    {
        // Start performance monitoring
        $operationId = $this->performanceService->startOperation('service_integration_test');

        // Use cache service within monitored operation
        $cacheKey = AdvancedCacheService::generateCacheKey('test', 'integration');
        $testData = ['integrated' => true, 'timestamp' => now()];

        $cachedResult = $this->cacheService->smartGet(
            $cacheKey,
            function () use ($testData) {
                return $testData;
            },
            300
        );

        // End performance monitoring
        $metrics = $this->performanceService->endOperation($operationId, [
            'cache_used' => true,
            'data_cached' => $cachedResult
        ]);

        // Verify integration worked
        $this->assertEquals($testData, $cachedResult);
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('duration_ms', $metrics);
    }
}
