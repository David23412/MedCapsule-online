<?php

namespace Tests\Feature;

use App\Http\Controllers\TopicAccessController;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizUltraConsolidatedCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentExtensionUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_personal_extensions_alongside_assignment_details(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        // This test exercises assignment extensions for an enrolled paid course.
        // A randomly free course also requires the separate survey unlock.
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $assignment = Assignment::factory()->completeTopic()->create([
            'topic_id' => $topic->id, 'course_id' => $course->id, 'deadline' => now()->addDay(),
        ]);
        $assignment->assignedUsers()->attach([$student->id, $other->id]);
        $extended = now()->addDays(3)->startOfMinute();
        $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('topic_access', [
            'topic_id' => $assignment->topic_id, 'user_id' => $student->id,
            'timestamp' => now()->format('Y-m-d-H'),
        ]);
        Cache::put($cacheKey, ['has_inactive_assignment' => true], 3600);


        $this->actingAs($admin)->putJson(route('assignments.update', $assignment), [
            'title' => 'Updated assignment',
            'user_extensions' => [$student->id => $extended->toDateTimeString()],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('Updated assignment', $assignment->fresh()->title);
        $this->assertFalse(Cache::has($cacheKey));
        $student->courses()->attach($assignment->topic->course_id, [
            'is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL,
        ]);
        $access = app(TopicAccessController::class)->checkAccess($assignment->topic, $student);
        $this->assertTrue($access['can_access']);
        $this->assertSame($extended->toDateTimeString(), $access['assignment_deadline']);

        $this->assertDatabaseHas('assignment_user', [
            'assignment_id' => $assignment->id, 'user_id' => $student->id,
            'extended_deadline' => $extended->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('assignment_user', [
            'assignment_id' => $assignment->id, 'user_id' => $other->id, 'extended_deadline' => null,
        ]);
    }

    public function test_invalid_extension_recipient_does_not_partially_update_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        $assignment = Assignment::factory()->create(['title' => 'Original title']);
        $assignment->assignedUsers()->attach($student);

        $this->actingAs($admin)->putJson(route('assignments.update', $assignment), [
            'title' => 'Must not be saved',
            'user_extensions' => [
                $student->id => now()->addDays(40)->toDateTimeString(),
                $outsider->id => now()->addDays(40)->toDateTimeString(),
            ],
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertSame('Original title', $assignment->fresh()->title);
        $this->assertDatabaseHas('assignment_user', [
            'assignment_id' => $assignment->id, 'user_id' => $student->id, 'extended_deadline' => null,
        ]);
        $this->assertDatabaseMissing('assignment_user', ['assignment_id' => $assignment->id, 'user_id' => $outsider->id]);
    }

    public function test_empty_deadline_preserves_the_existing_deadline(): void
    {
        $admin = User::factory()->admin()->create();
        $assignment = Assignment::factory()->create();
        $deadline = $assignment->deadline->toDateTimeString();
        $this->actingAs($admin)->putJson(route('assignments.update', $assignment), [
            'title' => 'Updated title', 'deadline' => '',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertSame($deadline, $assignment->fresh()->deadline->toDateTimeString());
    }
}
