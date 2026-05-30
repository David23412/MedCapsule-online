<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\TopicAccessController;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Services\TopicLockingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicLockingServiceSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_lock_subject_updates_only_matching_topics_in_selected_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $otherCourse = Course::factory()->create();

        $matchingTopic = Topic::factory()->forCourse($course)->create([
            'subject' => 'Anatomy',
            'is_locked' => false,
        ]);
        $sameCourseOtherSubject = Topic::factory()->forCourse($course)->create([
            'subject' => 'Histology',
            'is_locked' => false,
        ]);
        $otherCourseSameSubject = Topic::factory()->forCourse($otherCourse)->create([
            'subject' => 'Anatomy',
            'is_locked' => false,
        ]);

        $updatedCount = app(TopicLockingService::class)->lockSubject($course, 'Anatomy', $admin);

        $this->assertSame(1, $updatedCount);
        $this->assertTrue($matchingTopic->fresh()->is_locked);
        $this->assertFalse($sameCourseOtherSubject->fresh()->is_locked);
        $this->assertFalse($otherCourseSameSubject->fresh()->is_locked);
    }

    public function test_unlock_subject_updates_matching_topics(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $topic = Topic::factory()->forCourse($course)->create([
            'subject' => 'Physiology',
            'is_locked' => true,
        ]);

        $updatedCount = app(TopicLockingService::class)->unlockSubject($course, 'Physiology', $admin);

        $this->assertSame(1, $updatedCount);
        $this->assertFalse($topic->fresh()->is_locked);
    }

    public function test_locked_subject_topic_is_blocked_by_existing_topic_access_logic(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->forCourse($course)->create([
            'subject' => 'Biochemistry',
            'is_locked' => false,
        ]);

        $student->courses()->attach($course->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
        ]);

        app(TopicLockingService::class)->lockSubject($course, 'Biochemistry', User::factory()->admin()->create());

        $accessController = (new \ReflectionClass(TopicAccessController::class))->newInstanceWithoutConstructor();
        $access = $accessController->checkAccess($topic->fresh(), $student);

        $this->assertFalse($access['can_access']);
        $this->assertSame(403, $access['status_code']);
    }
}
