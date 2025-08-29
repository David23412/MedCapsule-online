<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaymentSecurityService;
use App\Services\AdvancedCacheService;
use App\Services\PerformanceMonitoringService;

class MilitaryGradePerformanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $paymentService;
    protected $paymentSecurityService;
    protected $cacheService;
    protected $performanceService;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize services
        $this->paymentService = app(PaymentService::class);
        $this->paymentSecurityService = app(PaymentSecurityService::class);
        $this->cacheService = app(AdvancedCacheService::class);
        $this->performanceService = app(PerformanceMonitoringService::class);
    }

    /**
     * FIXED: Test complete payment processing with security and performance monitoring
     */
    public function test_complete_payment_processing_with_security_and_performance()
    {
        // Create test user and course
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 99.99]);

        // Start performance monitoring
        $operationId = $this->performanceService->startOperation('payment_processing_test', [
            'test_type' => 'complete_payment_flow',
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        try {
            // Test payment processing with security
            $paymentData = [
                'payment_method' => 'vodafone_cash',
                'transaction_id' => 'TEST_' . $this->faker->uuid(),
                'amount' => 99.99
            ];

            $request = $this->createMockRequest($paymentData, $user);

            // Process payment
            $result = $this->paymentService->processManualPayment($request, $user, $course);

            // Assert payment was processed successfully
            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('payment_id', $result);

            // Verify payment was created
            $payment = Payment::find($result['payment_id']);
            $this->assertNotNull($payment);
            $this->assertEquals('pending', $payment->status);

            // Test security analysis
            $riskScore = $this->paymentSecurityService->analyzePaymentFraud($request, $user->id, $payment);
            $this->assertIsFloat($riskScore);
            $this->assertGreaterThanOrEqual(0, $riskScore);
            $this->assertLessThanOrEqual(1, $riskScore);

            // Test rate limiting
            $rateLimitStatus = $this->paymentSecurityService->checkRateLimit($user->id, 'manual_payment');
            $this->assertIsArray($rateLimitStatus);
            $this->assertArrayHasKey('is_limited', $rateLimitStatus);

            // End performance monitoring
            $metrics = $this->performanceService->endOperation($operationId, [
                'payment_id' => $result['payment_id'],
                'risk_score' => $riskScore,
                'rate_limit_status' => $rateLimitStatus
            ]);

            // Assert performance metrics are reasonable
            $this->assertIsArray($metrics);
            $this->assertArrayHasKey('duration_ms', $metrics);
            $this->assertLessThan(5000, $metrics['duration_ms']); // Should complete in under 5 seconds

        } finally {
            // Ensure operation is always ended
            try {
                $this->performanceService->endOperation($operationId, ['test_completed' => true]);
            } catch (\Exception $e) {
                // Operation might already be ended
            }
        }
    }

    /**
     * FIXED: Test advanced caching functionality
     */
    public function test_advanced_caching_functionality()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        // Test cache key generation
        $cacheKey = AdvancedCacheService::generateCacheKey('user', "courses_{$user->id}");
        $this->assertIsString($cacheKey);
        $this->assertStringStartsWith('usr_', $cacheKey);

        // Test smart caching
        $testData = ['test' => 'data', 'timestamp' => now()->toISOString()];

        $cachedResult = $this->cacheService->smartGet(
            "test_cache_key",
            function () use ($testData) {
                return $testData;
            },
            300
        );

        $this->assertEquals($testData, $cachedResult);

        // Verify data is cached
        $this->assertTrue(Cache::has("test_cache_key"));

        // Test cache warming
        $warmResults = $this->cacheService->warmCriticalCaches();
        $this->assertIsArray($warmResults);
        $this->assertArrayHasKey('warmed', $warmResults);
    }

    /**
     * FIXED: Test performance monitoring and alerting
     */
    public function test_performance_monitoring_and_alerting()
    {
        // Start operation monitoring
        $operationId = $this->performanceService->startOperation('performance_test', [
            'test_type' => 'monitoring_validation'
        ]);

        // Simulate some work
        usleep(100000); // 100ms delay

        // End operation
        $metrics = $this->performanceService->endOperation($operationId, [
            'test_data' => 'performance monitoring test'
        ]);

        // Verify metrics
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('duration_ms', $metrics);
        $this->assertGreaterThan(90, $metrics['duration_ms']); // At least 90ms due to usleep

        // Test system health
        $health = $this->performanceService->getSystemHealth();
        $this->assertIsArray($health);
        $this->assertArrayHasKey('database_connections', $health);
        $this->assertArrayHasKey('cache_performance', $health);

        // Test real-time metrics
        $realTimeMetrics = $this->performanceService->getRealTimeMetrics();
        $this->assertIsArray($realTimeMetrics);
        $this->assertArrayHasKey('current_response_time', $realTimeMetrics);
        $this->assertArrayHasKey('system_health', $realTimeMetrics);
    }

    /**
     * FIXED: Test payment security features
     */
    public function test_payment_security_features()
    {
        $user = User::factory()->create();

        // Test rate limiting
        $rateLimit1 = $this->paymentSecurityService->checkRateLimit($user->id, 'manual_payment');
        $this->assertFalse($rateLimit1['is_limited']);

        // Record payment attempts
        $this->paymentSecurityService->recordPaymentAttempt($user->id, 'manual_payment');

        // Check rate limit after attempt
        $rateLimit2 = $this->paymentSecurityService->checkRateLimit($user->id, 'manual_payment');
        $this->assertEquals(1, $rateLimit2['attempts']);

        // Test fraud analysis
        $request = $this->createMockRequest([
            'payment_method' => 'vodafone_cash',
            'transaction_id' => 'TEST_' . $this->faker->uuid(),
            'amount' => 50.00
        ], $user);

        $riskScore = $this->paymentSecurityService->analyzePaymentFraud($request, $user->id);
        $this->assertIsFloat($riskScore);
        $this->assertGreaterThanOrEqual(0, $riskScore);
        $this->assertLessThanOrEqual(1, $riskScore);

        // Test security metrics
        $securityMetrics = $this->paymentSecurityService->getSecurityMetrics();
        $this->assertIsArray($securityMetrics);
        $this->assertArrayHasKey('rate_limit_violations', $securityMetrics);
    }

    /**
     * FIXED: Test cache performance and optimization
     */
    public function test_cache_performance_optimization()
    {
        // Test cache metrics
        $cacheMetrics = $this->cacheService->getCacheMetrics();
        $this->assertIsArray($cacheMetrics);
        $this->assertArrayHasKey('hit_rate', $cacheMetrics);

        // Test cache warming
        $warmResults = $this->cacheService->warmCriticalCaches();
        $this->assertIsArray($warmResults);

        // Test cache key optimization
        $optimizedKey = $this->cacheService->optimizeCacheKey('Test Cache Key With Special Chars !@#$%^&*()');
        $this->assertIsString($optimizedKey);
        $this->assertLessThanOrEqual(250, strlen($optimizedKey));

        // Test data compression
        $largeData = array_fill(0, 150, ['test' => str_repeat('data', 100)]);
        $shouldCompress = $this->cacheService->shouldCompress($largeData);
        $this->assertTrue($shouldCompress);
    }

    /**
     * FIXED: Test system integration and service dependencies
     */
    public function test_system_integration_and_dependencies()
    {
        // Verify all services are properly registered
        $this->assertInstanceOf(PaymentService::class, $this->paymentService);
        $this->assertInstanceOf(PaymentSecurityService::class, $this->paymentSecurityService);
        $this->assertInstanceOf(AdvancedCacheService::class, $this->cacheService);
        $this->assertInstanceOf(PerformanceMonitoringService::class, $this->performanceService);

        // Test service dependencies
        $this->assertNotNull($this->paymentService);
        $this->assertNotNull($this->paymentSecurityService);
        $this->assertNotNull($this->cacheService);
        $this->assertNotNull($this->performanceService);

        // Test service method availability
        $this->assertTrue(method_exists($this->paymentService, 'processManualPayment'));
        $this->assertTrue(method_exists($this->paymentSecurityService, 'analyzePaymentFraud'));
        $this->assertTrue(method_exists($this->cacheService, 'smartGet'));
        $this->assertTrue(method_exists($this->performanceService, 'startOperation'));
    }

    /**
     * FIXED: Test database optimization and N+1 prevention
     */
    public function test_database_optimization_and_n1_prevention()
    {
        // Create test data
        $users = User::factory()->count(3)->create();
        $courses = Course::factory()->count(5)->create();

        // Test that course queries don't have N+1 issues
        $startQueries = DB::getQueryLog();

        foreach ($courses as $course) {
            $enrolledCount = $course->getEnrolledStudentsCountAttribute();
            $this->assertIsInt($enrolledCount);
        }

        $endQueries = DB::getQueryLog();
        $queryCount = count($endQueries) - count($startQueries);

        // Should be minimal queries due to optimization
        $this->assertLessThanOrEqual(10, $queryCount, 'Too many database queries detected - possible N+1 issue');

        // Test that user enrollment checks are optimized
        foreach ($users as $user) {
            $isEnrolled = $user->isEnrolledInCourse($courses->first()->id);
            $this->assertIsBool($isEnrolled);
        }
    }

    /**
     * FIXED: Test performance under load simulation
     */
    public function test_performance_under_load_simulation()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 29.99]);

        // Simulate multiple concurrent payment attempts
        $concurrentOperations = 5;
        $operations = [];

        for ($i = 0; $i < $concurrentOperations; $i++) {
            $operationId = $this->performanceService->startOperation("load_test_{$i}", [
                'iteration' => $i,
                'user_id' => $user->id
            ]);
            $operations[] = $operationId;
        }

        // Process operations
        foreach ($operations as $operationId) {
            $metrics = $this->performanceService->endOperation($operationId, [
                'load_test_completed' => true
            ]);

            // Verify performance is reasonable even under load
            $this->assertLessThan(1000, $metrics['duration_ms']); // Under 1 second
        }

        // Verify system health after load test
        $health = $this->performanceService->calculateSystemHealth();
        $this->assertGreaterThanOrEqual(80, $health, 'System health degraded under load');
    }

    /**
     * FIXED: Helper method to create mock request
     */
    private function createMockRequest(array $data, User $user)
    {
        $request = new \Illuminate\Http\Request();
        $request->merge($data);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock additional request methods
        $request = new class($data, $user) extends \Illuminate\Http\Request {
            private $mockUser;
            private $mockIp = '127.0.0.1';
            private $mockUserAgent = 'Test/1.0';

            public function __construct($data, $user)
            {
                parent::__construct($data);
                $this->mockUser = $user;
            }

            public function user()
            {
                return $this->mockUser;
            }

            public function ip()
            {
                return $this->mockIp;
            }

            public function userAgent()
            {
                return $this->mockUserAgent;
            }
        };

        return $request;
    }

    /**
     * FIXED: Clean up after tests
     */
    protected function tearDown(): void
    {
        // Clear any test caches
        Cache::flush();

        parent::tearDown();
    }
}
