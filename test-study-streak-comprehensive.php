<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class StudyStreakTester
{
    private $notificationService;
    private $testResults = [];
    private $testUser;

    public function __construct()
    {
        $this->notificationService = app(NotificationService::class);
    }

    public function runAllTests()
    {
        echo "🔥 COMPREHENSIVE STUDY STREAK TESTING SUITE 🔥\n";
        echo "=" . str_repeat("=", 60) . "\n\n";

        // Create test user
        $this->createTestUser();

        // Run all test scenarios
        $this->testFirstQuizStreak();
        $this->testConsecutiveDayStreak();
        $this->testStreakReset();
        $this->testSameDayMultipleQuizzes();
        $this->testSkippedDayStreak();
        $this->testWeekLongStreak();
        $this->testMonthLongStreak();
        $this->testStreakNotifications();
        $this->testStreakResetNotifications();
        $this->testDatabaseIntegrity();
        $this->testEdgeCases();
        $this->testPerformanceScenarios();

        // Display results
        $this->displayResults();

        // Cleanup
        $this->cleanup();
    }

    private function createTestUser()
    {
        echo "📝 Creating test user...\n";
        
        // Delete existing test user if exists
        User::where('email', 'streak.test@example.com')->delete();
        
        $this->testUser = User::create([
            'name' => 'Streak Test User',
            'email' => 'streak.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'study_streak_days' => 0,
            'correct_answers_count' => 0,
            'total_questions_attempted' => 0,
            'xp' => 0
        ]);

        echo "✅ Test user created (ID: {$this->testUser->id})\n\n";
    }

    private function testFirstQuizStreak()
    {
        echo "🎯 TEST 1: First Quiz Streak\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Reset user streak
        $this->testUser->update(['study_streak_days' => 0]);

        // Create first quiz attempt
        $this->createQuizAttempt(now());

        // Update streak
        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'First quiz should set streak to 1',
            $newStreak === 1,
            "Expected: 1, Got: $newStreak"
        );

        $this->testUser->refresh();
        $this->assertTest(
            'Database should reflect streak = 1',
            $this->testUser->study_streak_days === 1,
            "Expected: 1, Got: {$this->testUser->study_streak_days}"
        );

        echo "\n";
    }

    private function testConsecutiveDayStreak()
    {
        echo "🔥 TEST 2: Consecutive Day Streak\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Reset user
        $this->testUser->update(['study_streak_days' => 1]);
        $this->clearQuizAttempts();

        // Create quiz attempts for consecutive days
        $dates = [
            now()->subDays(4)->startOfDay()->addHours(10), // 5 days ago
            now()->subDays(3)->startOfDay()->addHours(14), // 4 days ago
            now()->subDays(2)->startOfDay()->addHours(9),  // 3 days ago
            now()->subDays(1)->startOfDay()->addHours(16), // Yesterday
            now()->startOfDay()->addHours(11)              // Today
        ];

        foreach ($dates as $date) {
            $this->createQuizAttempt($date);
        }

        // Update streak
        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            '5 consecutive days should give streak of 5',
            $newStreak === 5,
            "Expected: 5, Got: $newStreak"
        );

        echo "\n";
    }

    private function testStreakReset()
    {
        echo "💔 TEST 3: Streak Reset Scenario\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Set up user with existing streak
        $this->testUser->update(['study_streak_days' => 7]);
        $this->clearQuizAttempts();

        // Create quiz attempt 3 days ago (gap should reset streak)
        $this->createQuizAttempt(now()->subDays(3)->startOfDay()->addHours(10));

        // Update streak
        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Gap in streak should reset to 1',
            $newStreak === 1,
            "Expected: 1, Got: $newStreak"
        );

        // Check if reset notification was created
        $resetNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'streak_reset')
            ->latest()
            ->first();

        $this->assertTest(
            'Streak reset should create notification',
            $resetNotification !== null,
            "No streak reset notification found"
        );

        echo "\n";
    }

    private function testSameDayMultipleQuizzes()
    {
        echo "📚 TEST 4: Same Day Multiple Quizzes\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Reset user
        $this->testUser->update(['study_streak_days' => 3]);
        $this->clearQuizAttempts();

        $today = now()->startOfDay();

        // Create multiple quiz attempts on the same day
        $this->createQuizAttempt($today->copy()->addHours(9));
        $this->createQuizAttempt($today->copy()->addHours(14));
        $this->createQuizAttempt($today->copy()->addHours(18));

        // Also create yesterday's attempt
        $this->createQuizAttempt($today->copy()->subDay()->addHours(15));

        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Multiple quizzes same day should maintain streak',
            $newStreak === 2,
            "Expected: 2, Got: $newStreak"
        );

        echo "\n";
    }

    private function testSkippedDayStreak()
    {
        echo "⏭️ TEST 5: Skipped Day Streak\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Reset user
        $this->testUser->update(['study_streak_days' => 5]);
        $this->clearQuizAttempts();

        // Create attempts with a gap
        $this->createQuizAttempt(now()->subDays(3)->startOfDay()->addHours(10)); // 3 days ago
        $this->createQuizAttempt(now()->subDays(1)->startOfDay()->addHours(15)); // Yesterday (skipped day 2)

        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Skipped day should reset streak to 1',
            $newStreak === 1,
            "Expected: 1, Got: $newStreak"
        );

        echo "\n";
    }

    private function testWeekLongStreak()
    {
        echo "📅 TEST 6: Week-Long Streak\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Reset user
        $this->testUser->update(['study_streak_days' => 6]); // Set to 6 so 7 is a milestone increase
        $this->clearQuizAttempts();

        // Create 7 consecutive days of quiz attempts
        for ($i = 6; $i >= 0; $i--) {
            $this->createQuizAttempt(now()->subDays($i)->startOfDay()->addHours(rand(9, 18)));
        }

        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            '7 consecutive days should give streak of 7',
            $newStreak === 7,
            "Expected: 7, Got: $newStreak"
        );

        // Check for week streak notification
        $weekNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'study_streak')
            ->get()
            ->filter(function($notification) {
                $data = json_decode($notification->data, true);
                return isset($data['streak_days']) && $data['streak_days'] == 7;
            })
            ->first();

        $this->assertTest(
            'Week streak should create special notification',
            $weekNotification !== null && str_contains($weekNotification->title, 'Week'),
            "No week streak notification found"
        );

        echo "\n";
    }

    private function testMonthLongStreak()
    {
        echo "🏆 TEST 7: Month-Long Streak\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Reset user
        $this->testUser->update(['study_streak_days' => 29]); // Set to 29 so 30 is a milestone increase
        $this->clearQuizAttempts();

        // Create 30 consecutive days of quiz attempts
        for ($i = 29; $i >= 0; $i--) {
            $this->createQuizAttempt(now()->subDays($i)->startOfDay()->addHours(rand(9, 18)));
        }

        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            '30 consecutive days should give streak of 30',
            $newStreak === 30,
            "Expected: 30, Got: $newStreak"
        );

        // Check for month streak notification
        $monthNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'study_streak')
            ->get()
            ->filter(function($notification) {
                $data = json_decode($notification->data, true);
                return isset($data['streak_days']) && $data['streak_days'] == 30;
            })
            ->first();

        $this->assertTest(
            'Month streak should create special notification',
            $monthNotification !== null && str_contains($monthNotification->title, 'Month'),
            "No month streak notification found"
        );

        echo "\n";
    }

    private function testStreakNotifications()
    {
        echo "🔔 TEST 8: Streak Notifications\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Clear existing notifications
        Notification::where('user_id', $this->testUser->id)->delete();

        // Test different streak milestones
        $milestones = [1, 2, 3, 7, 14, 30, 60];

        foreach ($milestones as $streak) {
            $notification = $this->notificationService->createStudyStreakNotification($this->testUser, $streak);
            
            $this->assertTest(
                "Streak $streak notification created",
                $notification !== null,
                "Failed to create notification for streak $streak"
            );

            $this->assertTest(
                "Streak $streak notification has correct data",
                json_decode($notification->data)->streak_days === $streak,
                "Notification data mismatch for streak $streak"
            );
        }

        echo "\n";
    }

    private function testStreakResetNotifications()
    {
        echo "🔄 TEST 9: Streak Reset Notifications\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Clear existing notifications
        Notification::where('user_id', $this->testUser->id)->delete();

        // Set up user with high streak
        $this->testUser->update(['study_streak_days' => 15]);

        // Create reset notification
        $notification = $this->notificationService->createStreakResetNotification($this->testUser);

        $this->assertTest(
            'Streak reset notification created',
            $notification !== null,
            "Failed to create streak reset notification"
        );

        $this->assertTest(
            'Reset notification has correct type',
            $notification->type === 'streak_reset',
            "Expected type: streak_reset, Got: {$notification->type}"
        );

        $this->assertTest(
            'Reset notification has encouraging message',
            str_contains($notification->message, 'new opportunity'),
            "Reset notification message not encouraging"
        );

        echo "\n";
    }

    private function testDatabaseIntegrity()
    {
        echo "🗄️ TEST 10: Database Integrity\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Test database constraints and data integrity
        $this->testUser->refresh();

        $this->assertTest(
            'User streak is non-negative',
            $this->testUser->study_streak_days >= 0,
            "Streak cannot be negative: {$this->testUser->study_streak_days}"
        );

        // Test quiz attempts are properly linked
        $quizCount = QuizAttempt::where('user_id', $this->testUser->id)->count();
        $this->assertTest(
            'Quiz attempts are properly stored',
            $quizCount > 0,
            "No quiz attempts found for user"
        );

        // Test notification integrity
        $notificationCount = Notification::where('user_id', $this->testUser->id)->count();
        $this->assertTest(
            'Notifications are properly stored',
            $notificationCount > 0,
            "No notifications found for user"
        );

        echo "\n";
    }

    private function testEdgeCases()
    {
        echo "🎭 TEST 11: Edge Cases\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Test midnight boundary
        $this->testUser->update(['study_streak_days' => 2]);
        $this->clearQuizAttempts();

        // Quiz at 11:59 PM yesterday and 12:01 AM today
        $this->createQuizAttempt(now()->subDay()->endOfDay()->subMinute());
        $this->createQuizAttempt(now()->startOfDay()->addMinute());

        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Midnight boundary should maintain streak',
            $newStreak >= 2,
            "Expected: >= 2, Got: $newStreak"
        );

        // Test null streak initialization
        $this->testUser->update(['study_streak_days' => null]);
        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Null streak should initialize to 1 with quiz',
            $newStreak >= 1,
            "Expected: >= 1, Got: $newStreak"
        );

        echo "\n";
    }

    private function testPerformanceScenarios()
    {
        echo "⚡ TEST 12: Performance Scenarios\n";
        echo "-" . str_repeat("-", 30) . "\n";

        $startTime = microtime(true);

        // Create many quiz attempts
        for ($i = 0; $i < 100; $i++) {
            $this->createQuizAttempt(now()->subDays(rand(0, 30))->addHours(rand(9, 18)));
        }

        // Update streak
        $this->testUser->updateStudyStreak();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertTest(
            'Streak calculation should be fast (<500ms)',
            $executionTime < 500,
            "Execution time: {$executionTime}ms"
        );

        echo "\n";
    }

    private function createQuizAttempt($date)
    {
        $correctAnswers = rand(5, 10);
        $totalQuestions = 10;
        $score = $correctAnswers;
        $percentageGrade = round(($correctAnswers / $totalQuestions) * 100, 2);
        
        // Get a random question ID for the question_data
        $questionId = \App\Models\Question::inRandomOrder()->first()->id ?? 1;
        
        $questionData = [];
        for ($i = 0; $i < $totalQuestions; $i++) {
            $questionData[] = [
                'question_id' => $questionId,
                'user_answer' => 'Test Answer',
                'is_correct' => $i < $correctAnswers
            ];
        }

        // Use DB::table to bypass model events and set exact timestamps
        $attemptId = \Illuminate\Support\Facades\DB::table('quiz_attempts')->insertGetId([
            'user_id' => $this->testUser->id,
            'topic_id' => 1,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'test',
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage_grade' => $percentageGrade,
            'duration_seconds' => rand(300, 1800),
            'question_data' => json_encode($questionData),
            'created_at' => $date,
            'updated_at' => $date
        ]);
        
        return QuizAttempt::find($attemptId);
    }

    private function clearQuizAttempts()
    {
        QuizAttempt::where('user_id', $this->testUser->id)->delete();
    }

    private function assertTest($description, $condition, $errorMessage = '')
    {
        $result = [
            'description' => $description,
            'passed' => $condition,
            'error' => $errorMessage
        ];

        $this->testResults[] = $result;

        $status = $condition ? '✅' : '❌';
        echo "  $status $description";
        
        if (!$condition && $errorMessage) {
            echo " - $errorMessage";
        }
        
        echo "\n";
    }

    private function displayResults()
    {
        echo "\n" . "=" . str_repeat("=", 60) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo "=" . str_repeat("=", 60) . "\n\n";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($test) => $test['passed']));
        $failedTests = $totalTests - $passedTests;

        echo "Total Tests: $totalTests\n";
        echo "✅ Passed: $passedTests\n";
        echo "❌ Failed: $failedTests\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

        if ($failedTests > 0) {
            echo "🚨 FAILED TESTS:\n";
            echo "-" . str_repeat("-", 40) . "\n";
            foreach ($this->testResults as $test) {
                if (!$test['passed']) {
                    echo "❌ {$test['description']}\n";
                    if ($test['error']) {
                        echo "   Error: {$test['error']}\n";
                    }
                }
            }
            echo "\n";
        }

        // Display streak functionality status
        if ($passedTests === $totalTests) {
            echo "🎉 ALL TESTS PASSED! Study streak functionality is working flawlessly!\n";
            echo "🔥 Streak logic is robust and handles all scenarios correctly.\n";
            echo "🔔 Notification system is properly integrated.\n";
            echo "🗄️ Database integrity is maintained.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the streak implementation.\n";
        }

        echo "\n" . "=" . str_repeat("=", 60) . "\n";
    }

    private function cleanup()
    {
        echo "🧹 Cleaning up test data...\n";
        
        // Delete test user and related data
        QuizAttempt::where('user_id', $this->testUser->id)->delete();
        Notification::where('user_id', $this->testUser->id)->delete();
        $this->testUser->delete();
        
        echo "✅ Cleanup completed!\n";
    }
}

// Run the comprehensive test suite
$tester = new StudyStreakTester();
$tester->runAllTests();