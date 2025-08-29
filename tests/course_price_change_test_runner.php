<?php

/**
 * COURSE PRICE CHANGE COMPREHENSIVE TEST RUNNER
 *
 * This script tests all course price change scenarios without requiring PHPUnit setup.
 * Run with: php tests/course_price_change_test_runner.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

class CoursePriceChangeTestRunner
{
    private $app;
    private $testUser;
    private $testCourse;
    private $adminUser;
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;

    public function __construct()
    {
        $this->app = app();
    }

    public function run()
    {
        echo "🚀 COURSE PRICE CHANGE COMPREHENSIVE TEST SUITE\n";
        echo "===============================================\n";
        echo "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        echo "Environment: " . app()->environment() . "\n";
        echo "\n";

        try {
            $this->setup();
            $this->runAllTests();
            $this->report();
        } catch (Exception $e) {
            echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }

    private function setup()
    {
        echo "🔧 Setting up test environment...\n";

        // Create test user (student)
        $this->testUser = User::create([
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 3, // Student
        ]);

        // Create test admin
        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 1, // Admin
        ]);

        // Create test course
        $this->testCourse = Course::create([
            'name' => 'Test Course',
            'description' => 'Test course for price change testing',
            'type' => 'medical',
            'price' => 0, // Start as free
            'color' => '#3b82f6',
            'title_color' => '#FFFFFF',
            'academic_year' => '2025'
        ]);

        echo "✅ Test environment setup complete\n";
        echo "   Test Course: {$this->testCourse->name} (ID: {$this->testCourse->id})\n";
        echo "   Test User: {$this->testUser->name} (ID: {$this->testUser->id})\n";
        echo "   Test Admin: {$this->adminUser->name} (ID: {$this->adminUser->id})\n";
        echo "\n";
    }

    private function runAllTests()
    {
        $this->testFreeCourseAccess();
        $this->testFreeToPaidScenario();
        $this->testPaidToFreeScenario();
        $this->testPaidToPaidHigherScenario();
        $this->testPaidToPaidLowerScenario();
        $this->testCacheIntegrity();
        $this->testPerformance();
        $this->testMultipleUsersScenario();
        $this->testAdminAccess();
        $this->testEdgeCases();
    }

    private function testFreeCourseAccess()
    {
        echo "🧪 SCENARIO 1: FREE COURSE ACCESS\n";
        echo "=================================\n";
        $this->totalTests++;

        // Ensure course is free
        $this->testCourse->price = 0;
        $this->testCourse->save();

        // Test 1: Student access to free course
        $hasAccess = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($hasAccess, 'Student should have access to free course');
        echo "✅ Student can access free course\n";

        // Test 2: Admin access to free course
        $adminHasAccess = $this->adminUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($adminHasAccess, 'Admin should have access to free course');
        echo "✅ Admin can access free course\n";

        // Test 3: Auto-enrollment in free courses
        $this->testUser->enrollInAllFreeCourses();
        $hasAccessAfterAutoEnroll = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($hasAccessAfterAutoEnroll, 'User should be auto-enrolled in free courses');
        echo "✅ Auto-enrollment works for free courses\n";

        echo "✅ SCENARIO 1 PASSED: Free courses work correctly\n\n";
        $this->testResults['scenario1'] = 'PASSED';
        $this->passedTests++;
    }

    private function testFreeToPaidScenario()
    {
        echo "🧪 SCENARIO 2: FREE TO PAID TRANSITION\n";
        echo "=====================================\n";
        $this->totalTests++;

        // Start with free course
        $this->testCourse->price = 0;
        $this->testCourse->save();

        // Enroll user in free course
        $this->testUser->enrollInCourse($this->testCourse->id);
        $initialAccess = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($initialAccess, 'User should be enrolled in free course initially');
        echo "✅ User enrolled in free course\n";

        // Change to paid course
        $this->testCourse->price = 100;
        $this->testCourse->save();

        // Check if enrollment was revoked
        $accessAfterPriceChange = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertFalse($accessAfterPriceChange, 'User enrollment should be revoked when course becomes paid');
        echo "✅ User enrollment revoked when course became paid\n";

        // Verify database state
        $enrollmentRecord = DB::table('course_user')
            ->where('user_id', $this->testUser->id)
            ->where('course_id', $this->testCourse->id)
            ->first();

        if ($enrollmentRecord) {
            $this->assertNull($enrollmentRecord->enrolled_at, 'enrolled_at should be NULL in database');
            echo "✅ Database enrollment properly revoked\n";
        }

        echo "✅ SCENARIO 2 PASSED: Free to paid transition works correctly\n\n";
        $this->testResults['scenario2'] = 'PASSED';
        $this->passedTests++;
    }

    private function testPaidToFreeScenario()
    {
        echo "🧪 SCENARIO 3: PAID TO FREE TRANSITION\n";
        echo "=====================================\n";
        $this->totalTests++;

        // Start with paid course
        $this->testCourse->price = 100;
        $this->testCourse->save();

        // Ensure no initial enrollment
        $this->testUser->unenrollFromCourse($this->testCourse->id);
        $initialAccess = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertFalse($initialAccess, 'User should not be enrolled in paid course initially');
        echo "✅ User not enrolled in paid course initially\n";

        // Change to free course
        $this->testCourse->price = 0;
        $this->testCourse->save();

        // Check if user was auto-enrolled
        $accessAfterPriceChange = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($accessAfterPriceChange, 'User should be auto-enrolled when course becomes free');
        echo "✅ User auto-enrolled when course became free\n";

        // Verify database state
        $enrollmentRecord = DB::table('course_user')
            ->where('user_id', $this->testUser->id)
            ->where('course_id', $this->testCourse->id)
            ->first();

        $this->assertNotNull($enrollmentRecord, 'Enrollment record should exist');
        $this->assertNotNull($enrollmentRecord->enrolled_at, 'enrolled_at should not be NULL in database');
        echo "✅ Database auto-enrollment properly created\n";

        echo "✅ SCENARIO 3 PASSED: Paid to free transition works correctly\n\n";
        $this->testResults['scenario3'] = 'PASSED';
        $this->passedTests++;
    }

    private function testPaidToPaidHigherScenario()
    {
        echo "🧪 SCENARIO 4: PAID TO PAID (HIGHER PRICE)\n";
        echo "============================================\n";
        $this->totalTests++;

        // Start with paid course at lower price
        $this->testCourse->price = 50;
        $this->testCourse->save();

        // Manually enroll user (simulating previous purchase)
        $this->testUser->enrollInCourse($this->testCourse->id);
        $initialAccess = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($initialAccess, 'User should be enrolled in paid course initially');
        echo "✅ User enrolled in paid course initially\n";

        // Change to higher paid price
        $this->testCourse->price = 200;
        $this->testCourse->save();

        // Check if enrollment was preserved
        $accessAfterPriceChange = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($accessAfterPriceChange, 'User enrollment should be preserved when price increases');
        echo "✅ User enrollment preserved when price increased\n";

        // Verify database state
        $enrollmentRecord = DB::table('course_user')
            ->where('user_id', $this->testUser->id)
            ->where('course_id', $this->testCourse->id)
            ->first();

        $this->assertNotNull($enrollmentRecord, 'Enrollment record should still exist');
        $this->assertNotNull($enrollmentRecord->enrolled_at, 'enrolled_at should still be set');
        echo "✅ Database enrollment properly preserved\n";

        echo "✅ SCENARIO 4 PASSED: Paid to paid (higher) transition works correctly\n\n";
        $this->testResults['scenario4'] = 'PASSED';
        $this->passedTests++;
    }

    private function testPaidToPaidLowerScenario()
    {
        echo "🧪 SCENARIO 5: PAID TO PAID (LOWER PRICE)\n";
        echo "===========================================\n";
        $this->totalTests++;

        // Start with paid course at higher price
        $this->testCourse->price = 300;
        $this->testCourse->save();

        // Manually enroll user
        $this->testUser->enrollInCourse($this->testCourse->id);
        $initialAccess = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($initialAccess, 'User should be enrolled in paid course initially');
        echo "✅ User enrolled in paid course initially\n";

        // Change to lower paid price
        $this->testCourse->price = 100;
        $this->testCourse->save();

        // Check if enrollment was preserved
        $accessAfterPriceChange = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($accessAfterPriceChange, 'User enrollment should be preserved when price decreases');
        echo "✅ User enrollment preserved when price decreased\n";

        echo "✅ SCENARIO 5 PASSED: Paid to paid (lower) transition works correctly\n\n";
        $this->testResults['scenario5'] = 'PASSED';
        $this->passedTests++;
    }

    private function testCacheIntegrity()
    {
        echo "🧪 SCENARIO 6: CACHE INTEGRITY TEST\n";
        echo "===================================\n";
        $this->totalTests++;

        // Clear all caches first
        Cache::flush();

        // Test 1: Free course access (should be cached)
        $this->testCourse->price = 0;
        $this->testCourse->save();

        $access1 = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $access2 = $this->testUser->isEnrolledInCourse($this->testCourse->id);

        $this->assertEquals($access1, $access2, 'Cache should return consistent results');
        echo "✅ Cache returns consistent results\n";

        // Test 2: Price change should clear cache
        $this->testCourse->price = 100;
        $this->testCourse->save();

        $accessAfterPriceChange = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertFalse($accessAfterPriceChange, 'Cache should be cleared after price change');
        echo "✅ Cache properly cleared after price change\n";

        echo "✅ SCENARIO 6 PASSED: Cache integrity maintained\n\n";
        $this->testResults['scenario6'] = 'PASSED';
        $this->passedTests++;
    }

    private function testPerformance()
    {
        echo "🧪 SCENARIO 7: PERFORMANCE TEST\n";
        echo "===============================\n";
        $this->totalTests++;

        $startTime = microtime(true);

        // Perform 1000 access checks
        for ($i = 0; $i < 1000; $i++) {
            $this->testUser->isEnrolledInCourse($this->testCourse->id);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        echo "1000 access checks took: " . number_format($executionTime, 2) . "ms\n";
        echo "Average per check: " . number_format($executionTime / 1000, 2) . "ms\n";

        // Performance should be under 1ms per check
        $this->assertTrue($executionTime < 1000, 'Performance should be under 1 second for 1000 checks');
        echo "✅ Performance requirements met\n";

        echo "✅ SCENARIO 7 PASSED: Performance is acceptable\n\n";
        $this->testResults['scenario7'] = 'PASSED';
        $this->passedTests++;
    }

    private function testMultipleUsersScenario()
    {
        echo "🧪 SCENARIO 8: MULTIPLE USERS TEST\n";
        echo "==================================\n";
        $this->totalTests++;

        // Create multiple test users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create([
                'name' => "Test Student {$i}",
                'email' => "test{$i}@example.com",
                'password' => bcrypt('password'),
                'role' => 3, // Student
            ]);
        }

        // Test 1: Free course - all users should have access
        $this->testCourse->price = 0;
        $this->testCourse->save();

        foreach ($users as $index => $user) {
            $hasAccess = $user->isEnrolledInCourse($this->testCourse->id);
            $this->assertTrue($hasAccess, "User {$index} should have access to free course");
        }
        echo "✅ All users can access free course\n";

        // Test 2: Change to paid - all users should lose access
        $this->testCourse->price = 150;
        $this->testCourse->save();

        foreach ($users as $index => $user) {
            $hasAccess = $user->isEnrolledInCourse($this->testCourse->id);
            $this->assertFalse($hasAccess, "User {$index} should lose access when course becomes paid");
        }
        echo "✅ All users lose access when course becomes paid\n";

        // Test 3: Change back to free - all users should regain access
        $this->testCourse->price = 0;
        $this->testCourse->save();

        foreach ($users as $index => $user) {
            $hasAccess = $user->isEnrolledInCourse($this->testCourse->id);
            $this->assertTrue($hasAccess, "User {$index} should regain access when course becomes free again");
        }
        echo "✅ All users regain access when course becomes free again\n";

        echo "✅ SCENARIO 8 PASSED: Multiple users handled correctly\n\n";
        $this->testResults['scenario8'] = 'PASSED';
        $this->passedTests++;
    }

    private function testAdminAccess()
    {
        echo "🧪 SCENARIO 9: ADMIN ACCESS TEST\n";
        echo "===============================\n";
        $this->totalTests++;

        // Test 1: Admin access to free course
        $this->testCourse->price = 0;
        $this->testCourse->save();

        $adminAccess = $this->adminUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($adminAccess, 'Admin should always have access to free course');
        echo "✅ Admin can access free course\n";

        // Test 2: Admin access to paid course
        $this->testCourse->price = 500;
        $this->testCourse->save();

        $adminAccess = $this->adminUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($adminAccess, 'Admin should always have access to paid course');
        echo "✅ Admin can access paid course\n";

        // Test 3: Admin access even without enrollment
        $this->testUser->unenrollFromCourse($this->testCourse->id);
        $studentAccess = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertFalse($studentAccess, 'Student should not have access to paid course without enrollment');

        $adminAccess = $this->adminUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertTrue($adminAccess, 'Admin should have access regardless of enrollment');
        echo "✅ Admin access independent of enrollment\n";

        echo "✅ SCENARIO 9 PASSED: Admin access works correctly\n\n";
        $this->testResults['scenario9'] = 'PASSED';
        $this->passedTests++;
    }

    private function testEdgeCases()
    {
        echo "🧪 SCENARIO 10: EDGE CASES\n";
        echo "=========================\n";
        $this->totalTests++;

        // Test 1: Price change from 0 to 0 (should do nothing)
        $this->testCourse->price = 0;
        $this->testCourse->save();
        $this->testUser->enrollInCourse($this->testCourse->id);

        $accessBefore = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->testCourse->price = 0; // Same price
        $this->testCourse->save();
        $accessAfter = $this->testUser->isEnrolledInCourse($this->testCourse->id);

        $this->assertEquals($accessBefore, $accessAfter, 'Price change from 0 to 0 should do nothing');
        echo "✅ Price change from 0 to 0 handled correctly\n";

        // Test 2: Price change from 100 to 100 (should do nothing)
        $this->testCourse->price = 100;
        $this->testCourse->save();

        $accessBefore = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->testCourse->price = 100; // Same price
        $this->testCourse->save();
        $accessAfter = $this->testUser->isEnrolledInCourse($this->testCourse->id);

        $this->assertEquals($accessBefore, $accessAfter, 'Price change from 100 to 100 should do nothing');
        echo "✅ Price change from 100 to 100 handled correctly\n";

        // Test 3: Very small price changes
        $this->testCourse->price = 0.01;
        $this->testCourse->save();

        $accessAfter = $this->testUser->isEnrolledInCourse($this->testCourse->id);
        $this->assertFalse($accessAfter, 'Very small price should still revoke access');
        echo "✅ Very small price changes handled correctly\n";

        echo "✅ SCENARIO 10 PASSED: Edge cases handled correctly\n\n";
        $this->testResults['scenario10'] = 'PASSED';
        $this->passedTests++;
    }

    private function report()
    {
        echo "\n🎯 COMPREHENSIVE TEST REPORT\n";
        echo "===========================\n";

        echo "Total Scenarios Tested: {$this->totalTests}\n";
        echo "Passed Scenarios: {$this->passedTests}\n";
        echo "Failed Scenarios: " . ($this->totalTests - $this->passedTests) . "\n";
        echo "Success Rate: " . number_format(($this->passedTests / $this->totalTests) * 100, 1) . "%\n";

        echo "\n📊 SCENARIO BREAKDOWN:\n";
        for ($i = 1; $i <= $this->totalTests; $i++) {
            $status = $this->testResults["scenario{$i}"] ?? 'FAILED';
            $statusIcon = $status === 'PASSED' ? '✅' : '❌';
            $scenarioName = $this->getScenarioName($i);
            echo "{$statusIcon} Scenario {$i}: {$scenarioName} - {$status}\n";
        }

        echo "\n🎉 COURSE PRICE CHANGE FUNCTIONALITY TEST COMPLETED!\n";

        if ($this->passedTests === $this->totalTests) {
            echo "🎊 ALL TESTS PASSED - System is working perfectly!\n";
        } else {
            echo "⚠️ Some tests failed - Check the implementation\n";
        }
    }

    private function getScenarioName($scenarioNumber)
    {
        $names = [
            1 => 'Free Course Access',
            2 => 'Free to Paid Transition',
            3 => 'Paid to Free Transition',
            4 => 'Paid to Paid (Higher Price)',
            5 => 'Paid to Paid (Lower Price)',
            6 => 'Cache Integrity',
            7 => 'Performance Testing',
            8 => 'Multiple Users Handling',
            9 => 'Admin Access Testing',
            10 => 'Edge Cases'
        ];

        return $names[$scenarioNumber] ?? 'Unknown Scenario';
    }

    private function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new Exception("Assertion failed: {$message}");
        }
    }

    private function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new Exception("Assertion failed: {$message}");
        }
    }

    private function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: {$message}. Expected: {$expected}, Actual: {$actual}");
        }
    }

    private function assertNull($value, $message = '')
    {
        if ($value !== null) {
            throw new Exception("Assertion failed: {$message}. Expected NULL, got: {$value}");
        }
    }

    private function assertNotNull($value, $message = '')
    {
        if ($value === null) {
            throw new Exception("Assertion failed: {$message}. Expected not NULL, got NULL");
        }
    }
}

// Run the tests
$testRunner = new CoursePriceChangeTestRunner();
$testRunner->run();
