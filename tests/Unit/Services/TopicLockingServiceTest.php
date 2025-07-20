<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TopicLockingService;
use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopicLockingServiceTest extends TestCase
{
    use RefreshDatabase;

    private $topicLockingService;
    private $tutor;
    private $student;
    private $course;
    private $topic;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->topicLockingService = new TopicLockingService();
        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);

        // Create necessary relationships
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('course_user')->insert([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /** @test */
    public function it_can_lock_topic()
    {
        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->topicLockingService->lockTopic(
            $this->topic->id,
            $this->tutor->id,
            'Testing lock functionality'
        );

        $this->assertTrue($result);
        
        $topic = $this->topic->fresh();
        $this->assertTrue($topic->is_locked);
        $this->assertEquals($this->tutor->id, $topic->locked_by_tutor_id);
        $this->assertEquals('Testing lock functionality', $topic->lock_reason);
        $this->assertNotNull($topic->locked_at);
    }

    /** @test */
    public function it_can_unlock_topic()
    {
        // First lock the topic
        $this->topic->update([
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id,
            'lock_reason' => 'Test lock',
            'locked_at' => now()
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->topicLockingService->unlockTopic($this->topic->id, $this->tutor->id);

        $this->assertTrue($result);
        
        $topic = $this->topic->fresh();
        $this->assertFalse($topic->is_locked);
        $this->assertNull($topic->locked_by_tutor_id);
        $this->assertNull($topic->lock_reason);
        $this->assertNull($topic->locked_at);
        $this->assertNotNull($topic->unlocked_at);
    }

    /** @test */
    public function it_can_check_if_topic_is_locked()
    {
        // Topic not locked
        $this->assertFalse($this->topicLockingService->isTopicLocked($this->topic->id));

        // Lock the topic
        $this->topic->update(['is_locked' => true]);

        $this->assertTrue($this->topicLockingService->isTopicLocked($this->topic->id));
    }

    /** @test */
    public function it_returns_false_for_non_existent_topic()
    {
        $this->assertFalse($this->topicLockingService->isTopicLocked(99999));
    }

    /** @test */
    public function it_can_get_topic_lock_status()
    {
        $this->topic->update([
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id,
            'lock_reason' => 'Test reason',
            'locked_at' => now()
        ]);

        $status = $this->topicLockingService->getTopicLockStatus($this->topic->id);

        $this->assertTrue($status['exists']);
        $this->assertTrue($status['is_locked']);
        $this->assertEquals($this->tutor->id, $status['locked_by_tutor_id']);
        $this->assertEquals('Test reason', $status['lock_reason']);
        $this->assertFalse($status['can_access']);
    }

    /** @test */
    public function it_returns_proper_status_for_non_existent_topic()
    {
        $status = $this->topicLockingService->getTopicLockStatus(99999);

        $this->assertFalse($status['exists']);
    }

    /** @test */
    public function it_can_bulk_lock_topics()
    {
        $topic2 = Topic::factory()->create(['course_id' => $this->course->id]);
        $topic3 = Topic::factory()->create(['course_id' => $this->course->id]);

        $results = $this->topicLockingService->bulkLockTopics(
            [$this->topic->id, $topic2->id, $topic3->id],
            $this->tutor->id,
            'Bulk lock test'
        );

        $this->assertCount(3, $results['successful']);
        $this->assertEmpty($results['failed']);

        // Verify all topics are locked
        $this->assertTrue($this->topic->fresh()->is_locked);
        $this->assertTrue($topic2->fresh()->is_locked);
        $this->assertTrue($topic3->fresh()->is_locked);
    }

    /** @test */
    public function it_can_bulk_unlock_topics()
    {
        $topic2 = Topic::factory()->create([
            'course_id' => $this->course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $this->topic->update([
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $results = $this->topicLockingService->bulkUnlockTopics(
            [$this->topic->id, $topic2->id],
            $this->tutor->id
        );

        $this->assertCount(2, $results['successful']);
        $this->assertEmpty($results['failed']);

        // Verify all topics are unlocked
        $this->assertFalse($this->topic->fresh()->is_locked);
        $this->assertFalse($topic2->fresh()->is_locked);
    }

    /** @test */
    public function it_can_get_locked_topics_for_tutor()
    {
        $topic2 = Topic::factory()->create([
            'course_id' => $this->course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $anotherTutor = User::factory()->create(['role' => 'tutor']);
        $topic3 = Topic::factory()->create([
            'course_id' => $this->course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $anotherTutor->id
        ]);

        $lockedTopics = $this->topicLockingService->getLockedTopicsForTutor($this->tutor->id);

        $this->assertEquals(1, $lockedTopics->count());
        $this->assertEquals($topic2->id, $lockedTopics->first()->id);
    }

    /** @test */
    public function it_can_get_locked_topics_in_course()
    {
        $this->topic->update([
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $topic2 = Topic::factory()->create([
            'course_id' => $this->course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $anotherCourse = Course::factory()->create();
        $topic3 = Topic::factory()->create([
            'course_id' => $anotherCourse->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $lockedTopics = $this->topicLockingService->getLockedTopicsInCourse($this->course->id);

        $this->assertEquals(2, $lockedTopics->count());
        $this->assertTrue($lockedTopics->contains('id', $this->topic->id));
        $this->assertTrue($lockedTopics->contains('id', $topic2->id));
        $this->assertFalse($lockedTopics->contains('id', $topic3->id));
    }

    /** @test */
    public function it_can_get_accessible_topics_for_student()
    {
        // Create locked and unlocked topics
        $this->topic->update(['is_locked' => false]);
        
        $lockedTopic = Topic::factory()->create([
            'course_id' => $this->course->id,
            'is_locked' => true
        ]);

        $accessibleTopics = $this->topicLockingService->getAccessibleTopicsForStudent(
            $this->student->id,
            $this->course->id
        );

        $this->assertEquals(1, $accessibleTopics->count());
        $this->assertEquals($this->topic->id, $accessibleTopics->first()->id);
    }

    /** @test */
    public function it_can_lock_all_course_topics()
    {
        $topic2 = Topic::factory()->create(['course_id' => $this->course->id]);
        $topic3 = Topic::factory()->create(['course_id' => $this->course->id]);

        $results = $this->topicLockingService->lockCourseTopics(
            $this->course->id,
            $this->tutor->id,
            'Course-wide lock'
        );

        $this->assertCount(3, $results['successful']);
        $this->assertEmpty($results['failed']);

        // Verify all course topics are locked
        $courseTopics = Topic::where('course_id', $this->course->id)->get();
        foreach ($courseTopics as $topic) {
            $this->assertTrue($topic->is_locked);
            $this->assertEquals($this->tutor->id, $topic->locked_by_tutor_id);
        }
    }

    /** @test */
    public function it_can_unlock_all_course_topics()
    {
        // Lock all topics first
        Topic::where('course_id', $this->course->id)->update([
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id
        ]);

        $results = $this->topicLockingService->unlockCourseTopics($this->course->id, $this->tutor->id);

        $this->assertNotEmpty($results['successful']);
        $this->assertEmpty($results['failed']);

        // Verify all course topics are unlocked
        $courseTopics = Topic::where('course_id', $this->course->id)->get();
        foreach ($courseTopics as $topic) {
            $this->assertFalse($topic->is_locked);
        }
    }

    /** @test */
    public function it_can_get_locking_statistics()
    {
        // Create some locked topics - old ones
        Topic::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id,
            'locked_at' => now()->subDays(10) // Old
        ]);

        // Create recent locked topic
        Topic::factory()->create([
            'course_id' => $this->course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id,
            'locked_at' => now()->subDays(1) // Recent (within 7 days)
        ]);

        $stats = $this->topicLockingService->getLockingStatistics($this->tutor->id);

        $this->assertArrayHasKey('total_locked_topics', $stats);
        $this->assertArrayHasKey('recently_locked', $stats);
        $this->assertArrayHasKey('locks_by_course', $stats);
        $this->assertArrayHasKey('lock_activity', $stats);

        $this->assertEquals(3, $stats['total_locked_topics']);
        $this->assertEquals(1, $stats['recently_locked']);
        $this->assertIsArray($stats['locks_by_course']);
        $this->assertIsArray($stats['lock_activity']);
    }

    /** @test */
    public function it_can_check_student_topic_access()
    {
        // Test accessible topic
        $access = $this->topicLockingService->canStudentAccessTopic($this->student->id, $this->topic->id);
        
        $this->assertTrue($access['can_access']);
        $this->assertNull($access['reason']);

        // Test locked topic
        $this->topic->update([
            'is_locked' => true,
            'locked_by_tutor_id' => $this->tutor->id,
            'lock_reason' => 'Test lock'
        ]);

        $access = $this->topicLockingService->canStudentAccessTopic($this->student->id, $this->topic->id);
        
        $this->assertFalse($access['can_access']);
        $this->assertEquals('Topic is locked by tutor', $access['reason']);
        $this->assertEquals('Test lock', $access['lock_reason']);
    }

    /** @test */
    public function it_handles_non_existent_topic_access_check()
    {
        $access = $this->topicLockingService->canStudentAccessTopic($this->student->id, 99999);
        
        $this->assertFalse($access['can_access']);
        $this->assertEquals('Topic not found', $access['reason']);
    }

    /** @test */
    public function it_checks_course_access_for_student()
    {
        // Remove student from course
        DB::table('course_user')->where('user_id', $this->student->id)->delete();

        $access = $this->topicLockingService->canStudentAccessTopic($this->student->id, $this->topic->id);
        
        $this->assertFalse($access['can_access']);
        $this->assertEquals('No access to course', $access['reason']);
    }
}