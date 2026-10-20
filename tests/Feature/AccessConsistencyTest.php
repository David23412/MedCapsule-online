<?php

namespace Tests\Feature;

use App\Http\Controllers\TopicAccessController;
use App\Models\{Assignment, Course, Topic, User, Video};
use App\Services\QuizUltraConsolidatedCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB};
use Tests\TestCase;

class AccessConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $student->courses()->attach($course, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        return [$student, $course, $topic];
    }

    public function test_topic_permissions_ignore_old_cached_grants_and_denials(): void
    {
        [$student, $course, $topic] = $this->fixture();
        $key = QuizUltraConsolidatedCacheService::generateCacheKey('topic_access', [
            'topic_id' => $topic->id, 'user_id' => $student->id, 'timestamp' => now()->format('Y-m-d-H'),
        ]);
        $controller = app(TopicAccessController::class);
        Cache::put($key, ['can_access' => false], 3600);
        $this->assertTrue($controller->checkAccess($topic, $student)['can_access']);
        Cache::put($key, ['can_access' => true], 3600);
        $student->courses()->updateExistingPivot($course, ['is_enrolled' => false]);
        $this->assertFalse($controller->checkAccess($topic, $student->fresh())['can_access']);
    }

    public function test_topic_lock_changes_and_assignment_deadlines_are_fresh(): void
    {
        [$student, $course, $topic] = $this->fixture();
        $assignment = Assignment::factory()->completeTopic()->create([
            'course_id' => $course->id, 'topic_id' => $topic->id, 'deadline' => now()->addMinute(),
        ]);
        $assignment->assignedUsers()->attach($student);
        $controller = app(TopicAccessController::class);
        $this->assertTrue($controller->checkAccess($topic, $student)['has_assignment']);
        $this->travel(2)->minutes();
        $this->assertTrue($controller->checkAccess($topic, $student)['has_inactive_assignment']);
        $topic->update(['is_locked' => true]);
        $this->assertFalse($controller->checkAccess($topic, $student)['can_access']);
        $topic->update(['is_locked' => false]);
        $this->assertTrue($controller->checkAccess($topic, $student)['can_access']);
    }

    public function test_explicit_revoke_is_safe_to_repeat_and_polling_uses_active_flag(): void
    {
        [$student, $course] = $this->fixture();
        // Legacy enrollments can be active without a timestamp.
        $this->actingAs($student)->postJson('/api/check-enrollment-status', ['course_ids' => [$course->id]])
            ->assertOk()->assertJsonPath('enrollment_changes.0.is_enrolled', true);
        $student->courses()->updateExistingPivot($course, ['enrolled_at' => now()]);
        $admin = User::factory()->admin()->create();
        $payload = ['user_id' => $student->id, 'course_id' => $course->id, 'is_enrolled' => false];
        $this->actingAs($admin)->postJson(route('admin.user-course.revoke'), $payload)
            ->assertOk()->assertJsonPath('is_enrolled', false);
        $this->postJson(route('admin.user-course.revoke'), $payload)->assertOk()->assertJsonPath('is_enrolled', false);
        $this->actingAs($student)->postJson('/api/check-enrollment-status', ['course_ids' => [$course->id]])
            ->assertOk()->assertJsonPath('enrollment_changes.0.is_enrolled', false);
        // Preserve the historical toggle behavior for older clients that omit intent.
        unset($payload['is_enrolled']);
        $this->actingAs($admin)->postJson(route('admin.user-course.revoke'), $payload)
            ->assertOk()->assertJsonPath('is_enrolled', true);
    }

    public function test_explicit_admin_actions_are_repeatable_and_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $this->actingAs($admin);
        $url = route('admin.user-course.toggle-admin');
        $payload = ['user_id' => $student->id, 'action' => 'grant'];
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('new_role', User::ROLE_ADMIN);
        $this->postJson($url, [...$payload, 'action' => 'promote'])->assertOk()->assertJsonPath('new_role', User::ROLE_ADMIN);
        $payload['action'] = 'revoke';
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('new_role', User::ROLE_STUDENT);
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('new_role', User::ROLE_STUDENT);
        $this->postJson($url, ['user_id' => $admin->id, 'action' => 'revoke'])->assertStatus(400);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_polling_rejects_malformed_and_unbounded_course_lists(): void
    {
        $this->actingAs(User::factory()->student()->create());
        foreach (['/api/check-enrollment-status', '/api/check-payment-status'] as $url) {
            $this->postJson($url, ['course_ids' => [[1]]])->assertUnprocessable();
            $this->postJson($url, ['course_ids' => range(1, 201)])->assertUnprocessable();
        }
    }

    public function test_video_progress_requires_access_and_validation_errors_remain_422(): void
    {
        [$student, $course, $topic] = $this->fixture();
        Video::create(['topic_id' => $topic->id, 'title' => 'Sample video', 'youtube_video_id' => 'sample12345']);
        $this->actingAs($student);
        $payload = ['topic_id' => $topic->id, 'current_time' => 25, 'duration' => 100];
        $this->postJson(route('video.progress.update'), $payload)->assertOk()->assertJsonPath('progress.percentage', '25.00');
        $this->getJson(route('video.progress.get', $topic))->assertOk()->assertJsonPath('percentage', '25.00');
        $this->postJson(route('video.progress.update'), [...$payload, 'current_time' => -1])->assertUnprocessable();
        $this->postJson(route('video.progress.update'), [...$payload, 'duration' => 1e12])->assertUnprocessable();
        $other = User::factory()->student()->create();
        $this->actingAs($other)->postJson(route('video.progress.update'), $payload)->assertForbidden();
        $this->getJson(route('video.progress.get', $topic))->assertForbidden();
        $this->assertDatabaseCount('video_user_progress', 1);
        $student->courses()->updateExistingPivot($course, ['is_enrolled' => false]);
        $this->actingAs($student->fresh())->postJson(route('video.progress.update'), $payload)->assertForbidden();
    }

    public function test_progress_updates_cannot_write_into_another_students_cache(): void
    {
        [$student, , $topic] = $this->fixture();
        $other = User::factory()->student()->create();
        $this->actingAs($student)->postJson(route('progress.store-update'), [
            'topicId' => $topic->id, 'userId' => $other->id, 'progress' => ['score' => 100],
        ])->assertUnprocessable();
        $this->assertFalse(Cache::has("user_progress_updates_{$other->id}"));
        $this->postJson(route('progress.store-update'), ['topicId' => $topic->id, 'userId' => $student->id])->assertOk();
        $this->assertTrue(Cache::has("user_progress_updates_{$student->id}"));
    }

    public function test_invalid_notification_and_progress_polling_input_returns_validation_errors(): void
    {
        $this->actingAs(User::factory()->student()->create());
        $this->getJson(route('notifications.index', ['limit' => ['bad']]))->assertUnprocessable();
        $this->postJson(route('notifications.mark-read'), ['notification_id' => ['bad']])->assertUnprocessable();
        $this->postJson(route('notifications.mark-read'), [])->assertUnprocessable();
        $this->getJson(route('progress.recent-updates', ['since' => ['bad']]))->assertUnprocessable();
        $this->getJson(route('notifications.index', ['limit' => 5]))->assertOk();
    }

    public function test_assignment_sync_requires_assignment_membership_and_matching_topic(): void
    {
        [$student, $course, $topic] = $this->fixture();
        $assignment = Assignment::factory()->completeTopic()->create(['topic_id' => $topic->id, 'course_id' => $course->id]);
        $this->actingAs($student);
        $payload = ['assignmentId' => $assignment->id, 'userId' => $student->id, 'percentage' => 50, 'score' => 1, 'total' => 2];
        $this->postJson(route('progress.store-assignment'), $payload)->assertForbidden();
        $this->assertFalse(Cache::has("user_assignment_completions_{$student->id}"));
        $assignment->assignedUsers()->attach($student);
        $this->postJson(route('progress.store-assignment'), $payload)->assertOk();
        $this->postJson(route('progress.store-assignment'), [...$payload, 'score' => 3])->assertUnprocessable();
        $foreignTopic = Topic::factory()->forCourse($course)->create();
        $this->postJson(route('progress.store-update'), [
            'topicId' => $foreignTopic->id, 'userId' => $student->id, 'assignmentId' => $assignment->id,
        ])->assertForbidden();
    }
}
