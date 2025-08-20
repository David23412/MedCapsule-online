<?php

namespace Tests\Feature;

/**
 * 🚀 COMPREHENSIVE ASSIGNMENT E2E TEST
 * 
 * This script will:
 * 1. Create test assignments
 * 2. Test all assignment functionality including deletion
 * 3. Simulate HTTP requests to test the actual API
 * 4. Test pivot table functionality
 * 5. Test deadline scenarios
 * 6. Generate comprehensive report
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\QuizAttempt;
use Carbon\Carbon;

class ComprehensiveAssignmentE2ETest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $testResults = [];
    private $testUsers = [];
    private $testCourses = [];
    private $testTopics = [];
    private $testAssignments = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    /**
     * 🔥 MAIN E2E TEST RUNNER
     */
    public function test_complete_assignment_e2e_functionality()
    {
        echo "\n🚀 STARTING COMPREHENSIVE ASSIGNMENT E2E TEST\n";
        echo "=" . str_repeat("=", 60) . "\n";

        $startTime = microtime(true);

        // Phase 1: Test Assignment Creation
        $this->test_assignment_creation();

        // Phase 2: Test Assignment Deletion (HTTP)
        $this->test_assignment_deletion_http();

        // Phase 3: Test Deadline Scenarios
        $this->test_deadline_scenarios();

        // Phase 4: Test Grading and Locking
        $this->test_grading_and_locking();

        // Phase 5: Test Pivot Table Functionality
        $this->test_pivot_table_functionality();

        // Phase 6: Test Edge Cases
        $this->test_edge_cases();

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->generateFinalReport($executionTime);
    }

    /**
     * Create comprehensive test data
     */
    private function createTestData()
    {
        echo "📊 Creating comprehensive test data...\n";

        // Create test users
        $this->testUsers['admin'] = User::factory()->create([
            'role' => 'admin',
            'name' => 'Test Admin',
            'email' => 'admin@test.com'
        ]);

        $this->testUsers['tutor'] = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Test Tutor',
            'email' => 'tutor@test.com'
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->testUsers["student{$i}"] = User::factory()->create([
                'role' => 'student',
                'name' => "Test Student {$i}",
                'email' => "student{$i}@test.com"
            ]);
        }

        // Create test courses
        for ($i = 1; $i <= 3; $i++) {
            $this->testCourses["course{$i}"] = Course::factory()->create([
                'name' => "Test Course {$i}",
                'description' => "Test course {$i} for E2E testing"
            ]);
        }

        // Create test topics
        foreach ($this->testCourses as $key => $course) {
            for ($i = 1; $i <= 2; $i++) {
                $topicKey = "{$key}_topic{$i}";
                $this->testTopics[$topicKey] = Topic::factory()->create([
                    'name' => "Test Topic {$i} - {$course->name}",
                    'course_id' => $course->id,
                    'description' => "Test topic for E2E testing"
                ]);
            }
        }

        echo "✅ Test data created successfully\n";
    }

    /**
     * 🔥 PHASE 1: Test Assignment Creation
     */
    private function test_assignment_creation()
    {
        echo "\n🔥 PHASE 1: TESTING ASSIGNMENT CREATION\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $testsPassed = 0;
        $totalTests = 0;

        // Test 1: Basic assignment creation
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'E2E Test Basic Assignment',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(7),
                'assignment_mode' => 'complete_topic',
                'duration_minutes' => 60
            ]);

            $this->testAssignments['basic'] = $assignment;
            $testsPassed++;
            echo "✅ Basic assignment creation: PASSED\n";
        } catch (\Exception $e) {
            echo "❌ Basic assignment creation: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 2: Source-specific assignment
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'E2E Test Source-Specific Assignment',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(5),
                'assignment_mode' => 'source_specific',
                'assigned_source' => 'test_source_1',
                'duration_minutes' => 45
            ]);

            $this->testAssignments['source_specific'] = $assignment;
            $testsPassed++;
            echo "✅ Source-specific assignment creation: PASSED\n";
        } catch (\Exception $e) {
            echo "❌ Source-specific assignment creation: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 3: Assignment with no time limit
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'E2E Test No Time Limit Assignment',
                'course_id' => $this->testCourses['course2']->id,
                'topic_id' => $this->testTopics['course2_topic1']->id,
                'deadline' => now()->addDays(10),
                'assignment_mode' => 'complete_topic',
                'duration_minutes' => 0 // No time limit
            ]);

            $this->testAssignments['no_time_limit'] = $assignment;
            $testsPassed++;
            echo "✅ No time limit assignment creation: PASSED\n";
        } catch (\Exception $e) {
            echo "❌ No time limit assignment creation: FAILED - " . $e->getMessage() . "\n";
        }

        $this->testResults['assignment_creation'] = "{$testsPassed}/{$totalTests} PASSED";
    }

    /**
     * 🔥 PHASE 2: Test Assignment Deletion via HTTP
     */
    private function test_assignment_deletion_http()
    {
        echo "\n🔥 PHASE 2: TESTING ASSIGNMENT DELETION VIA HTTP\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $testsPassed = 0;
        $totalTests = 0;

        // Test 1: Delete as tutor (should work)
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Test Delete Assignment - Tutor',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(1),
                'assignment_mode' => 'complete_topic'
            ]);

            $response = $this->actingAs($this->testUsers['tutor'])
                            ->deleteJson("/assignments/{$assignment->id}");

            if ($response->status() === 200) {
                $testsPassed++;
                echo "✅ Delete as tutor: PASSED\n";
            } else {
                echo "❌ Delete as tutor: FAILED - Status: {$response->status()}\n";
                echo "    Response: " . $response->getContent() . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ Delete as tutor: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 2: Delete as admin (should work)
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Test Delete Assignment - Admin',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(1),
                'assignment_mode' => 'complete_topic'
            ]);

            $response = $this->actingAs($this->testUsers['admin'])
                            ->deleteJson("/assignments/{$assignment->id}");

            if ($response->status() === 200) {
                $testsPassed++;
                echo "✅ Delete as admin: PASSED\n";
            } else {
                echo "❌ Delete as admin: FAILED - Status: {$response->status()}\n";
                echo "    Response: " . $response->getContent() . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ Delete as admin: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 3: Delete as student (should fail)
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Test Delete Assignment - Student',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(1),
                'assignment_mode' => 'complete_topic'
            ]);

            $response = $this->actingAs($this->testUsers['student1'])
                            ->deleteJson("/assignments/{$assignment->id}");

            if ($response->status() === 403 || $response->status() === 401) {
                $testsPassed++;
                echo "✅ Delete as student (should fail): PASSED\n";
            } else {
                echo "❌ Delete as student (should fail): FAILED - Status: {$response->status()}\n";
            }
        } catch (\Exception $e) {
            echo "❌ Delete as student: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 4: Delete non-existent assignment (should return 404)
        $totalTests++;
        try {
            $response = $this->actingAs($this->testUsers['tutor'])
                            ->deleteJson("/assignments/99999");

            if ($response->status() === 404) {
                $testsPassed++;
                echo "✅ Delete non-existent assignment: PASSED\n";
            } else {
                echo "❌ Delete non-existent assignment: FAILED - Status: {$response->status()}\n";
            }
        } catch (\Exception $e) {
            echo "❌ Delete non-existent assignment: FAILED - " . $e->getMessage() . "\n";
        }

        $this->testResults['assignment_deletion_http'] = "{$testsPassed}/{$totalTests} PASSED";
    }

    /**
     * 🔥 PHASE 3: Test Deadline Scenarios
     */
    private function test_deadline_scenarios()
    {
        echo "\n🔥 PHASE 3: TESTING DEADLINE SCENARIOS\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $testsPassed = 0;
        $totalTests = 0;

        // Test 1: Future deadline assignment
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Future Deadline Assignment',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addWeeks(2),
                'assignment_mode' => 'complete_topic'
            ]);

            if ($assignment->isActive() && !$assignment->isOverdue()) {
                $testsPassed++;
                echo "✅ Future deadline assignment: PASSED\n";
            } else {
                echo "❌ Future deadline assignment: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ Future deadline assignment: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 2: Overdue assignment
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Overdue Assignment',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->subDays(1),
                'assignment_mode' => 'complete_topic'
            ]);

            if ($assignment->isOverdue() && !$assignment->isActive()) {
                $testsPassed++;
                echo "✅ Overdue assignment: PASSED\n";
            } else {
                echo "❌ Overdue assignment: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ Overdue assignment: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 3: Due soon assignment
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Due Soon Assignment',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addHours(2),
                'assignment_mode' => 'complete_topic',
                'duration_minutes' => 30
            ]);

            if ($assignment->isActive() && $assignment->hasTimeLimit()) {
                $testsPassed++;
                echo "✅ Due soon assignment: PASSED\n";
            } else {
                echo "❌ Due soon assignment: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ Due soon assignment: FAILED - " . $e->getMessage() . "\n";
        }

        $this->testResults['deadline_scenarios'] = "{$testsPassed}/{$totalTests} PASSED";
    }

    /**
     * 🔥 PHASE 4: Test Grading and Locking
     */
    private function test_grading_and_locking()
    {
        echo "\n🔥 PHASE 4: TESTING GRADING & LOCKING\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $testsPassed = 0;
        $totalTests = 0;

        // Test 1: Assignment grading
        $totalTests++;
        try {
            $assignment = $this->testAssignments['basic'];
            $user = $this->testUsers['student1'];

            // Create a quiz attempt (simulating grading)
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'topic_id' => $assignment->topic_id,
                'score' => 85,
                'percentage_grade' => 85.0,
                'total_questions' => 10,
                'correct_answers' => 8,
                'time_taken' => 1200,
                'completed_at' => now()
            ]);

            if ($attempt->exists && $assignment->isCompletedByUser($user->id)) {
                $testsPassed++;
                echo "✅ Assignment grading: PASSED\n";
            } else {
                echo "❌ Assignment grading: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ Assignment grading: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 2: One-shot rule (locking after attempt)
        $totalTests++;
        try {
            $assignment = $this->testAssignments['source_specific'];
            $user = $this->testUsers['student2'];

            // User hasn't attempted yet
            $canStart1 = $assignment->canUserStart($user->id);

            // Create an attempt
            QuizAttempt::create([
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'topic_id' => $assignment->topic_id,
                'score' => 45,
                'percentage_grade' => 45.0,
                'total_questions' => 10,
                'correct_answers' => 4,
                'time_taken' => 900,
                'completed_at' => now()
            ]);

            // User has attempted, should be locked
            $canStart2 = $assignment->canUserStart($user->id);

            if ($canStart1 && !$canStart2) {
                $testsPassed++;
                echo "✅ One-shot rule: PASSED\n";
            } else {
                echo "❌ One-shot rule: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ One-shot rule: FAILED - " . $e->getMessage() . "\n";
        }

        $this->testResults['grading_locking'] = "{$testsPassed}/{$totalTests} PASSED";
    }

    /**
     * 🔥 PHASE 5: Test Pivot Table Functionality
     */
    private function test_pivot_table_functionality()
    {
        echo "\n🔥 PHASE 5: TESTING PIVOT TABLE FUNCTIONALITY\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $testsPassed = 0;
        $totalTests = 0;

        // Test 1: Assign users to assignment
        $totalTests++;
        try {
            $assignment = $this->testAssignments['no_time_limit'];
            $user = $this->testUsers['student3'];

            // Assign user to assignment via pivot table
            $assignment->assignedUsers()->attach($user->id);

            if ($assignment->isAssignedToUser($user->id)) {
                $testsPassed++;
                echo "✅ User assignment via pivot: PASSED\n";
            } else {
                echo "❌ User assignment via pivot: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ User assignment via pivot: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 2: Bulk user assignment
        $totalTests++;
        try {
            $assignment = Assignment::create([
                'title' => 'Bulk Assignment Test',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(5),
                'assignment_mode' => 'complete_topic'
            ]);

            $userIds = [
                $this->testUsers['student1']->id,
                $this->testUsers['student2']->id,
                $this->testUsers['student3']->id
            ];

            // Bulk assign users
            $assignment->assignedUsers()->attach($userIds);

            $assignedCount = $assignment->assignedUsers()->count();
            if ($assignedCount === count($userIds)) {
                $testsPassed++;
                echo "✅ Bulk user assignment: PASSED\n";
            } else {
                echo "❌ Bulk user assignment: FAILED - Expected: " . count($userIds) . ", Got: {$assignedCount}\n";
            }
        } catch (\Exception $e) {
            echo "❌ Bulk user assignment: FAILED - " . $e->getMessage() . "\n";
        }

        $this->testResults['pivot_table'] = "{$testsPassed}/{$totalTests} PASSED";
    }

    /**
     * 🔥 PHASE 6: Test Edge Cases
     */
    private function test_edge_cases()
    {
        echo "\n🔥 PHASE 6: TESTING EDGE CASES\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $testsPassed = 0;
        $totalTests = 0;

        // Test 1: Delete assignment with attempts
        $totalTests++;
        try {
            $assignment = $this->testAssignments['basic']; // Has attempts from previous tests
            $assignmentId = $assignment->id;

            $deleted = $assignment->delete();
            $stillExists = Assignment::find($assignmentId);

            if ($deleted && !$stillExists) {
                $testsPassed++;
                echo "✅ Delete assignment with attempts: PASSED\n";
            } else {
                echo "❌ Delete assignment with attempts: FAILED\n";
            }
        } catch (\Exception $e) {
            echo "❌ Delete assignment with attempts: FAILED - " . $e->getMessage() . "\n";
        }

        // Test 2: Assignment status computation
        $totalTests++;
        try {
            $activeAssignment = Assignment::create([
                'title' => 'Active Status Test',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->addDays(1),
                'assignment_mode' => 'complete_topic'
            ]);

            $overdueAssignment = Assignment::create([
                'title' => 'Overdue Status Test',
                'course_id' => $this->testCourses['course1']->id,
                'topic_id' => $this->testTopics['course1_topic1']->id,
                'deadline' => now()->subDays(1),
                'assignment_mode' => 'complete_topic'
            ]);

            $activeStatus = $activeAssignment->getCurrentStatusAttribute();
            $overdueStatus = $overdueAssignment->getCurrentStatusAttribute();

            if ($activeStatus === Assignment::STATUS_ACTIVE && 
                $overdueStatus === Assignment::STATUS_OVERDUE) {
                $testsPassed++;
                echo "✅ Assignment status computation: PASSED\n";
            } else {
                echo "❌ Assignment status computation: FAILED - Active: {$activeStatus}, Overdue: {$overdueStatus}\n";
            }
        } catch (\Exception $e) {
            echo "❌ Assignment status computation: FAILED - " . $e->getMessage() . "\n";
        }

        $this->testResults['edge_cases'] = "{$testsPassed}/{$totalTests} PASSED";
    }

    /**
     * Generate final comprehensive report
     */
    private function generateFinalReport($executionTime)
    {
        echo "\n" . "=" . str_repeat("=", 60) . "\n";
        echo "🏁 COMPREHENSIVE E2E TEST REPORT\n";
        echo "=" . str_repeat("=", 60) . "\n";
        echo "⏱️  Execution Time: {$executionTime} seconds\n";
        echo "📊 Test Results:\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $totalPassed = 0;
        $totalTests = 0;

        foreach ($this->testResults as $phase => $result) {
            echo sprintf("%-30s: %s\n", ucwords(str_replace('_', ' ', $phase)), $result);

            if (strpos($result, '/') !== false) {
                $parts = explode('/', $result);
                if (count($parts) >= 2) {
                    $passed = (int)$parts[0];
                    $total = (int)explode(' ', $parts[1])[0];
                    $totalPassed += $passed;
                    $totalTests += $total;
                }
            }
        }

        echo "-" . str_repeat("-", 50) . "\n";
        echo sprintf("🎯 OVERALL SCORE: %d/%d (%.1f%%)\n",
                    $totalPassed, $totalTests,
                    $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0);

        if ($totalPassed === $totalTests) {
            echo "🎉 ALL TESTS PASSED! ASSIGNMENT SYSTEM IS BULLETPROOF!\n";
        } else {
            echo "⚠️  SOME TESTS FAILED. REVIEW RESULTS ABOVE.\n";
        }

        echo "=" . str_repeat("=", 60) . "\n";

        // Provide specific recommendations
        $this->provideRecommendations();
    }

    /**
     * Provide specific recommendations based on test results
     */
    private function provideRecommendations()
    {
        echo "\n🔧 RECOMMENDATIONS:\n";
        echo "-" . str_repeat("-", 30) . "\n";

        echo "1. ASSIGNMENT DELETION 404 FIX:\n";
        echo "   - Ensure user is authenticated with tutor/admin role\n";
        echo "   - Verify assignment ID exists in database\n";
        echo "   - Check CSRF token in JavaScript requests\n\n";

        echo "2. JAVASCRIPT IMPROVEMENTS:\n";
        echo "   - Added better error handling and debugging\n";
        echo "   - Enhanced user feedback for different error types\n";
        echo "   - Added request logging for troubleshooting\n\n";

        echo "3. CONTROLLER ENHANCEMENTS:\n";
        echo "   - Added comprehensive logging for deletion attempts\n";
        echo "   - Improved error messages and status codes\n";
        echo "   - Better exception handling\n\n";

        echo "4. TESTING RECOMMENDATIONS:\n";
        echo "   - Run this E2E test regularly to catch regressions\n";
        echo "   - Test with different user roles and permissions\n";
        echo "   - Verify database constraints and relationships\n\n";
    }
}

// If running as standalone script
if (php_sapi_name() === 'cli' && !class_exists('Tests\TestCase')) {
    echo "⚠️  This is a Laravel test class. Run with: php artisan test --filter ComprehensiveAssignmentE2ETest\n";
}