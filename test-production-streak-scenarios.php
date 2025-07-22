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

class ProductionStreakScenarioTester
{
    private $testResults = [];
    private $users = [];

    public function runAllTests()
    {
        echo "🚀 PRODUCTION STREAK SCENARIO TESTING 🚀\n";
        echo "=" . str_repeat("=", 60) . "\n\n";

        // Test various production scenarios
        $this->testNewUserJourney();
        $this->testConsistentStudentScenario();
        $this->testInconsistentStudentScenario();
        $this->testStreakRecoveryScenario();
        $this->testHighAchieverScenario();
        $this->testProfilePageIntegration();

        // Display results
        $this->displayResults();

        // Cleanup
        $this->cleanup();
    }

    private function testNewUserJourney()
    {
        echo "👶 TEST 1: New User Journey\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $user = $this->createUser('new.user@example.com', 'New User');

        // Day 1: First quiz
        $this->createQuizAttempt($user, now()->startOfDay()->addHours(10));
        $streak = $user->updateStudyStreak();

        $this->assertTest(
            'New user first quiz should set streak to 1',
            $streak === 1,
            "Expected: 1, Got: $streak"
        );

        // Check first streak notification
        $notification = Notification::where('user_id', $user->id)
            ->where('type', 'study_streak')
            ->first();

        $this->assertTest(
            'First streak should create encouraging notification',
            $notification !== null && str_contains($notification->message, 'started'),
            "No encouraging first streak notification found"
        );

        // Day 2: Second quiz
        $this->createQuizAttempt($user, now()->subDays(1)->startOfDay()->addHours(14));
        $streak = $user->updateStudyStreak();

        $this->assertTest(
            'Second day should increment streak to 2',
            $streak === 2,
            "Expected: 2, Got: $streak"
        );

        // Day 3: Third quiz
        $this->createQuizAttempt($user, now()->subDays(2)->startOfDay()->addHours(16));
        $streak = $user->updateStudyStreak();

        $this->assertTest(
            'Third day should increment streak to 3',
            $streak === 3,
            "Expected: 3, Got: $streak"
        );

        echo "\n";
    }

    private function testConsistentStudentScenario()
    {
        echo "📚 TEST 2: Consistent Student Scenario\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $user = $this->createUser('consistent.student@example.com', 'Consistent Student');

        // Simulate 2 weeks of consistent daily study
        for ($day = 13; $day >= 0; $day--) {
            $this->createQuizAttempt($user, now()->subDays($day)->startOfDay()->addHours(rand(9, 18)));
        }

        $streak = $user->updateStudyStreak();

        $this->assertTest(
            '14 days of consistent study should give streak of 14',
            $streak === 14,
            "Expected: 14, Got: $streak"
        );

        // Check for milestone notifications
        $weekNotification = Notification::where('user_id', $user->id)
            ->where('type', 'study_streak')
            ->get()
            ->filter(function($n) {
                $data = json_decode($n->data, true);
                return $data['streak_days'] == 7;
            })
            ->first();

        $this->assertTest(
            'Week milestone should create special notification',
            $weekNotification !== null && str_contains($weekNotification->title, 'Week'),
            "No week milestone notification found"
        );

        $twoWeekNotification = Notification::where('user_id', $user->id)
            ->where('type', 'study_streak')
            ->get()
            ->filter(function($n) {
                $data = json_decode($n->data, true);
                return $data['streak_days'] == 14;
            })
            ->first();

        $this->assertTest(
            'Two week milestone should create special notification',
            $twoWeekNotification !== null && str_contains($twoWeekNotification->title, 'Two Week'),
            "No two week milestone notification found"
        );

        echo "\n";
    }

    private function testInconsistentStudentScenario()
    {
        echo "📉 TEST 3: Inconsistent Student Scenario\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $user = $this->createUser('inconsistent.student@example.com', 'Inconsistent Student');

        // Build up a streak
        for ($day = 9; $day >= 0; $day--) {
            $this->createQuizAttempt($user, now()->subDays($day)->startOfDay()->addHours(rand(9, 18)));
        }

        $initialStreak = $user->updateStudyStreak();

        $this->assertTest(
            'Initial consistent period should build streak',
            $initialStreak === 10,
            "Expected: 10, Got: $initialStreak"
        );

        // Clear recent attempts and create gap
        QuizAttempt::where('user_id', $user->id)->delete();
        $this->createQuizAttempt($user, now()->subDays(5)->startOfDay()->addHours(10));

        $resetStreak = $user->updateStudyStreak();

        $this->assertTest(
            'Gap in activity should reset streak to 1',
            $resetStreak === 1,
            "Expected: 1, Got: $resetStreak"
        );

        // Check for reset notification
        $resetNotification = Notification::where('user_id', $user->id)
            ->where('type', 'streak_reset')
            ->first();

        $this->assertTest(
            'Streak reset should create encouraging notification',
            $resetNotification !== null && str_contains($resetNotification->message, 'new opportunity'),
            "No encouraging reset notification found"
        );

        echo "\n";
    }

    private function testStreakRecoveryScenario()
    {
        echo "💪 TEST 4: Streak Recovery Scenario\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $user = $this->createUser('recovery.student@example.com', 'Recovery Student');

        // User had a streak, lost it, now rebuilding
        $user->update(['study_streak_days' => 1]);

        // Rebuild streak over several days
        $recoveryDays = [3, 2, 1, 0]; // 4 days of recovery
        foreach ($recoveryDays as $daysAgo) {
            $this->createQuizAttempt($user, now()->subDays($daysAgo)->startOfDay()->addHours(rand(9, 18)));
        }

        $recoveryStreak = $user->updateStudyStreak();

        $this->assertTest(
            'Recovery period should build new streak',
            $recoveryStreak === 4,
            "Expected: 4, Got: $recoveryStreak"
        );

        // Check that user gets encouragement for rebuilding
        $notifications = Notification::where('user_id', $user->id)
            ->where('type', 'study_streak')
            ->get();

        $this->assertTest(
            'Recovery should generate progress notifications',
            $notifications->count() > 0,
            "No progress notifications during recovery"
        );

        echo "\n";
    }

    private function testHighAchieverScenario()
    {
        echo "🏆 TEST 5: High Achiever Scenario\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $user = $this->createUser('high.achiever@example.com', 'High Achiever');

        // Simulate 2 months of consistent study
        for ($day = 59; $day >= 0; $day--) {
            $this->createQuizAttempt($user, now()->subDays($day)->startOfDay()->addHours(rand(9, 18)));
        }

        $streak = $user->updateStudyStreak();

        $this->assertTest(
            '60 days of study should give streak of 60',
            $streak === 60,
            "Expected: 60, Got: $streak"
        );

        // Check for legendary notification
        $legendaryNotification = Notification::where('user_id', $user->id)
            ->where('type', 'study_streak')
            ->get()
            ->filter(function($n) {
                $data = json_decode($n->data, true);
                return $data['streak_days'] == 60;
            })
            ->first();

        $this->assertTest(
            'Legendary streak should create special notification',
            $legendaryNotification !== null && str_contains($legendaryNotification->title, 'Legendary'),
            "No legendary streak notification found"
        );

        $this->assertTest(
            'Legendary notification should mention elite status',
            $legendaryNotification !== null && str_contains($legendaryNotification->message, 'elite'),
            "Legendary notification doesn't mention elite status"
        );

        echo "\n";
    }

    private function testProfilePageIntegration()
    {
        echo "👤 TEST 6: Profile Page Integration\n";
        echo "-" . str_repeat("-", 40) . "\n";

        $user = $this->createUser('profile.test@example.com', 'Profile Test User');

        // Create some quiz history
        for ($day = 6; $day >= 0; $day--) {
            $this->createQuizAttempt($user, now()->subDays($day)->startOfDay()->addHours(rand(9, 18)));
        }

        $streak = $user->updateStudyStreak();
        $user->refresh();

        $this->assertTest(
            'User model should have correct streak value',
            $user->study_streak_days === 7,
            "Expected: 7, Got: {$user->study_streak_days}"
        );

        // Test that streak is properly displayed in profile context
        $this->assertTest(
            'Streak should be positive for active user',
            $user->study_streak_days > 0,
            "Streak should be positive: {$user->study_streak_days}"
        );

        // Test XP calculation includes streak bonus
        $xp = $user->calculateXP();
        $expectedStreakBonus = $user->study_streak_days * 10;

        $this->assertTest(
            'XP calculation should include streak bonus',
            $xp >= $expectedStreakBonus,
            "XP ($xp) should include streak bonus ($expectedStreakBonus)"
        );

        // Test that recent notifications are available
        $recentNotifications = Notification::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        $this->assertTest(
            'Recent streak activity should generate notifications',
            $recentNotifications > 0,
            "No recent notifications found"
        );

        echo "\n";
    }

    private function createUser($email, $name)
    {
        // Clean up existing user
        User::where('email', $email)->delete();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'student',
            'study_streak_days' => 1,
            'correct_answers_count' => 0,
            'total_questions_attempted' => 0,
            'xp' => 0
        ]);

        $this->users[] = $user;
        return $user;
    }

    private function createQuizAttempt($user, $date)
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
            'user_id' => $user->id,
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
        echo "📊 PRODUCTION SCENARIO TEST RESULTS\n";
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
            echo "🎉 ALL PRODUCTION SCENARIO TESTS PASSED!\n";
            echo "🚀 System is ready for production deployment!\n";
            echo "📈 All user journeys are properly supported!\n";
            echo "🔔 Notification system provides excellent user experience!\n";
        } else {
            echo "⚠️  Some production scenarios failed. Please review before deployment.\n";
        }

        echo "\n" . "=" . str_repeat("=", 60) . "\n";
    }

    private function cleanup()
    {
        echo "🧹 Cleaning up test data...\n";
        
        foreach ($this->users as $user) {
            QuizAttempt::where('user_id', $user->id)->delete();
            Notification::where('user_id', $user->id)->delete();
            $user->delete();
        }
        
        echo "✅ Cleanup completed!\n";
    }
}

// Run the production scenario test suite
$tester = new ProductionStreakScenarioTester();
$tester->runAllTests();