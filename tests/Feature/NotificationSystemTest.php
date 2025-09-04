<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $course;
    protected $topic;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@test.com'
        ]);

        $this->course = Course::factory()->create([
            'name' => 'Test Course',
            'price' => 0
        ]);

        $this->topic = Topic::factory()->create([
            'name' => 'Test Topic',
            'course_id' => $this->course->id
        ]);

        // Clear any existing notifications and duplicate prevention cache before each test
        \Illuminate\Support\Facades\Cache::forget("user_notifications_{$this->user->id}");
        \Illuminate\Support\Facades\Cache::forget("user_unread_count_{$this->user->id}");

        // Clear duplicate prevention cache for all possible notification types
        $duplicateTypes = [
            'random_quiz_completed', 'random_quiz_perfect', 'tutor_quiz_completed',
            'tutor_perfect', 'quiz_completed', 'perfect_score', 'assignment_completed',
            'assignment_perfect', 'quiz_completed', 'test_notification'
        ];

        foreach ($duplicateTypes as $type) {
            \Illuminate\Support\Facades\Cache::forget("user_notif_dup_{$this->user->id}_" . md5("{$type}:" . ($type === 'test_notification' ? 'Test Title' : 'Test Title')));
        }
    }

    /** @test */
    public function random_quiz_completion_triggers_correct_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 8,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'quiz_type' => 'random'
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('random_quiz_completed', $notification['type']);
        $this->assertEquals('Random Quiz Completed', $notification['title']);
        $this->assertStringContainsString('80%', $notification['message']);
        $this->assertStringContainsString('random quiz', strtolower($notification['message']));
        $this->assertEquals('fas fa-dice', $notification['icon']);
        $this->assertEquals('normal', $notification['priority']);
    }

    /** @test */
    public function random_quiz_perfect_score_triggers_perfect_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 10,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'quiz_type' => 'random'
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('random_quiz_perfect', $notification['type']);
        $this->assertEquals('Perfect Random Quiz!', $notification['title']);
        $this->assertStringContainsString('100%', $notification['message']);
        $this->assertEquals('fas fa-dice', $notification['icon']);
        $this->assertEquals('perfect', $notification['priority']);
    }

    /** @test */
    public function tutor_mode_completion_triggers_correct_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 9,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'mode' => 'tutor'
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('tutor_quiz_completed', $notification['type']);
        $this->assertEquals('Tutor Quiz Completed', $notification['title']);
        $this->assertStringContainsString('90%', $notification['message']);
        $this->assertStringContainsString('tutor mode', strtolower($notification['message']));
        $this->assertEquals('fas fa-star', $notification['icon']);
        $this->assertEquals('normal', $notification['priority']);
    }

    /** @test */
    public function tutor_mode_perfect_score_triggers_perfect_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 10,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'mode' => 'tutor'
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('tutor_perfect', $notification['type']);
        $this->assertEquals('Perfect Tutor Score', $notification['title']);
        $this->assertStringContainsString('100%', $notification['message']);
        $this->assertEquals('fas fa-star', $notification['icon']);
        $this->assertEquals('perfect', $notification['priority']);
    }

    /** @test */
    public function regular_quiz_completion_triggers_correct_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 7,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('quiz_completed', $notification['type']);
        $this->assertEquals('Quiz Completed', $notification['title']);
        $this->assertStringContainsString('70%', $notification['message']);
        $this->assertEquals('fas fa-check-circle', $notification['icon']);
        $this->assertEquals('normal', $notification['priority']);
    }

    /** @test */
    public function regular_quiz_perfect_score_triggers_perfect_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 10,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('perfect_score', $notification['type']);
        $this->assertEquals('Perfect Score', $notification['title']);
        $this->assertStringContainsString('100%', $notification['message']);
        $this->assertEquals('fas fa-star', $notification['icon']);
        $this->assertEquals('perfect', $notification['priority']);
    }

    /** @test */
    public function assignment_completion_triggers_correct_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 19,
            'total_questions' => 20,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'assignment_completed' => true,
            'assignment_title' => 'Test Assignment'
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('assignment_completed', $notification['type']);
        $this->assertEquals('Assignment Completed', $notification['title']);
        $this->assertStringContainsString('95%', $notification['message']);
        $this->assertStringContainsString('Test Assignment', $notification['message']);
        $this->assertEquals('fas fa-clipboard-check', $notification['icon']);
        $this->assertEquals('normal', $notification['priority']);
    }

    /** @test */
    public function mistake_review_completion_triggers_correct_notification()
    {
        // Act
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 17,
            'total_questions' => 20,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'mode' => 'mistake_review'
        ]);

        // Assert
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('quiz_completed', $notification['type']);
        $this->assertEquals('Mistake Review Completed', $notification['title']);
        $this->assertStringContainsString('85%', $notification['message']);
        $this->assertStringContainsString('mistake review', strtolower($notification['message']));
        $this->assertEquals('fas fa-check-circle', $notification['icon']);
        $this->assertEquals('normal', $notification['priority']);
    }

    /** @test */
    public function system_handles_invalid_data_gracefully()
    {
        // Clear cache first
        \Illuminate\Support\Facades\Cache::forget("user_notifications_{$this->user->id}");
        \Illuminate\Support\Facades\Cache::forget("user_unread_count_{$this->user->id}");

        // Act - Pass invalid data
        NotificationTriggerService::onQuizCompleted($this->user, [
            'score' => 'invalid',
            'total_questions' => 1,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name
        ]);

        // Assert - Should still create a fallback notification
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertEquals('quiz_completed', $notification['type']);
        $this->assertStringContainsString('completed a quiz', strtolower($notification['message']));
    }

    /** @test */
    public function debug_notification_creation()
    {
        // Debug test to see what's happening
        $cacheKey = "user_notifications_{$this->user->id}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        // Create a simple notification directly
        $result = UniversalNotificationService::create(
            $this->user,
            'test_notification',
            'Test Title',
            'Test message',
            [],
            'normal'
        );

        $this->assertNotNull($result);
        $this->assertEquals('test_notification', $result['type']);

        // Check if it's in cache
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications);
    }

    /** @test */
    public function debug_notification_trigger_service()
    {
        // Debug the NotificationTriggerService specifically
        $cacheKey = "user_notifications_{$this->user->id}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        // Test the service method directly
        $quizData = [
            'score' => 8,
            'total_questions' => 10,
            'course_name' => $this->course->name,
            'topic_name' => $this->topic->name,
            'quiz_type' => 'random'
        ];

        // Call the service
        NotificationTriggerService::onQuizCompleted($this->user, $quizData);

        // Check if notification was created
        $notifications = UniversalNotificationService::getNotifications($this->user);
        $this->assertCount(1, $notifications, 'Notification should be created');

        $notification = $notifications->first();
        $this->assertEquals('random_quiz_completed', $notification['type'], 'Should detect random quiz type');
    }

    /** @test */
    public function activity_type_detection_works_correctly()
    {
        // Test different activity types are detected correctly
        $testCases = [
            [['assignment_completed' => true], 'assignment_completion'],
            [['quiz_type' => 'random'], 'random_quiz'],
            [['mode' => 'tutor'], 'tutor_quiz'],
            [['mode' => 'mistake_review'], 'mistake_review'],
            [[], 'regular_quiz'],
        ];

        foreach ($testCases as [$data, $expectedType]) {
            $fullData = array_merge([
                'score' => 8,
                'total_questions' => 10,
                'course_name' => $this->course->name,
                'topic_name' => $this->topic->name
            ], $data);

            $activityType = $this->invokePrivateMethod(
                NotificationTriggerService::class,
                'detectActivityType',
                [$fullData]
            );

            $this->assertEquals($expectedType, $activityType, "Failed for data: " . json_encode($data));
        }
    }

    /** @test */
    public function notification_priority_levels_are_correct()
    {
        $testCases = [
            [10, 10, 'perfect'], // 100% score
            [8, 10, 'normal'],   // 80% score
            [5, 10, 'normal'],   // 50% score
        ];

        foreach ($testCases as $index => [$score, $total, $expectedPriority]) {
            // Clear cache for each test case
            \Illuminate\Support\Facades\Cache::forget("user_notifications_{$this->user->id}");
            \Illuminate\Support\Facades\Cache::forget("user_unread_count_{$this->user->id}");

            NotificationTriggerService::onQuizCompleted($this->user, [
                'score' => $score,
                'total_questions' => $total,
                'course_name' => $this->course->name,
                'topic_name' => $this->topic->name
            ]);

            $notifications = UniversalNotificationService::getNotifications($this->user);
            $notification = $notifications->first();

            $this->assertNotNull($notification, "Notification should be created for test case {$index}");
            $this->assertEquals($expectedPriority, $notification['priority'],
                "Priority should be {$expectedPriority} for score {$score}/{$total}");
        }
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($className, $methodName, array $parameters = [])
    {
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }
}
