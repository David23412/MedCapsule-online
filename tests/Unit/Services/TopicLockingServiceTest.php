<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use App\Services\TopicLockingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TopicLockingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TopicLockingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TopicLockingService();
    }

    /** @test */
    public function it_locks_a_topic()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create tutor-course relationship
        DB::table('course_tutors')->insert([
            'tutor_id' => $tutor->id,
            'course_id' => $course->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Act
        $result = $this->service->lock($topic, $tutor, 'Testing lock');
        
        // Assert
        $this->assertTrue($result);
        $topic->refresh();
        $this->assertTrue($topic->is_locked);
        $this->assertEquals($tutor->id, $topic->locked_by_tutor_id);
        $this->assertEquals('Testing lock', $topic->lock_reason);
        $this->assertNotNull($topic->locked_at);
    }
    
    /** @test */
    public function it_unlocks_a_topic()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Testing lock',
            'locked_at' => now()
        ]);
        
        // Create tutor-course relationship
        DB::table('course_tutors')->insert([
            'tutor_id' => $tutor->id,
            'course_id' => $course->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Act
        $result = $this->service->unlock($topic, $tutor);
        
        // Assert
        $this->assertTrue($result);
        $topic->refresh();
        $this->assertFalse($topic->is_locked);
        $this->assertNull($topic->locked_by_tutor_id);
        $this->assertNull($topic->lock_reason);
        $this->assertNotNull($topic->unlocked_at);
    }
    
    /** @test */
    public function it_allows_admin_to_lock_any_topic()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Act
        $result = $this->service->lock($topic, $admin, 'Admin lock');
        
        // Assert
        $this->assertTrue($result);
        $topic->refresh();
        $this->assertTrue($topic->is_locked);
        $this->assertEquals($admin->id, $topic->locked_by_tutor_id);
        $this->assertEquals('Admin lock', $topic->lock_reason);
    }
    
    /** @test */
    public function it_prevents_non_tutors_from_locking_topics()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only tutors and admins can lock/unlock topics');
        
        // Act
        $this->service->lock($topic, $student, 'Student lock attempt');
    }
    
    /** @test */
    public function it_prevents_tutors_without_course_permission_from_locking_topics()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // No tutor-course relationship created
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You do not have permission to lock/unlock topics in this course');
        
        // Act
        $this->service->lock($topic, $tutor, 'Unauthorized lock attempt');
    }
    
    /** @test */
    public function it_bulk_locks_topics()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        $topics = Topic::factory()->count(3)->create(['course_id' => $course->id]);
        
        // Create tutor-course relationship
        DB::table('course_tutors')->insert([
            'tutor_id' => $tutor->id,
            'course_id' => $course->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $topicIds = $topics->pluck('id')->toArray();
        
        // Act
        $results = $this->service->bulkLock($topicIds, $tutor, 'Bulk lock test');
        
        // Assert
        $this->assertCount(3, $results);
        foreach ($topicIds as $topicId) {
            $this->assertTrue($results[$topicId]['success']);
            $topic = Topic::find($topicId);
            $this->assertTrue($topic->is_locked);
            $this->assertEquals($tutor->id, $topic->locked_by_tutor_id);
            $this->assertEquals('Bulk lock test', $topic->lock_reason);
        }
    }
    
    /** @test */
    public function it_bulk_unlocks_topics()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        $topics = Topic::factory()->count(3)->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Pre-locked',
            'locked_at' => now()
        ]);
        
        // Create tutor-course relationship
        DB::table('course_tutors')->insert([
            'tutor_id' => $tutor->id,
            'course_id' => $course->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $topicIds = $topics->pluck('id')->toArray();
        
        // Act
        $results = $this->service->bulkUnlock($topicIds, $tutor);
        
        // Assert
        $this->assertCount(3, $results);
        foreach ($topicIds as $topicId) {
            $this->assertTrue($results[$topicId]['success']);
            $topic = Topic::find($topicId);
            $this->assertFalse($topic->is_locked);
            $this->assertNull($topic->locked_by_tutor_id);
            $this->assertNull($topic->lock_reason);
        }
    }
    
    /** @test */
    public function it_handles_nonexistent_topics_in_bulk_operations()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $topicIds = [999999, 888888]; // Non-existent IDs
        
        // Act
        $results = $this->service->bulkLock($topicIds, $admin, 'Test');
        
        // Assert
        $this->assertCount(2, $results);
        foreach ($topicIds as $topicId) {
            $this->assertFalse($results[$topicId]['success']);
            $this->assertEquals('Topic not found', $results[$topicId]['message']);
        }
    }
    
    /** @test */
    public function it_gets_locked_topics_for_a_course()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        
        // Create some locked and unlocked topics
        $lockedTopics = Topic::factory()->count(2)->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Test lock',
            'locked_at' => now()
        ]);
        
        $unlockedTopics = Topic::factory()->count(3)->create([
            'course_id' => $course->id,
            'is_locked' => false
        ]);
        
        // Act
        $result = $this->service->getLockedTopics($course);
        
        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $topic) {
            $this->assertTrue($topic->is_locked);
            $this->assertEquals($tutor->id, $topic->locked_by_tutor_id);
        }
    }
    
    /** @test */
    public function it_gets_topics_locked_by_a_tutor()
    {
        // Arrange
        $tutor1 = User::factory()->create(['role' => 'tutor']);
        $tutor2 = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        
        // Create topics locked by different tutors
        $tutor1Topics = Topic::factory()->count(2)->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor1->id,
            'lock_reason' => 'Tutor 1 lock',
            'locked_at' => now()
        ]);
        
        $tutor2Topics = Topic::factory()->count(3)->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor2->id,
            'lock_reason' => 'Tutor 2 lock',
            'locked_at' => now()
        ]);
        
        // Act
        $result = $this->service->getTopicsLockedByTutor($tutor1);
        
        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $topic) {
            $this->assertTrue($topic->is_locked);
            $this->assertEquals($tutor1->id, $topic->locked_by_tutor_id);
            $this->assertEquals('Tutor 1 lock', $topic->lock_reason);
        }
    }
    
    /** @test */
    public function it_checks_if_a_topic_is_locked()
    {
        // Arrange
        $lockedTopic = Topic::factory()->create([
            'is_locked' => true,
            'locked_by_tutor_id' => 1,
            'locked_at' => now()
        ]);
        
        $unlockedTopic = Topic::factory()->create([
            'is_locked' => false
        ]);
        
        // Act & Assert
        $this->assertTrue($this->service->isLocked($lockedTopic));
        $this->assertFalse($this->service->isLocked($unlockedTopic));
    }
    
    /** @test */
    public function it_gets_lock_info_for_a_topic()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor', 'name' => 'Test Tutor']);
        $lockedTopic = Topic::factory()->create([
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Testing lock info',
            'locked_at' => now()
        ]);
        
        $unlockedTopic = Topic::factory()->create([
            'is_locked' => false
        ]);
        
        // Act
        $lockedInfo = $this->service->getLockInfo($lockedTopic);
        $unlockedInfo = $this->service->getLockInfo($unlockedTopic);
        
        // Assert
        $this->assertNotNull($lockedInfo);
        $this->assertTrue($lockedInfo['is_locked']);
        $this->assertEquals($tutor->id, $lockedInfo['locked_by_tutor_id']);
        $this->assertEquals('Test Tutor', $lockedInfo['locked_by_tutor_name']);
        $this->assertEquals('Testing lock info', $lockedInfo['lock_reason']);
        $this->assertNotNull($lockedInfo['locked_at']);
        
        $this->assertNull($unlockedInfo);
    }
}