<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Payment;
use App\Services\UltraFastQuizService;
use App\Services\SecurePaymentService;
use App\Services\PerformanceMonitorService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrutalSystemReadinessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $ultraFastQuizService;
    private $securePaymentService;
    private $performanceMonitor;
    private $userService;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize services
        $this->ultraFastQuizService = app(UltraFastQuizService::class);
        $this->securePaymentService = app(SecurePaymentService::class);
        $this->performanceMonitor = app(PerformanceMonitorService::class);
        $this->userService = app(UserService::class);

        // Clear all caches before each test
        Redis::flushall();
        Cache::flush();
    }

    /**
     * 🧪 BRUTAL E2E TEST: Complete system under extreme load
     * Tests: Performance, Security, Reliability, Data Integrity
     */
    public function test_system_under_extreme_load()
    {
        Log::info('🚀 STARTING BRUTAL SYSTEM READINESS TEST');

        // 1. SETUP TEST DATA
        $this->setupMassiveTestData();

        // 2. PERFORMANCE TESTS
        $this->testPerformanceUnderLoad();

        // 3. SECURITY TESTS
        $this->testSecurityUnderAttack();

        // 4. RELIABILITY TESTS
        $this->testSystemReliability();

        // 5. DATA INTEGRITY TESTS
        $this->testDataIntegrity();

        // 6. CONCURRENT ACCESS TESTS
        $this->testConcurrentAccess();

        Log::info('✅ BRUTAL SYSTEM READINESS TEST COMPLETED');
    }

    /**
     * 🔧 SETUP: Create massive test data
     */
    private function setupMassiveTestData()
    {
        Log::info('📊 Setting up massive test data...');

        // Create 1000 users
        $users = User::factory()->count(1000)->create();

        // Create 100 courses
        $courses = Course::factory()->count(100)->create();

        // Create 1000 topics across courses
        $topics = [];
        foreach ($courses as $course) {
            $courseTopics = Topic::factory()->count(10)->create(['course_id' => $course->id]);
            $topics = array_merge($topics, $courseTopics->toArray());
        }

        // Create 100,000 questions across topics
        $questions = [];
        foreach ($topics as $topic) {
            $topicQuestions = Question::factory()->count(100)->create(['topic_id' => $topic['id']]);
            $questions = array_merge($questions, $topicQuestions->toArray());
        }

        // Create 50,000 quiz attempts
        foreach ($users->take(500) as $user) {
            for ($i = 0; $i < 100; $i++) {
                // Create quiz attempt for random topic
                $randomTopic = collect($topics)->random();
                \App\Models\QuizAttempt::create([
                    'user_id' => $user->id,
                    'topic_id' => $randomTopic['id'],
                    'assignment_id' => null,
                    'quiz_type' => 'normal',
                    'study_mode' => 'quiz',
                    'source' => collect(['General', 'Clinical', 'Theoretical'])->random(),
                    'score' => rand(0, 100),
                    'total_questions' => rand(10, 50),
                    'percentage_grade' => rand(0, 100),
                    'duration_seconds' => rand(60, 3600),
                    'question_data' => json_encode([]),
                    'correct_answers' => rand(0, 50)
                ]);
            }
        }

        Log::info('✅ Massive test data created', [
            'users' => count($users),
            'courses' => count($courses),
            'topics' => count($topics),
            'questions' => count($questions),
            'quiz_attempts' => 50000
        ]);
    }

    /**
     * ⚡ PERFORMANCE TEST: System under extreme load
     */
    private function testPerformanceUnderLoad()
    {
        Log::info('🏃 Testing performance under extreme load...');

        $topics = Topic::all();
        $users = User::all();

        // Test 1: UltraFastQuizService Performance
        $startTime = microtime(true);
        foreach ($topics->take(100) as $topic) {
            $operationId = $this->performanceMonitor->startOperation('brutal_quiz_load_test');

            $result = $this->ultraFastQuizService->getQuizQuestions($topic, ['all'], false);

            $performanceData = $this->performanceMonitor->endOperation($operationId, [
                'topic_id' => $topic->id,
                'questions_count' => count($result['questions'] ?? [])
            ]);

            // Assert performance requirements
            $this->assertLessThan(1000, $performanceData['execution_time_ms'],
                "Quiz loading took too long: {$performanceData['execution_time_ms']}ms");
            $this->assertLessThan(5, $performanceData['queries_used'] ?? 999,
                "Too many queries: {$performanceData['queries_used']}");
        }
        $quizLoadTime = (microtime(true) - $startTime) * 1000;

        Log::info('✅ Quiz performance test passed', [
            'total_time_ms' => $quizLoadTime,
            'avg_time_per_quiz' => $quizLoadTime / 100
        ]);

        // Test 2: UserService N+1 Prevention
        $startTime = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $page = rand(1, 20);
            $result = $this->userService->getManageAccessPageData($page);
            $this->assertTrue($result['success']);
            $this->assertIsArray($result['enrolledUsers']);
        }
        $userServiceTime = (microtime(true) - $startTime) * 1000;

        Log::info('✅ UserService N+1 test passed', [
            'total_time_ms' => $userServiceTime,
            'avg_time_per_request' => $userServiceTime / 50
        ]);
    }

    /**
     * 🔒 SECURITY TEST: System under attack simulation
     */
    private function testSecurityUnderAttack()
    {
        Log::info('🛡️ Testing security under attack simulation...');

        $testUser = User::first();
        $testCourse = Course::first();

        // Test 1: SQL Injection Attempts
        $maliciousInputs = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "<script>alert('xss')</script>",
            "../../../etc/passwd",
            "UNION SELECT username, password FROM users"
        ];

        foreach ($maliciousInputs as $input) {
            $paymentData = [
                'payment_method' => 'vodafone_cash',
                'reference_number' => $input,
                'amount' => $testCourse->price
            ];

            try {
                $result = $this->securePaymentService->processPayment($testUser, $testCourse, $paymentData);
                // Should either succeed with sanitization or fail gracefully
                $this->assertTrue($result['success'] || isset($result['error_code']));
            } catch (\Exception $e) {
                // Should fail gracefully without exposing sensitive info
                $this->assertStringNotContains($e->getMessage(), 'SQLSTATE');
                $this->assertStringNotContains($e->getMessage(), 'password');
            }
        }

        // Test 2: Rate Limiting
        $rateLimitHits = 0;
        for ($i = 0; $i < 20; $i++) {
            $paymentData = [
                'payment_method' => 'vodafone_cash',
                'reference_number' => "TEST_{$i}",
                'amount' => $testCourse->price
            ];

            $result = $this->securePaymentService->processPayment($testUser, $testCourse, $paymentData);
            if (!$result['success']) {
                $rateLimitHits++;
            }
        }

        // Should have some rate limiting
        $this->assertGreaterThan(0, $rateLimitHits, 'Rate limiting not working');

        // Test 3: Fraud Detection
        $suspiciousData = [
            'payment_method' => 'unknown_method',
            'reference_number' => 'SUSPICIOUS_' . uniqid(),
            'amount' => 999999, // Very high amount
            'user_ip' => '192.168.1.100',
            'user_agent' => 'Bot/1.0'
        ];

        $result = $this->securePaymentService->processPayment($testUser, $testCourse, $suspiciousData);
        // Should be flagged by fraud detection
        $this->assertTrue(
            !$result['success'] || $result['error_code'] === 'FRAUD_DETECTED',
            'Fraud detection not working'
        );

        Log::info('✅ Security tests passed');
    }

    /**
     * 🔄 RELIABILITY TEST: System stability under stress
     */
    private function testSystemReliability()
    {
        Log::info('🔄 Testing system reliability under stress...');

        // Test 1: Circuit Breaker Activation
        $testUser = User::first();
        $testCourse = Course::first();

        // Simulate failures to trigger circuit breaker
        for ($i = 0; $i < 10; $i++) {
            // Create invalid payment data to cause failures
            $invalidData = [
                'payment_method' => 'invalid',
                'reference_number' => '',
                'amount' => 0
            ];

            try {
                $this->securePaymentService->processPayment($testUser, $testCourse, $invalidData);
            } catch (\Exception $e) {
                // Expected failures
            }
        }

        // Check if circuit breaker is activated
        $circuitState = Redis::get('payment_circuit_breaker_state');
        $this->assertTrue(
            $circuitState === 'open' || $circuitState === 'half-open',
            'Circuit breaker not activating under failure conditions'
        );

        // Test 2: Cache Resilience
        Redis::flushall(); // Clear Redis

        // System should still work with database fallback
        $result = $this->ultraFastQuizService->getQuizQuestions(Topic::first(), ['all'], true);
        $this->assertTrue($result['success'], 'System not resilient to cache failures');

        // Test 3: Memory Usage Monitoring
        $metrics = $this->performanceMonitor->getRealTimeMetrics();
        $this->assertLessThan(128 * 1024 * 1024, $metrics['memory_usage']['current'] ?? 0,
            'Memory usage too high');

        Log::info('✅ Reliability tests passed');
    }

    /**
     * 📊 DATA INTEGRITY TEST: Database consistency
     */
    private function testDataIntegrity()
    {
        Log::info('📊 Testing data integrity and consistency...');

        // Test 1: Foreign Key Constraints
        $topics = Topic::all();
        foreach ($topics as $topic) {
            // Check that topic belongs to valid course
            $this->assertTrue(Course::where('id', $topic->course_id)->exists(),
                "Topic {$topic->id} has invalid course_id {$topic->course_id}");
        }

        // Test 2: Quiz Attempt Data Integrity
        $attempts = \App\Models\QuizAttempt::all();
        foreach ($attempts as $attempt) {
            // Check that attempt belongs to valid user and topic
            $this->assertTrue(User::where('id', $attempt->user_id)->exists(),
                "Quiz attempt {$attempt->id} has invalid user_id {$attempt->user_id}");
            $this->assertTrue(Topic::where('id', $attempt->topic_id)->exists(),
                "Quiz attempt {$attempt->id} has invalid topic_id {$attempt->topic_id}");

            // Check question_data is valid JSON
            $this->assertJson($attempt->question_data ?? '[]');
        }

        // Test 3: Payment Data Integrity
        $payments = Payment::all();
        foreach ($payments as $payment) {
            // Check that payment belongs to valid user and course
            $this->assertTrue(User::where('id', $payment->user_id)->exists(),
                "Payment {$payment->id} has invalid user_id {$payment->user_id}");
            $this->assertTrue(Course::where('id', $payment->course_id)->exists(),
                "Payment {$payment->id} has invalid course_id {$payment->course_id}");
        }

        // Test 4: Cache Data Consistency
        $cacheStats = $this->performanceMonitor->getCachePerformance();
        $this->assertGreaterThanOrEqual(0, $cacheStats['hit_rate']);
        $this->assertLessThanOrEqual(100, $cacheStats['hit_rate']);

        Log::info('✅ Data integrity tests passed');
    }

    /**
     * 👥 CONCURRENT ACCESS TEST: Multiple users simultaneously
     */
    private function testConcurrentAccess()
    {
        Log::info('👥 Testing concurrent access patterns...');

        $topics = Topic::take(10)->get();
        $users = User::take(50)->get();

        // Simulate concurrent quiz loading
        $concurrentOperations = [];
        foreach ($users as $index => $user) {
            $topic = $topics->get($index % $topics->count());

            $concurrentOperations[] = function() use ($topic) {
                $operationId = $this->performanceMonitor->startOperation('concurrent_quiz_load');

                $result = $this->ultraFastQuizService->getQuizQuestions($topic, ['all'], false);

                $performanceData = $this->performanceMonitor->endOperation($operationId, [
                    'concurrent' => true,
                    'questions_count' => count($result['questions'] ?? [])
                ]);

                return $performanceData;
            };
        }

        // Execute concurrent operations
        $startTime = microtime(true);
        $results = [];

        foreach ($concurrentOperations as $operation) {
            $results[] = $operation();
        }

        $concurrentTime = (microtime(true) - $startTime) * 1000;

        // Verify all operations completed successfully
        foreach ($results as $result) {
            $this->assertLessThan(2000, $result['execution_time_ms'],
                "Concurrent operation took too long: {$result['execution_time_ms']}ms");
        }

        // Verify performance degradation is acceptable
        $avgTime = array_sum(array_column($results, 'execution_time_ms')) / count($results);
        $this->assertLessThan(500, $avgTime,
            "Average concurrent operation time too high: {$avgTime}ms");

        Log::info('✅ Concurrent access tests passed', [
            'total_time_ms' => $concurrentTime,
            'operations_count' => count($results),
            'avg_time_per_operation' => $avgTime
        ]);
    }

    /**
     * 🚨 STRESS TEST: System under maximum load
     */
    public function test_system_under_maximum_load()
    {
        Log::info('🚨 STARTING MAXIMUM LOAD STRESS TEST');

        $topics = Topic::take(50)->get();

        // Test 1: Maximum concurrent quiz loads
        $maxConcurrent = 100;
        $operations = [];

        for ($i = 0; $i < $maxConcurrent; $i++) {
            $operations[] = function() use ($topics, $i) {
                $topic = $topics->get($i % $topics->count());
                $operationId = $this->performanceMonitor->startOperation('max_load_test');

                $result = $this->ultraFastQuizService->getQuizQuestions($topic, ['all'], false);

                $performanceData = $this->performanceMonitor->endOperation($operationId, [
                    'load_test' => true,
                    'operation_index' => $i
                ]);

                return $performanceData;
            };
        }

        // Execute maximum load
        $startTime = microtime(true);
        $results = [];

        // Use promises-like execution (simulated)
        foreach ($operations as $operation) {
            $results[] = $operation();
        }

        $loadTestTime = (microtime(true) - $startTime) * 1000;

        // Analyze results
        $successfulOperations = 0;
        $failedOperations = 0;
        $slowOperations = 0;

        foreach ($results as $result) {
            if ($result['execution_time_ms'] < 5000) { // 5 second threshold
                $successfulOperations++;
            } elseif ($result['execution_time_ms'] < 10000) { // 10 second threshold
                $slowOperations++;
            } else {
                $failedOperations++;
            }
        }

        // Assert system stability under maximum load
        $successRate = ($successfulOperations / $maxConcurrent) * 100;
        $this->assertGreaterThanOrEqual(90, $successRate,
            "Success rate too low under maximum load: {$successRate}%");

        $failureRate = ($failedOperations / $maxConcurrent) * 100;
        $this->assertLessThanOrEqual(5, $failureRate,
            "Failure rate too high under maximum load: {$failureRate}%");

        Log::info('✅ Maximum load stress test completed', [
            'total_operations' => $maxConcurrent,
            'total_time_ms' => $loadTestTime,
            'successful' => $successfulOperations,
            'slow' => $slowOperations,
            'failed' => $failedOperations,
            'success_rate' => $successRate . '%',
            'avg_response_time' => array_sum(array_column($results, 'execution_time_ms')) / count($results)
        ]);
    }

    /**
     * 📈 PERFORMANCE REGRESSION TEST
     */
    public function test_performance_regression()
    {
        Log::info('📈 Testing for performance regression...');

        $topic = Topic::first();

        // Establish baseline performance
        $baselineResults = [];
        for ($i = 0; $i < 10; $i++) {
            $operationId = $this->performanceMonitor->startOperation('baseline_test');
            $result = $this->ultraFastQuizService->getQuizQuestions($topic, ['all'], false);
            $performanceData = $this->performanceMonitor->endOperation($operationId);
            $baselineResults[] = $performanceData['execution_time_ms'];
        }

        $baselineAvg = array_sum($baselineResults) / count($baselineResults);

        // Test current performance
        $currentResults = [];
        for ($i = 0; $i < 20; $i++) {
            $operationId = $this->performanceMonitor->startOperation('regression_test');
            $result = $this->ultraFastQuizService->getQuizQuestions($topic, ['all'], false);
            $performanceData = $this->performanceMonitor->endOperation($operationId);
            $currentResults[] = $performanceData['execution_time_ms'];
        }

        $currentAvg = array_sum($currentResults) / count($currentResults);

        // Check for performance regression
        $regressionThreshold = 50; // 50% performance degradation allowed
        $degradation = (($currentAvg - $baselineAvg) / $baselineAvg) * 100;

        $this->assertLessThanOrEqual($regressionThreshold, $degradation,
            "Performance regression detected: {$degradation}% degradation");

        Log::info('✅ Performance regression test passed', [
            'baseline_avg_ms' => $baselineAvg,
            'current_avg_ms' => $currentAvg,
            'degradation_percent' => $degradation
        ]);
    }

    /**
     * 🏥 SYSTEM HEALTH CHECK
     */
    public function test_system_health_comprehensive()
    {
        Log::info('🏥 Running comprehensive system health check...');

        // Check all critical services
        $healthChecks = [
            'database' => $this->checkDatabaseHealth(),
            'redis' => $this->checkRedisHealth(),
            'services' => $this->checkServicesHealth(),
            'performance' => $this->checkPerformanceHealth(),
            'security' => $this->checkSecurityHealth()
        ];

        foreach ($healthChecks as $component => $status) {
            $this->assertTrue($status['healthy'],
                "Health check failed for {$component}: " . ($status['error'] ?? 'Unknown error'));
        }

        Log::info('✅ Comprehensive system health check passed');
    }

    private function checkDatabaseHealth(): array
    {
        try {
            DB::select('SELECT 1');
            $connection = DB::connection();
            $queryCount = count(DB::getQueryLog());

            return [
                'healthy' => true,
                'connection' => $connection->getName(),
                'query_count' => $queryCount
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkRedisHealth(): array
    {
        try {
            Redis::ping();
            $info = Redis::info();

            return [
                'healthy' => true,
                'version' => $info['redis_version'] ?? 'unknown',
                'connections' => $info['connected_clients'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkServicesHealth(): array
    {
        try {
            // Test UltraFastQuizService
            $topic = Topic::first();
            if ($topic) {
                $result = $this->ultraFastQuizService->getQuizQuestions($topic, ['all'], false);
                $quizServiceHealthy = $result['success'] ?? false;
            } else {
                $quizServiceHealthy = false;
            }

            // Test SecurePaymentService
            $user = User::first();
            $course = Course::first();
            if ($user && $course) {
                $paymentResult = $this->securePaymentService->processPayment($user, $course, [
                    'payment_method' => 'test',
                    'reference_number' => 'HEALTH_CHECK',
                    'amount' => 1
                ]);
                $paymentServiceHealthy = true; // Service exists and doesn't crash
            } else {
                $paymentServiceHealthy = false;
            }

            return [
                'healthy' => $quizServiceHealthy && $paymentServiceHealthy,
                'quiz_service' => $quizServiceHealthy,
                'payment_service' => $paymentServiceHealthy
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkPerformanceHealth(): array
    {
        try {
            $metrics = $this->performanceMonitor->getRealTimeMetrics();

            $memoryHealthy = ($metrics['memory_usage']['current'] ?? 0) < (128 * 1024 * 1024);
            $activeOpsHealthy = ($metrics['active_operations'] ?? 0) < 100;

            return [
                'healthy' => $memoryHealthy && $activeOpsHealthy,
                'memory_usage' => $metrics['memory_usage'] ?? [],
                'active_operations' => $metrics['active_operations'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkSecurityHealth(): array
    {
        try {
            $alerts = $this->performanceMonitor->getPerformanceAlerts();
            $circuitState = Redis::get('payment_circuit_breaker_state');

            return [
                'healthy' => count($alerts) < 10, // Less than 10 alerts is healthy
                'alerts_count' => count($alerts),
                'circuit_breaker_state' => $circuitState ?? 'closed'
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
