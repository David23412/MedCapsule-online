<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Notification;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Mistake;
use App\Services\NotificationService;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class ComprehensiveNotificationTester
{
    private $notificationService;
    private $testResults = [];
    private $testUser;
    private $tutorUser;
    private $testCourse;
    private $testTopic;

    public function __construct()
    {
        $this->notificationService = app(NotificationService::class);
    }

    public function runAllTests()
    {
        echo "🔔 COMPREHENSIVE NOTIFICATION SYSTEM TESTING 🔔\n";
        echo "=" . str_repeat("=", 70) . "\n\n";

        // Create test data
        $this->createTestData();

        // Test all notification scenarios
        $this->testStudyStreakNotifications();
        $this->testTopicMasteryNotifications();
        $this->testCourseProgressNotifications();
        $this->testWeakTopicsNotifications();
        $this->testMistakeReviewNotifications();
        $this->testLevelUpNotifications();
        $this->testMilestoneNotifications();
        $this->testAssignmentNotifications();
        $this->testPerformanceNotifications();
        $this->testLeaderboardNotifications();
        $this->testPaymentNotifications();
        $this->testReviewReminderNotifications();
        $this->testFailedTopicNotifications();
        $this->testCorrectAnswersMilestoneNotifications();
        $this->testSkippedQuestionReminders();
        $this->testTopicUnlockNotifications();
        $this->testCourseMasteryNotifications();

        // Display results
        $this->displayResults();

        // Cleanup
        $this->cleanup();
    }

    private function createTestData()
    {
        echo "📝 Creating test data...\n";
        
        // Clean up existing test data
        User::where('email', 'like', '%notification.test%')->delete();
        
        // Create test users
        $this->testUser = User::create([
            'name' => 'Notification Test Student',
            'email' => 'student.notification.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'study_streak_days' => 1,
            'correct_answers_count' => 50,
            'total_questions_attempted' => 100,
            'xp' => 500
        ]);

        $this->tutorUser = User::create([
            'name' => 'Notification Test Tutor',
            'email' => 'tutor.notification.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'tutor',
            'study_streak_days' => 1,
            'correct_answers_count' => 0,
            'total_questions_attempted' => 0,
            'xp' => 0
        ]);

        // Get or create test course and topic
        $this->testCourse = Course::first() ?? Course::create([
            'name' => 'Test Course',
            'description' => 'Test course for notifications',
            'price' => 0
        ]);

        $this->testTopic = Topic::first() ?? Topic::create([
            'name' => 'Test Topic',
            'course_id' => $this->testCourse->id
        ]);

        echo "✅ Test data created\n\n";
    }

    private function testStudyStreakNotifications()
    {
        echo "🔥 TEST 1: Study Streak Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $streakMilestones = [1, 2, 3, 7, 14, 30, 60, 100];

        foreach ($streakMilestones as $streak) {
            $notification = $this->notificationService->createStudyStreakNotification($this->testUser, $streak);
            
            $this->assertTest(
                "Streak $streak notification created",
                $notification !== null,
                "Failed to create streak $streak notification"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Streak $streak notification has correct data",
                    $data['streak_days'] === $streak,
                    "Expected streak_days: $streak, Got: " . ($data['streak_days'] ?? 'null')
                );

                $this->assertTest(
                    "Streak $streak notification has appropriate title",
                    !empty($notification->title),
                    "Empty title for streak $streak"
                );

                $this->assertTest(
                    "Streak $streak notification has encouraging message",
                    !empty($notification->message) && strlen($notification->message) > 10,
                    "Message too short or empty for streak $streak"
                );
            }
        }

        // Test streak reset notification
        $resetNotification = $this->notificationService->createStreakResetNotification($this->testUser);
        
        $this->assertTest(
            'Streak reset notification created',
            $resetNotification !== null && $resetNotification->type === 'streak_reset',
            "Failed to create streak reset notification"
        );

        echo "\n";
    }

    private function testTopicMasteryNotifications()
    {
        echo "🎯 TEST 2: Topic Mastery Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $topicName = $this->testTopic->name;
        $notification = $this->notificationService->createTopicMasteryNotification($this->testUser, $topicName);

        $this->assertTest(
            'Topic mastery notification created',
            $notification !== null && $notification->type === 'topic_mastery',
            "Failed to create topic mastery notification"
        );

        if ($notification) {
            $this->assertTest(
                'Topic mastery notification mentions topic name',
                str_contains($notification->message, $topicName),
                "Notification doesn't mention topic name: {$notification->message}"
            );

            $this->assertTest(
                'Topic mastery notification is encouraging',
                str_contains($notification->message, 'mastered') || str_contains($notification->title, 'Mastered'),
                "Notification not encouraging: {$notification->message}"
            );
        }

        echo "\n";
    }

    private function testCourseProgressNotifications()
    {
        echo "📚 TEST 3: Course Progress Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $courseName = $this->testCourse->name;
        $progressPercentages = [25, 50, 75, 100];

        foreach ($progressPercentages as $progress) {
            $notification = $this->notificationService->createCourseProgressNotification(
                $this->testUser, 
                $courseName, 
                $progress
            );

            $this->assertTest(
                "Course progress $progress% notification created",
                $notification !== null && $notification->type === 'course_progress',
                "Failed to create course progress $progress% notification"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Course progress notification has correct percentage",
                    $data['progress_percentage'] === $progress,
                    "Expected: $progress%, Got: " . ($data['progress_percentage'] ?? 'null')
                );

                $this->assertTest(
                    "Course progress notification mentions course name",
                    str_contains($notification->message, $courseName),
                    "Notification doesn't mention course: {$notification->message}"
                );
            }
        }

        // Test course welcome notification
        $welcomeNotification = $this->notificationService->createCourseWelcomeNotification($this->testUser, $courseName);
        
        $this->assertTest(
            'Course welcome notification created',
            $welcomeNotification !== null && $welcomeNotification->type === 'course_welcome',
            "Failed to create course welcome notification"
        );

        echo "\n";
    }

    private function testWeakTopicsNotifications()
    {
        echo "⚠️ TEST 4: Weak Topics Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $weakTopics = ['Anatomy', 'Physiology', 'Pharmacology'];
        $notification = $this->notificationService->createWeakTopicsNotification($this->testUser, $weakTopics);

        $this->assertTest(
            'Weak topics notification created',
            $notification !== null && $notification->type === 'weak_topics',
            "Failed to create weak topics notification"
        );

        if ($notification) {
            $data = json_decode($notification->data, true);
            $this->assertTest(
                'Weak topics notification has correct topics',
                $data['topics'] === $weakTopics,
                "Topics data mismatch"
            );

            $this->assertTest(
                'Weak topics notification mentions topic count',
                str_contains($notification->message, (string)count($weakTopics)),
                "Notification doesn't mention topic count: {$notification->message}"
            );
        }

        // Test single weak topic added notification
        $singleTopicNotification = $this->notificationService->createWeakTopicAddedNotification($this->testUser, 'Cardiology');
        
        $this->assertTest(
            'Single weak topic notification created',
            $singleTopicNotification !== null && $singleTopicNotification->type === 'weak_topic_added',
            "Failed to create single weak topic notification"
        );

        echo "\n";
    }

    private function testMistakeReviewNotifications()
    {
        echo "🔄 TEST 5: Mistake Review Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $mistakeCounts = [1, 5, 10, 25];

        foreach ($mistakeCounts as $count) {
            $notification = $this->notificationService->createReviewMistakesNotification($this->testUser, $count);

            $this->assertTest(
                "Mistake review notification for $count mistakes created",
                $notification !== null && $notification->type === 'review_mistakes',
                "Failed to create mistake review notification for $count mistakes"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Mistake review notification has correct count",
                    $data['mistake_count'] === $count,
                    "Expected: $count, Got: " . ($data['mistake_count'] ?? 'null')
                );

                $expectedText = $count === 1 ? '1 mistake' : "$count mistakes";
                $this->assertTest(
                    "Mistake review notification mentions correct count",
                    str_contains($notification->message, $expectedText),
                    "Notification doesn't mention correct count: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testLevelUpNotifications()
    {
        echo "⬆️ TEST 6: Level Up Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $levels = [2, 5, 10, 25, 50];

        foreach ($levels as $level) {
            $xpNeeded = 1000 - ($this->testUser->xp % 1000);
            $notification = $this->notificationService->createLevelUpNotification($this->testUser, $level, $xpNeeded);

            $this->assertTest(
                "Level $level notification created",
                $notification !== null && $notification->type === 'level_up',
                "Failed to create level $level notification"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Level up notification has correct level",
                    $data['new_level'] === $level,
                    "Expected level: $level, Got: " . ($data['new_level'] ?? 'null')
                );

                $this->assertTest(
                    "Level up notification mentions level",
                    str_contains($notification->message, "level $level"),
                    "Notification doesn't mention level: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testMilestoneNotifications()
    {
        echo "🏆 TEST 7: Milestone Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $milestones = [
            'First Quiz Completed' => 'You completed your first quiz! Great start!',
            '100 Questions Answered' => 'You have answered 100 questions correctly!',
            'Week of Consistent Study' => 'You studied consistently for a full week!',
            'Perfect Quiz Score' => 'You achieved a perfect score on a quiz!'
        ];

        foreach ($milestones as $achievement => $description) {
            $notification = $this->notificationService->createMilestoneNotification($this->testUser, $achievement, $description);

            $this->assertTest(
                "Milestone '$achievement' notification created",
                $notification !== null && $notification->type === 'milestone',
                "Failed to create milestone notification for '$achievement'"
            );

            if ($notification) {
                $this->assertTest(
                    "Milestone notification has correct title",
                    $notification->title === $achievement,
                    "Expected title: '$achievement', Got: '{$notification->title}'"
                );

                $this->assertTest(
                    "Milestone notification has correct message",
                    $notification->message === $description,
                    "Message mismatch for milestone '$achievement'"
                );
            }
        }

        echo "\n";
    }

    private function testAssignmentNotifications()
    {
        echo "📋 TEST 8: Assignment Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        // Skip if Assignment model doesn't exist
        if (!class_exists('App\Models\Assignment')) {
            echo "  ⏭️ Skipping assignment tests - Assignment model not found\n\n";
            return;
        }

        // Test assignment deadline change notification
        $oldDeadline = now()->addDays(3);
        $newDeadline = now()->addDays(5);
        
        // Create mock assignment data for testing
        $assignmentData = (object)[
            'id' => 1,
            'title' => 'Test Assignment',
            'student_id' => $this->testUser->id,
            'tutor_id' => $this->tutorUser->id,
            'deadline' => $newDeadline,
            'student' => $this->testUser,
            'tutor' => $this->tutorUser,
            'topic' => $this->testTopic,
            'course' => $this->testCourse
        ];

        // Test deadline change notification creation directly
        $deadlineNotification = $this->notificationService->createAssignmentNotification(
            $this->testUser->id,
            'assignment_deadline_changed',
            'Assignment Deadline Updated',
            "The deadline for 'Test Assignment' has been changed from {$oldDeadline->format('M j, Y g:i A')} to {$newDeadline->format('M j, Y g:i A')}",
            [
                'assignment_id' => 1,
                'old_deadline' => $oldDeadline->toISOString(),
                'new_deadline' => $newDeadline->toISOString()
            ]
        );

        $this->assertTest(
            'Assignment deadline change notification created',
            $deadlineNotification !== null && $deadlineNotification->type === 'assignment_deadline_changed',
            "Failed to create assignment deadline change notification"
        );

        // Test assignment reminder notification
        $reminderNotification = $this->notificationService->createAssignmentNotification(
            $this->testUser->id,
            'assignment_reminder',
            'Assignment Due Soon!',
            "Reminder: 'Test Assignment' is due in 2 days",
            ['assignment_id' => 1]
        );

        $this->assertTest(
            'Assignment reminder notification created',
            $reminderNotification !== null && $reminderNotification->type === 'assignment_reminder',
            "Failed to create assignment reminder notification"
        );

        // Test assignment overdue notification
        $overdueNotification = $this->notificationService->createAssignmentNotification(
            $this->testUser->id,
            'assignment_overdue',
            'Assignment Overdue',
            "'Test Assignment' is now overdue. Please complete it as soon as possible.",
            ['assignment_id' => 1]
        );

        $this->assertTest(
            'Assignment overdue notification created',
            $overdueNotification !== null && $overdueNotification->type === 'assignment_overdue',
            "Failed to create assignment overdue notification"
        );

        echo "\n";
    }

    private function testPerformanceNotifications()
    {
        echo "📊 TEST 9: Performance Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $performanceScenarios = [
            ['accuracy' => 95.5, 'timeframe' => 'week', 'expected_tone' => 'Excellent'],
            ['accuracy' => 85.0, 'timeframe' => 'month', 'expected_tone' => 'Excellent'],
            ['accuracy' => 75.0, 'timeframe' => 'week', 'expected_tone' => 'Keep practicing'],
            ['accuracy' => 60.0, 'timeframe' => 'month', 'expected_tone' => 'Keep practicing']
        ];

        foreach ($performanceScenarios as $scenario) {
            $notification = $this->notificationService->createPerformanceNotification(
                $this->testUser,
                $scenario['accuracy'],
                $scenario['timeframe']
            );

            $this->assertTest(
                "Performance notification for {$scenario['accuracy']}% accuracy created",
                $notification !== null && $notification->type === 'performance',
                "Failed to create performance notification for {$scenario['accuracy']}%"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Performance notification has correct accuracy",
                    abs($data['accuracy'] - $scenario['accuracy']) < 0.01,
                    "Expected accuracy: {$scenario['accuracy']}, Got: " . ($data['accuracy'] ?? 'null')
                );

                $this->assertTest(
                    "Performance notification mentions timeframe",
                    str_contains($notification->message, $scenario['timeframe']),
                    "Notification doesn't mention timeframe: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testLeaderboardNotifications()
    {
        echo "🏅 TEST 10: Leaderboard Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $rankingScenarios = [
            ['old_rank' => 10, 'new_rank' => 5, 'expected_tone' => 'moved up'],
            ['old_rank' => 3, 'new_rank' => 1, 'expected_tone' => 'moved up'],
            ['old_rank' => 5, 'new_rank' => 8, 'expected_tone' => 'changed'],
            ['old_rank' => 1, 'new_rank' => 2, 'expected_tone' => 'changed']
        ];

        foreach ($rankingScenarios as $scenario) {
            $notification = $this->notificationService->createLeaderboardRankNotification(
                $this->testUser,
                $scenario['old_rank'],
                $scenario['new_rank']
            );

            $this->assertTest(
                "Leaderboard notification for rank change {$scenario['old_rank']} → {$scenario['new_rank']} created",
                $notification !== null && $notification->type === 'leaderboard_rank',
                "Failed to create leaderboard notification"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Leaderboard notification has correct ranks",
                    $data['old_rank'] === $scenario['old_rank'] && $data['new_rank'] === $scenario['new_rank'],
                    "Rank data mismatch"
                );

                $this->assertTest(
                    "Leaderboard notification mentions ranks",
                    str_contains($notification->message, "#{$scenario['old_rank']}") && 
                    str_contains($notification->message, "#{$scenario['new_rank']}"),
                    "Notification doesn't mention ranks: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testPaymentNotifications()
    {
        echo "💳 TEST 11: Payment Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $courseName = $this->testCourse->name;
        $amount = 99.99;

        $notification = $this->notificationService->createPaymentSuccessNotification(
            $this->testUser,
            $courseName,
            $amount
        );

        $this->assertTest(
            'Payment success notification created',
            $notification !== null && $notification->type === 'payment_success',
            "Failed to create payment success notification"
        );

        if ($notification) {
            $data = json_decode($notification->data, true);
            $this->assertTest(
                'Payment notification has correct amount',
                $data['amount'] === $amount,
                "Expected amount: $amount, Got: " . ($data['amount'] ?? 'null')
            );

            $this->assertTest(
                'Payment notification mentions course',
                str_contains($notification->message, $courseName),
                "Notification doesn't mention course: {$notification->message}"
            );

            $this->assertTest(
                'Payment notification mentions amount',
                str_contains($notification->message, (string)$amount),
                "Notification doesn't mention amount: {$notification->message}"
            );
        }

        echo "\n";
    }

    private function testReviewReminderNotifications()
    {
        echo "🔔 TEST 12: Review Reminder Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $topicsToReview = ['Cardiology', 'Neurology', 'Endocrinology'];
        $notification = $this->notificationService->createReviewReminderNotification($this->testUser, $topicsToReview);

        $this->assertTest(
            'Review reminder notification created',
            $notification !== null && $notification->type === 'review_reminder',
            "Failed to create review reminder notification"
        );

        if ($notification) {
            $data = json_decode($notification->data, true);
            $this->assertTest(
                'Review reminder has correct topics',
                $data['topics'] === $topicsToReview,
                "Topics data mismatch"
            );

            $this->assertTest(
                'Review reminder mentions topic count',
                str_contains($notification->message, (string)count($topicsToReview)),
                "Notification doesn't mention topic count: {$notification->message}"
            );
        }

        echo "\n";
    }

    private function testFailedTopicNotifications()
    {
        echo "❌ TEST 13: Failed Topic Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $topicName = $this->testTopic->name;
        $topicId = $this->testTopic->id;
        $scores = [45.0, 30.0, 60.0, 25.0];

        foreach ($scores as $score) {
            $notification = $this->notificationService->createFailedTopicNotification(
                $this->testUser,
                $topicName,
                $topicId,
                $score
            );

            $this->assertTest(
                "Failed topic notification for {$score}% score created",
                $notification !== null && $notification->type === 'failed_topic',
                "Failed to create failed topic notification for {$score}%"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Failed topic notification has correct score",
                    abs($data['score'] - $score) < 0.01,
                    "Expected score: $score, Got: " . ($data['score'] ?? 'null')
                );

                $this->assertTest(
                    "Failed topic notification mentions topic",
                    str_contains($notification->message, $topicName),
                    "Notification doesn't mention topic: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testCorrectAnswersMilestoneNotifications()
    {
        echo "✅ TEST 14: Correct Answers Milestone Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $milestones = [100, 200, 500, 1000];

        foreach ($milestones as $milestone) {
            $notification = $this->notificationService->createCorrectAnswersMilestoneNotification(
                $this->testUser,
                $milestone
            );

            $this->assertTest(
                "Correct answers milestone $milestone notification created",
                $notification !== null && $notification->type === 'correct_answers_milestone',
                "Failed to create correct answers milestone $milestone notification"
            );

            if ($notification) {
                $data = json_decode($notification->data, true);
                $this->assertTest(
                    "Milestone notification has correct count",
                    $data['milestone'] === $milestone,
                    "Expected milestone: $milestone, Got: " . ($data['milestone'] ?? 'null')
                );

                $this->assertTest(
                    "Milestone notification mentions achievement",
                    str_contains($notification->message, (string)$milestone),
                    "Notification doesn't mention milestone: {$notification->message}"
                );
            }
        }

        echo "\n";
    }

    private function testSkippedQuestionReminders()
    {
        echo "⏭️ TEST 15: Skipped Question Reminders\n";
        echo "-" . str_repeat("-", 50) . "\n";

        // Create a custom notification for skipped questions
        $skippedCount = 5;
        $notification = $this->notificationService->createNotification(
            $this->testUser->id,
            'skipped_questions_reminder',
            'Skipped Questions Reminder',
            "You have $skippedCount skipped questions. Consider reviewing them to improve your understanding.",
            ['skipped_count' => $skippedCount]
        );

        $this->assertTest(
            'Skipped questions reminder notification created',
            $notification !== null && $notification->type === 'skipped_questions_reminder',
            "Failed to create skipped questions reminder notification"
        );

        if ($notification) {
            $data = json_decode($notification->data, true);
            $this->assertTest(
                'Skipped questions notification has correct count',
                $data['skipped_count'] === $skippedCount,
                "Expected count: $skippedCount, Got: " . ($data['skipped_count'] ?? 'null')
            );
        }

        echo "\n";
    }

    private function testTopicUnlockNotifications()
    {
        echo "🔓 TEST 16: Topic Unlock Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $topicName = $this->testTopic->name;
        $tutorName = $this->tutorUser->name;

        $notification = $this->notificationService->createNotification(
            $this->testUser->id,
            'topic_unlocked',
            'New Topic Unlocked!',
            "Your tutor $tutorName has unlocked the topic '$topicName' for you. You can now access new materials and quizzes!",
            [
                'topic_name' => $topicName,
                'tutor_name' => $tutorName,
                'topic_id' => $this->testTopic->id
            ]
        );

        $this->assertTest(
            'Topic unlock notification created',
            $notification !== null && $notification->type === 'topic_unlocked',
            "Failed to create topic unlock notification"
        );

        if ($notification) {
            $this->assertTest(
                'Topic unlock notification mentions topic',
                str_contains($notification->message, $topicName),
                "Notification doesn't mention topic: {$notification->message}"
            );

            $this->assertTest(
                'Topic unlock notification mentions tutor',
                str_contains($notification->message, $tutorName),
                "Notification doesn't mention tutor: {$notification->message}"
            );
        }

        echo "\n";
    }

    private function testCourseMasteryNotifications()
    {
        echo "🎓 TEST 17: Course Mastery Notifications\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $courseName = $this->testCourse->name;

        $notification = $this->notificationService->createNotification(
            $this->testUser->id,
            'course_mastery',
            'Course Mastered!',
            "Congratulations! You have mastered the entire '$courseName' course. You've completed all topics and achieved excellent performance across all areas!",
            [
                'course_name' => $courseName,
                'course_id' => $this->testCourse->id,
                'completion_date' => now()->toISOString()
            ]
        );

        $this->assertTest(
            'Course mastery notification created',
            $notification !== null && $notification->type === 'course_mastery',
            "Failed to create course mastery notification"
        );

        if ($notification) {
            $this->assertTest(
                'Course mastery notification mentions course',
                str_contains($notification->message, $courseName),
                "Notification doesn't mention course: {$notification->message}"
            );

            $this->assertTest(
                'Course mastery notification is celebratory',
                str_contains($notification->message, 'Congratulations') || str_contains($notification->title, 'Mastered'),
                "Notification not celebratory enough: {$notification->message}"
            );
        }

        echo "\n";
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
        echo "\n" . "=" . str_repeat("=", 70) . "\n";
        echo "📊 COMPREHENSIVE NOTIFICATION TEST RESULTS\n";
        echo "=" . str_repeat("=", 70) . "\n\n";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($test) => $test['passed']));
        $failedTests = $totalTests - $passedTests;

        echo "Total Tests: $totalTests\n";
        echo "✅ Passed: $passedTests\n";
        echo "❌ Failed: $failedTests\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

        // Group results by test category
        $categories = [
            'Study Streak' => 'Streak',
            'Topic Mastery' => 'Topic mastery',
            'Course Progress' => 'Course progress',
            'Weak Topics' => 'Weak topic',
            'Mistake Review' => 'Mistake review',
            'Level Up' => 'Level',
            'Milestone' => 'Milestone',
            'Assignment' => 'Assignment',
            'Performance' => 'Performance',
            'Leaderboard' => 'Leaderboard',
            'Payment' => 'Payment',
            'Review Reminder' => 'Review reminder',
            'Failed Topic' => 'Failed topic',
            'Correct Answers' => 'Correct answers',
            'Skipped Questions' => 'Skipped questions',
            'Topic Unlock' => 'Topic unlock',
            'Course Mastery' => 'Course mastery'
        ];

        echo "📋 RESULTS BY CATEGORY:\n";
        foreach ($categories as $categoryName => $searchTerm) {
            $categoryTests = array_filter($this->testResults, fn($test) => 
                str_contains($test['description'], $searchTerm)
            );
            
            if (!empty($categoryTests)) {
                $categoryPassed = count(array_filter($categoryTests, fn($test) => $test['passed']));
                $categoryTotal = count($categoryTests);
                $categoryRate = round(($categoryPassed / $categoryTotal) * 100, 2);
                
                $status = $categoryRate === 100.0 ? '✅' : ($categoryRate >= 80 ? '⚠️' : '❌');
                echo "$status $categoryName: $categoryRate% ($categoryPassed/$categoryTotal)\n";
            }
        }

        if ($failedTests > 0) {
            echo "\n🚨 FAILED TESTS:\n";
            echo "-" . str_repeat("-", 50) . "\n";
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
            echo "🎉 ALL NOTIFICATION TESTS PASSED!\n";
            echo "🔔 Complete notification system is working perfectly!\n";
            echo "📱 All user scenarios are covered with appropriate notifications!\n";
            echo "🎯 Smart, contextual messaging enhances user experience!\n";
            echo "🚀 System is ready for production deployment!\n";
        } else {
            $successRate = round(($passedTests / $totalTests) * 100, 2);
            if ($successRate >= 90) {
                echo "🎯 EXCELLENT! Notification system is highly reliable!\n";
                echo "🔔 Minor issues detected but core functionality is solid!\n";
                echo "✅ Ready for production with monitoring!\n";
            } else if ($successRate >= 80) {
                echo "⚠️ GOOD! Most notification scenarios working correctly!\n";
                echo "🔧 Some improvements needed before production!\n";
            } else {
                echo "❌ NEEDS WORK! Significant notification issues detected!\n";
                echo "🛠️ Address failing tests before deployment!\n";
            }
        }

        echo "\n" . "=" . str_repeat("=", 70) . "\n";
    }

    private function cleanup()
    {
        echo "🧹 Cleaning up test data...\n";
        
        // Clean up notifications
        Notification::where('user_id', $this->testUser->id)->delete();
        Notification::where('user_id', $this->tutorUser->id)->delete();
        
        // Clean up users
        $this->testUser->delete();
        $this->tutorUser->delete();
        
        echo "✅ Cleanup completed!\n";
    }
}

// Run the comprehensive notification test suite
$tester = new ComprehensiveNotificationTester();
$tester->runAllTests();