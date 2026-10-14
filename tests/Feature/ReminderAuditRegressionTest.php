<?php

namespace Tests\Feature;

use App\Events\NotificationCreated;
use App\Http\Controllers\Topic\TopicActionController;
use App\Models\Assignment;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PerformanceTrackingService;
use App\Services\UniversalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ReminderAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->freezeTime();
    }

    public function test_existing_reminder_schedule_is_registered(): void
    {
        $commands = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->pluck('command')->filter()->implode("\n");
        $this->assertStringContainsString('assignments:send-reminders', $commands);
        $this->assertStringContainsString('notifications:send-reminders', $commands);
        $this->assertStringContainsString('db:prune-operational-data', $commands);
    }

    public function test_assignment_reminders_use_assigned_users_and_do_not_collide_between_assignments(): void
    {
        $student = User::factory()->student()->create();
        $assignments = Assignment::factory()->count(2)->create(['deadline' => now()->addHours(2)]);
        foreach ($assignments as $assignment) {
            $assignment->assignedUsers()->attach($student->id);
        }

        $this->artisan('assignments:send-reminders')->assertSuccessful();
        $notifications = UniversalNotificationService::getNotifications($student);
        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing($assignments->modelKeys(), $notifications->pluck('data.assignment_id')->all());
        $this->assertSame(['assignment_due_3h'], $notifications->pluck('type')->unique()->values()->all());
        $this->artisan('assignments:send-reminders')->assertSuccessful();
        $this->assertCount(2, UniversalNotificationService::getNotifications($student));
    }

    public function test_assignment_reminders_respect_extensions_and_completed_attempts(): void
    {
        $completed = User::factory()->student()->create();
        $extended = User::factory()->student()->create();
        $waiting = User::factory()->student()->create();
        $assignment = Assignment::factory()->create(['deadline' => now()->addMinutes(40)]);
        $assignment->assignedUsers()->attach($completed->id);
        $assignment->assignedUsers()->attach($extended->id, ['extended_deadline' => now()->addDays(2)]);
        $assignment->assignedUsers()->attach($waiting->id);
        QuizAttempt::factory()->assignment()->create(['user_id' => $completed->id, 'assignment_id' => $assignment->id]);

        $this->artisan('assignments:send-reminders')->assertSuccessful();
        $this->assertCount(0, UniversalNotificationService::getNotifications($completed));
        $this->assertCount(0, UniversalNotificationService::getNotifications($extended));
        $this->assertSame('assignment_due_1h', UniversalNotificationService::getNotifications($waiting)->first()['type']);
    }

    public function test_overdue_reminder_is_not_sent_again_after_short_notification_deduplication_expires(): void
    {
        $student = User::factory()->student()->create();
        $assignment = Assignment::factory()->create(['deadline' => now()->subMinutes(10)]);
        $assignment->assignedUsers()->attach($student->id);
        $this->artisan('assignments:send-reminders')->assertSuccessful();
        $this->travel(2)->hours();
        $this->artisan('assignments:send-reminders')->assertSuccessful();
        $this->assertCount(1, UniversalNotificationService::getNotifications($student));
    }

    public function test_deadline_reminder_service_uses_cached_notifications_and_per_student_state(): void
    {
        $students = User::factory()->student()->count(2)->create();
        $assignment = Assignment::factory()->create(['deadline' => now()->addHours(20)]);
        $assignment->assignedUsers()->attach($students->modelKeys());
        $service = app(NotificationService::class);
        $this->assertSame(2, $service->sendDeadlineReminders());
        $this->assertSame(0, $service->sendDeadlineReminders());
        foreach ($students as $student) {
            $this->assertSame($assignment->id, UniversalNotificationService::getNotifications($student)->first()['data']['assignment_id']);
        }
    }

    public function test_study_reminders_query_the_current_progress_relationship(): void
    {
        $student = User::factory()->student()->create();
        $topic = Topic::factory()->create();
        $student->userTopicProgress()->create(['topic_id' => $topic->id, 'percentage_grade' => 40]);
        $tracking = Mockery::mock(PerformanceTrackingService::class);
        $tracking->shouldReceive('getReminderSnapshot')->once()->with(Mockery::on(fn ($user) => $user->id === $student->id))
            ->andReturn(['should_send_weak_topic_reminder' => false]);
        $this->app->instance(PerformanceTrackingService::class, $tracking);
        $this->artisan('notifications:send-reminders')->assertSuccessful();
    }

    public function test_topic_progress_update_uses_the_students_aggregate_attempt_score(): void
    {
        $student = User::factory()->student()->create();
        $topic = Topic::factory()->create();
        Question::factory()->count(4)->create(['topic_id' => $topic->id, 'source' => 'Lecture']);
        QuizAttempt::factory()->create(['user_id' => $student->id, 'topic_id' => $topic->id, 'source' => 'Lecture', 'score' => 3, 'total_questions' => 4, 'percentage_grade' => 75]);
        $this->actingAs($student);
        app(TopicActionController::class)->updateTopicAfterQuiz($topic->id);
        $this->assertDatabaseHas('user_topic_progress', ['user_id' => $student->id, 'topic_id' => $topic->id, 'percentage_grade' => 75]);
    }

    public function test_notification_event_accepts_the_current_cache_payload(): void
    {
        $event = new NotificationCreated(['id' => 'notification-1', 'type' => 'assignment_due_1h', 'title' => 'DUE IN 1 HOUR'], 7);
        $this->assertSame('notification-1', $event->broadcastWith()['notification_id']);
        $this->assertSame(7, $event->broadcastWith()['user_id']);
    }

    public function test_quiz_factories_produce_records_matching_the_schema(): void
    {
        foreach ([QuizAttempt::factory(), QuizAttempt::factory()->highScore(), QuizAttempt::factory()->lowScore(), QuizAttempt::factory()->assignment(), QuizAttempt::factory()->timedOut(), QuizAttempt::factory()->practice()] as $factory) {
            $attempt = $factory->create();
            $this->assertGreaterThan(0, $attempt->total_questions);
            $this->assertGreaterThanOrEqual(0, $attempt->score);
            $this->assertLessThanOrEqual($attempt->total_questions, $attempt->score);
            $this->assertEqualsWithDelta($attempt->score / $attempt->total_questions * 100, $attempt->percentage_grade, 0.01);
        }
    }
}
