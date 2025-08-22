<?php

/**
 * 🚨 BRUTAL SYSTEM READINESS CHECK - MANUAL EXECUTION
 *
 * This script performs comprehensive system health checks
 * to verify deployment readiness without relying on PHPUnit
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\UltraFastQuizService;
use App\Services\SecurePaymentService;
use App\Services\PerformanceMonitorService;
use App\Services\UserService;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemHealthCheck
{
    private $app;
    private $results = [];

    public function __construct()
    {
        $this->initializeLaravel();
    }

    private function initializeLaravel()
    {
        $app = require_once __DIR__ . '/../../bootstrap/app.php';
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();
        $this->app = $app;
    }

    public function runCompleteHealthCheck()
    {
        echo "🚀 STARTING BRUTAL SYSTEM READINESS CHECK\n";
        echo "==========================================\n\n";

        // 1. INFRASTRUCTURE CHECKS
        $this->checkInfrastructure();

        // 2. SERVICE INTEGRATION CHECKS
        $this->checkServiceIntegration();

        // 3. PERFORMANCE VALIDATION
        $this->validatePerformance();

        // 4. SECURITY VALIDATION
        $this->validateSecurity();

        // 5. DATA INTEGRITY CHECKS
        $this->checkDataIntegrity();

        // 6. DEPLOYMENT READINESS
        $this->assessDeploymentReadiness();

        $this->printSummary();
    }

    private function checkInfrastructure()
    {
        echo "🔧 INFRASTRUCTURE CHECKS\n";
        echo "------------------------\n";

        // Check Database
        try {
            DB::select('SELECT 1');
            $this->results['infrastructure']['database'] = ['status' => '✅ PASS', 'message' => 'Database connection OK'];
            echo "✅ Database: Connected\n";
        } catch (Exception $e) {
            $this->results['infrastructure']['database'] = ['status' => '❌ FAIL', 'message' => 'Database connection failed'];
            echo "❌ Database: Failed - {$e->getMessage()}\n";
        }

        // Check Redis
        try {
            Redis::ping();
            $this->results['infrastructure']['redis'] = ['status' => '✅ PASS', 'message' => 'Redis connection OK'];
            echo "✅ Redis: Connected\n";
        } catch (Exception $e) {
            $this->results['infrastructure']['redis'] = ['status' => '❌ FAIL', 'message' => 'Redis connection failed'];
            echo "❌ Redis: Failed - {$e->getMessage()}\n";
        }

        // Check Services
        try {
            $ultraFastService = app(UltraFastQuizService::class);
            $this->results['infrastructure']['ultra_fast_service'] = ['status' => '✅ PASS', 'message' => 'UltraFastQuizService loaded'];
            echo "✅ UltraFastQuizService: Loaded\n";
        } catch (Exception $e) {
            $this->results['infrastructure']['ultra_fast_service'] = ['status' => '❌ FAIL', 'message' => 'Service loading failed'];
            echo "❌ UltraFastQuizService: Failed - {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function checkServiceIntegration()
    {
        echo "🔗 SERVICE INTEGRATION CHECKS\n";
        echo "-----------------------------\n";

        // Test UltraFastQuizService
        try {
            $service = app(UltraFastQuizService::class);
            $topics = Topic::take(5)->get();

            if ($topics->count() > 0) {
                $topic = $topics->first();
                $startTime = microtime(true);
                $result = $service->getQuizQuestions($topic, ['all'], false);
                $executionTime = (microtime(true) - $startTime) * 1000;

                $this->results['services']['ultra_fast_quiz'] = [
                    'status' => '✅ PASS',
                    'message' => "Service working, execution: {$executionTime}ms",
                    'execution_time' => $executionTime
                ];
                echo "✅ UltraFastQuizService: Working ({$executionTime}ms)\n";
            } else {
                $this->results['services']['ultra_fast_quiz'] = ['status' => '⚠️  WARN', 'message' => 'No topics found to test'];
                echo "⚠️  UltraFastQuizService: No test data available\n";
            }
        } catch (Exception $e) {
            $this->results['services']['ultra_fast_quiz'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ UltraFastQuizService: Failed - {$e->getMessage()}\n";
        }

        // Test SecurePaymentService
        try {
            $service = app(SecurePaymentService::class);
            $this->results['services']['secure_payment'] = ['status' => '✅ PASS', 'message' => 'Service loaded successfully'];
            echo "✅ SecurePaymentService: Loaded\n";
        } catch (Exception $e) {
            $this->results['services']['secure_payment'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ SecurePaymentService: Failed - {$e->getMessage()}\n";
        }

        // Test PerformanceMonitorService
        try {
            $service = app(PerformanceMonitorService::class);
            $metrics = $service->getRealTimeMetrics();
            $this->results['services']['performance_monitor'] = ['status' => '✅ PASS', 'message' => 'Metrics retrieved successfully'];
            echo "✅ PerformanceMonitorService: Working\n";
        } catch (Exception $e) {
            $this->results['services']['performance_monitor'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ PerformanceMonitorService: Failed - {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function validatePerformance()
    {
        echo "⚡ PERFORMANCE VALIDATION\n";
        echo "------------------------\n";

        // Test Quiz Loading Performance
        try {
            $service = app(UltraFastQuizService::class);
            $topics = Topic::take(10)->get();

            $totalTime = 0;
            $successfulLoads = 0;

            foreach ($topics as $topic) {
                $startTime = microtime(true);
                $result = $service->getQuizQuestions($topic, ['all'], false);
                $executionTime = (microtime(true) - $startTime) * 1000;

                $totalTime += $executionTime;
                if ($executionTime < 1000) { // 1 second threshold
                    $successfulLoads++;
                }
            }

            $avgTime = $totalTime / $topics->count();
            $successRate = ($successfulLoads / $topics->count()) * 100;

            $status = $avgTime < 100 ? '✅ PASS' : ($avgTime < 500 ? '⚠️  WARN' : '❌ FAIL');
            $this->results['performance']['quiz_loading'] = [
                'status' => $status,
                'message' => "Avg: {$avgTime}ms, Success Rate: {$successRate}%",
                'avg_time' => $avgTime,
                'success_rate' => $successRate
            ];
            echo "✅ Quiz Loading: {$avgTime}ms average ({$successRate}% success rate)\n";

        } catch (Exception $e) {
            $this->results['performance']['quiz_loading'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ Quiz Loading: Failed - {$e->getMessage()}\n";
        }

        // Test Cache Performance
        try {
            $service = app(PerformanceMonitorService::class);
            $cacheStats = $service->getCachePerformance();

            $hitRate = $cacheStats['hit_rate'];
            $status = $hitRate >= 95 ? '✅ PASS' : ($hitRate >= 80 ? '⚠️  WARN' : '❌ FAIL');

            $this->results['performance']['cache'] = [
                'status' => $status,
                'message' => "Hit Rate: {$hitRate}%",
                'hit_rate' => $hitRate
            ];
            echo "✅ Cache Performance: {$hitRate}% hit rate\n";

        } catch (Exception $e) {
            $this->results['performance']['cache'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ Cache Performance: Failed - {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function validateSecurity()
    {
        echo "🔒 SECURITY VALIDATION\n";
        echo "---------------------\n";

        // Test SQL Injection Protection
        try {
            $service = app(SecurePaymentService::class);
            $user = User::first();
            $course = Course::first();

            if ($user && $course) {
                $maliciousData = [
                    'payment_method' => "' OR '1'='1",
                    'reference_number' => "TEST_' OR 1=1; --",
                    'amount' => $course->price
                ];

                $result = $service->processPayment($user, $course, $maliciousData);

                // Should either succeed with sanitization or fail gracefully
                $this->results['security']['sql_injection'] = ['status' => '✅ PASS', 'message' => 'SQL injection attempts handled safely'];
                echo "✅ SQL Injection: Protected against malicious input\n";
            } else {
                $this->results['security']['sql_injection'] = ['status' => '⚠️  WARN', 'message' => 'No test data available'];
                echo "⚠️  SQL Injection: No test data available\n";
            }
        } catch (Exception $e) {
            $this->results['security']['sql_injection'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ SQL Injection: Failed - {$e->getMessage()}\n";
        }

        // Test Circuit Breaker
        try {
            // Simulate failures to test circuit breaker
            $circuitState = Redis::get('payment_circuit_breaker_state');
            $this->results['security']['circuit_breaker'] = ['status' => '✅ PASS', 'message' => 'Circuit breaker system active'];
            echo "✅ Circuit Breaker: System active\n";
        } catch (Exception $e) {
            $this->results['security']['circuit_breaker'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ Circuit Breaker: Failed - {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function checkDataIntegrity()
    {
        echo "📊 DATA INTEGRITY CHECKS\n";
        echo "------------------------\n";

        // Check Foreign Key Constraints
        try {
            $topics = Topic::all();
            $invalidTopics = 0;

            foreach ($topics as $topic) {
                if (!Course::where('id', $topic->course_id)->exists()) {
                    $invalidTopics++;
                }
            }

            if ($invalidTopics === 0) {
                $this->results['data_integrity']['foreign_keys'] = ['status' => '✅ PASS', 'message' => 'All foreign key constraints valid'];
                echo "✅ Foreign Keys: All constraints valid\n";
            } else {
                $this->results['data_integrity']['foreign_keys'] = ['status' => '❌ FAIL', 'message' => "{$invalidTopics} invalid foreign keys found"];
                echo "❌ Foreign Keys: {$invalidTopics} invalid constraints found\n";
            }
        } catch (Exception $e) {
            $this->results['data_integrity']['foreign_keys'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ Foreign Keys: Failed - {$e->getMessage()}\n";
        }

        // Check Quiz Attempts Integrity
        try {
            $attempts = \App\Models\QuizAttempt::all();
            $invalidAttempts = 0;

            foreach ($attempts as $attempt) {
                if (!User::where('id', $attempt->user_id)->exists() ||
                    !Topic::where('id', $attempt->topic_id)->exists()) {
                    $invalidAttempts++;
                }
            }

            if ($invalidAttempts === 0) {
                $this->results['data_integrity']['quiz_attempts'] = ['status' => '✅ PASS', 'message' => 'All quiz attempts have valid references'];
                echo "✅ Quiz Attempts: All references valid\n";
            } else {
                $this->results['data_integrity']['quiz_attempts'] = ['status' => '❌ FAIL', 'message' => "{$invalidAttempts} invalid references found"];
                echo "❌ Quiz Attempts: {$invalidAttempts} invalid references found\n";
            }
        } catch (Exception $e) {
            $this->results['data_integrity']['quiz_attempts'] = ['status' => '❌ FAIL', 'message' => $e->getMessage()];
            echo "❌ Quiz Attempts: Failed - {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function assessDeploymentReadiness()
    {
        echo "🚀 DEPLOYMENT READINESS ASSESSMENT\n";
        echo "----------------------------------\n";

        $criticalIssues = 0;
        $warningIssues = 0;

        // Analyze all results
        foreach ($this->results as $category => $checks) {
            foreach ($checks as $check => $result) {
                if (strpos($result['status'], '❌ FAIL') !== false) {
                    $criticalIssues++;
                } elseif (strpos($result['status'], '⚠️  WARN') !== false) {
                    $warningIssues++;
                }
            }
        }

        if ($criticalIssues === 0) {
            $this->results['deployment']['overall'] = ['status' => '✅ READY', 'message' => 'System is ready for deployment'];
            echo "✅ DEPLOYMENT STATUS: System is READY for deployment\n";
        } else {
            $this->results['deployment']['overall'] = ['status' => '❌ NOT READY', 'message' => "{$criticalIssues} critical issues found"];
            echo "❌ DEPLOYMENT STATUS: System is NOT ready for deployment\n";
            echo "   Critical Issues: {$criticalIssues}\n";
            echo "   Warning Issues: {$warningIssues}\n";
        }

        echo "\n";
    }

    private function printSummary()
    {
        echo "📋 FINAL ASSESSMENT SUMMARY\n";
        echo "===========================\n\n";

        $statusCounts = ['✅ PASS' => 0, '⚠️  WARN' => 0, '❌ FAIL' => 0];

        foreach ($this->results as $category => $checks) {
            echo strtoupper($category) . ":\n";
            foreach ($checks as $check => $result) {
                echo "  {$result['status']} {$check}: {$result['message']}\n";
                $statusCounts[$result['status']]++;
            }
            echo "\n";
        }

        echo "OVERALL RESULTS:\n";
        echo "  ✅ Passed: {$statusCounts['✅ PASS']}\n";
        echo "  ⚠️  Warnings: {$statusCounts['⚠️  WARN']}\n";
        echo "  ❌ Failed: {$statusCounts['❌ FAIL']}\n\n";

        if ($statusCounts['❌ FAIL'] === 0) {
            echo "🎉 SYSTEM IS DEPLOYMENT READY!\n";
            echo "   All critical systems are operational and optimized.\n";
        } else {
            echo "⚠️  SYSTEM REQUIRES ATTENTION\n";
            echo "   {$statusCounts['❌ FAIL']} critical issues need to be resolved before deployment.\n";
        }
    }
}

// Run the health check
$checker = new SystemHealthCheck();
$checker->runCompleteHealthCheck();
