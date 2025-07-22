<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class StreakNotificationIntegrationTester
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
        echo "🔔 STREAK NOTIFICATION INTEGRATION TESTING 🔔\n";
        echo "=" . str_repeat("=", 60) . "\n\n";

        // Create test user
        $this->createTestUser();

        // Run notification integration tests
        $this->testStreakProgressNotifications();
        $this->testStreakMilestoneNotifications();
        $this->testStreakResetNotifications();
        $this->testNotificationContent();
        $this->testNotificationTiming();
        $this->testNotificationDeduplication();
        $this->testRealWorldScenarios();

        // Display results
        $this->displayResults();

        // Cleanup
        $this->cleanup();
    }

    private function createTestUser()
    {
        echo "📝 Creating test user...\n";
        
        // Delete existing test user if exists
        User::where('email', 'notification.test@example.com')->delete();
        
        $this->testUser = User::create([
            'name' => 'Notification Test User',
            'email' => 'notification.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'study_streak_days' => 1,
            'correct_answers_count' => 0,
            'total_questions_attempted' => 0,
            'xp' => 0
        ]);

        echo "✅ Test user created (ID: {$this->testUser->id})\n\n";
    }

    private function testStreakProgressNotifications()
    {
        echo "📈 TEST 1: Streak Progress Notifications\n";
        echo "-" . str_repeat("-", 40) . "\n";

        // Clear existing data
        $this->clearQuizAttempts();
        Notification::where('user_id', $this->testUser->id)->delete();

        // Test progressive streak building with notifications
        $progressiveStreaks = [1, 2, 3, 7, 14, 30];
        
        foreach ($progressiveStreaks as $targetStreak) {
            // Set up user for this streak level
            $this->testUser->update(['study_streak_days' => $targetStreak - 1]);
            $this->clearQuizAttempts();
            
            // Create quiz attempts for consecutive days
            for ($i = $targetStreak - 1; $i >= 0; $i--) {
                $this->createQuizAttempt(now()->subDays($i)->startOfDay()->addHours(rand(9, 18)));
            }
            
            // Update streak and check notification
            $newStreak = $this->testUser->updateStudyStreak();
            
            $this->assertTest(
                "Streak $targetStreak should be calculated correctly",
                $newStreak === $targetStreak,
                "Expected: $targetStreak, Got: $newStreak"
            );
            
            // Check if appropriate notification was created
            $notification = Notification::where('user_id', $this->testUser->id)
                ->where('type', 'study_streak')
                ->latest()
                ->first();
                
            if (in_array($targetStreak, [1, 2, 3, 7, 14, 30])) {
                $this->assertTest(
                    "Streak $targetStreak should create notification",
                    $notification !== null,
                    "No notification found for streak $targetStreak"
                );
                
                if ($notification) {
                    $data = json_decode($notification->data, true);
                    $this->assertTest(
                        "Notification data should match streak $targetStreak",
                        $data['streak_days'] === $targetStreak,
                        "Expected: $targetStreak, Got: " . ($data['streak_days'] ?? 'null')
                    );
                }
            }
            
            // Clear for next test
            Notification::where('user_id', $this->testUser->id)->delete();
        }

        echo "\n";
    }

    private function testStreakMilestoneNotifications()
    {
        echo "🏆 TEST 2: Streak Milestone Notifications\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $milestones = [
            7 => 'Week',
            14 => 'Two Week',
            30 => 'Month',
            60 => 'Legendary'
        ];

        foreach ($milestones as $streak => $expectedKeyword) {
            // Set up user for milestone
            $this->testUser->update(['study_streak_days' => $streak - 1]);
            $this->clearQuizAttempts();
            Notification::where('user_id', $this->testUser->id)->delete();
            
            // Create quiz attempts for the milestone streak
            for ($i = $streak - 1; $i >= 0; $i--) {
                $this->createQuizAttempt(now()->subDays($i)->startOfDay()->addHours(rand(9, 18)));
            }
            
            $newStreak = $this->testUser->updateStudyStreak();
            
            // Check milestone notification
            $notification = Notification::where('user_id', $this->testUser->id)
                ->where('type', 'study_streak')
                ->latest()
                ->first();
                
            $this->assertTest(
                "Streak $streak should create milestone notification",
                $notification !== null,
                "No milestone notification found for streak $streak"
            );
            
            if ($notification) {
                $this->assertTest(
                    "Milestone notification should contain '$expectedKeyword'",
                    str_contains($notification->title, $expectedKeyword),
                    "Title '{$notification->title}' doesn't contain '$expectedKeyword'"
                );
                
                $this->assertTest(
                    "Milestone notification should be encouraging",
                    str_contains($notification->message, '!') || str_contains($notification->message, 'Amazing') || str_contains($notification->message, 'Incredible'),
                    "Message not encouraging: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testStreakResetNotifications()
    {
        echo "💔 TEST 3: Streak Reset Notifications\n";
        echo "-" . str_repeat("-", 40) . "\n";

        // Set up user with high streak
        $this->testUser->update(['study_streak_days' => 15]);
        $this->clearQuizAttempts();
        Notification::where('user_id', $this->testUser->id)->delete();

        // Create quiz attempt 3 days ago (should reset streak)
        $this->createQuizAttempt(now()->subDays(3)->startOfDay()->addHours(10));

        $newStreak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Streak should reset to 1',
            $newStreak === 1,
            "Expected: 1, Got: $newStreak"
        );

        // Check reset notification
        $resetNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'streak_reset')
            ->first();

        $this->assertTest(
            'Reset notification should be created',
            $resetNotification !== null,
            "No reset notification found"
        );

        if ($resetNotification) {
            $this->assertTest(
                'Reset notification should be encouraging',
                str_contains($resetNotification->message, 'new opportunity') || str_contains($resetNotification->message, 'begin again'),
                "Reset message not encouraging: {$resetNotification->message}"
            );
        }

        echo "\n";
    }

    private function testNotificationContent()
    {
        echo "📝 TEST 4: Notification Content Quality\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $testCases = [
            1 => ['started', 'streak'],
            2 => ['Two days', 'momentum'],
            3 => ['Three day', 'habit'],
            7 => ['Week', 'Amazing'],
            14 => ['Two Week', 'dedication'],
            30 => ['Month', 'incredible'],
            60 => ['Legendary', 'elite']
        ];

        foreach ($testCases as $streak => $keywords) {
            $notification = $this->notificationService->createStudyStreakNotification($this->testUser, $streak);
            
            $this->assertTest(
                "Streak $streak notification should be created",
                $notification !== null,
                "Failed to create notification for streak $streak"
            );
            
            if ($notification) {
                foreach ($keywords as $keyword) {
                    $this->assertTest(
                        "Streak $streak should contain '$keyword'",
                        str_contains($notification->title, $keyword) || str_contains($notification->message, $keyword),
                        "Notification doesn't contain '$keyword': {$notification->title} - {$notification->message}"
                    );
                }
                
                // Check data structure
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Notification data should contain streak_days",
                    isset($data['streak_days']) && $data['streak_days'] === $streak,
                    "Data missing or incorrect: " . json_encode($data)
                );
            }
            
            // Clean up
            $notification->delete();
        }

        echo "\n";
    }

    private function testNotificationTiming()
    {
        echo "⏰ TEST 5: Notification Timing\n";
        echo "-" . str_repeat("-", 40) . "\n";

        // Clear existing data
        $this->clearQuizAttempts();
        Notification::where('user_id', $this->testUser->id)->delete();

        // Test that notifications are created immediately after streak update
        $this->testUser->update(['study_streak_days' => 6]);
        
        // Create 7 days of quiz attempts
        for ($i = 6; $i >= 0; $i--) {
            $this->createQuizAttempt(now()->subDays($i)->startOfDay()->addHours(10));
        }

        $beforeTime = now();
        $newStreak = $this->testUser->updateStudyStreak();
        $afterTime = now();

        $notification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'study_streak')
            ->first();

        $this->assertTest(
            'Notification should be created immediately',
            $notification !== null,
            "No notification found"
        );

        if ($notification) {
            $this->assertTest(
                'Notification timestamp should be within execution window',
                $notification->created_at >= $beforeTime && $notification->created_at <= $afterTime,
                "Notification created at {$notification->created_at}, expected between $beforeTime and $afterTime"
            );
        }

        echo "\n";
    }

    private function testNotificationDeduplication()
    {
        echo "🔄 TEST 6: Notification Deduplication\n";
        echo "-" . str_repeat("-", 40) . "\n";

        // Clear existing data
        Notification::where('user_id', $this->testUser->id)->delete();

        // Create multiple notifications for the same streak level
        $notification1 = $this->notificationService->createStudyStreakNotification($this->testUser, 7);
        $notification2 = $this->notificationService->createStudyStreakNotification($this->testUser, 7);
        $notification3 = $this->notificationService->createStudyStreakNotification($this->testUser, 7);

        $this->assertTest(
            'All notifications should be created (no deduplication in service)',
            $notification1 && $notification2 && $notification3,
            "Some notifications were not created"
        );

        // Count notifications
        $notificationCount = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'study_streak')
            ->count();

        $this->assertTest(
            'Multiple notifications should be allowed',
            $notificationCount === 3,
            "Expected: 3, Got: $notificationCount"
        );

        echo "\n";
    }

    private function testRealWorldScenarios()
    {
        echo "🌍 TEST 7: Real World Scenarios\n";
        echo "-" . str_repeat("-", 40) . "\n";

        // Scenario 1: User builds streak over time
        $this->clearQuizAttempts();
        Notification::where('user_id', $this->testUser->id)->delete();
        $this->testUser->update(['study_streak_days' => 1]);

        // Simulate 10 days of activity
        for ($day = 1; $day <= 10; $day++) {
            $this->createQuizAttempt(now()->subDays(10 - $day)->startOfDay()->addHours(rand(9, 18)));
            $streak = $this->testUser->updateStudyStreak();
            
            // Check if milestone notifications are created at appropriate times
            if (in_array($day, [3, 7])) {
                $notification = Notification::where('user_id', $this->testUser->id)
                    ->where('type', 'study_streak')
                    ->latest()
                    ->first();
                    
                $this->assertTest(
                    "Day $day should create milestone notification",
                    $notification !== null,
                    "No notification found for day $day"
                );
            }
        }

        // Scenario 2: User loses streak and rebuilds
        $this->clearQuizAttempts();
        Notification::where('user_id', $this->testUser->id)->delete();
        $this->testUser->update(['study_streak_days' => 10]);

        // Create gap (3 days ago)
        $this->createQuizAttempt(now()->subDays(3)->startOfDay()->addHours(10));
        $streak = $this->testUser->updateStudyStreak();

        $this->assertTest(
            'Streak should reset after gap',
            $streak === 1,
            "Expected: 1, Got: $streak"
        );

        $resetNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'streak_reset')
            ->first();

        $this->assertTest(
            'Reset notification should be created',
            $resetNotification !== null,
            "No reset notification found"
        );

        echo "\n";
    }

    private function createQuizAttempt($date)
    {
        $correctAnswers = rand(5, 10);
        $totalQuestions = 10;
        $score = $correctAnswers;
        $percentageGrade = round(($correctAnswers / $totalQuestions) * 100, 2);
        
        $questionId = \App\Models\Question::inRandomOrder()->first()->id ?? 1;
        
        $questionData = [];
        for ($i = 0; $i < $totalQuestions; $i++) {
            $questionData[] = [
                'question_id' => $questionId,
                'user_answer' => 'Test Answer',
                'is_correct' => $i < $correctAnswers
            ];
        }

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
        echo "📊 NOTIFICATION INTEGRATION TEST RESULTS\n";
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

        if ($passedTests === $totalTests) {
            echo "🎉 ALL NOTIFICATION INTEGRATION TESTS PASSED!\n";
            echo "🔔 Smart notifications are working perfectly!\n";
            echo "📈 Progress tracking is accurate and timely!\n";
            echo "💬 Notification content is engaging and appropriate!\n";
        } else {
            echo "⚠️  Some notification tests failed. Please review the implementation.\n";
        }

        echo "\n" . "=" . str_repeat("=", 60) . "\n";
    }

    private function cleanup()
    {
        echo "🧹 Cleaning up test data...\n";
        
        QuizAttempt::where('user_id', $this->testUser->id)->delete();
        Notification::where('user_id', $this->testUser->id)->delete();
        $this->testUser->delete();
        
        echo "✅ Cleanup completed!\n";
    }
}

// Run the notification integration test suite
$tester = new StreakNotificationIntegrationTester();
$tester->runAllTests();