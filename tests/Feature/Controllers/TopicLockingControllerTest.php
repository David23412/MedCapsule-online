<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TopicLockingControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topic/{$topic->id}/lock", [
                             'reason' => 'Testing lock via API'
                         ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Topic locked successfully'
        ]);
        
        $topic->refresh();
        $this->assertTrue($topic->is_locked);
        $this->assertEquals($tutor->id, $topic->locked_by_tutor_id);
        $this->assertEquals('Testing lock via API', $topic->lock_reason);
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
        
        // Act
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topic/{$topic->id}/unlock");
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Topic unlocked successfully'
        ]);
        
        $topic->refresh();
        $this->assertFalse($topic->is_locked);
        $this->assertNull($topic->locked_by_tutor_id);
        $this->assertNull($topic->lock_reason);
    }
    
    /** @test */
    public function it_allows_admin_to_lock_any_topic()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Act
        $response = $this->actingAs($admin)
                         ->postJson("/api/topic/{$topic->id}/lock", [
                             'reason' => 'Admin lock via API'
                         ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Topic locked successfully'
        ]);
        
        $topic->refresh();
        $this->assertTrue($topic->is_locked);
        $this->assertEquals($admin->id, $topic->locked_by_tutor_id);
        $this->assertEquals('Admin lock via API', $topic->lock_reason);
    }
    
    /** @test */
    public function it_prevents_students_from_locking_topics()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Act
        $response = $this->actingAs($student)
                         ->postJson("/api/topic/{$topic->id}/lock", [
                             'reason' => 'Student lock attempt'
                         ]);
        
        // Assert
        $response->assertStatus(403);
    }
    
    /** @test */
    public function it_prevents_tutors_without_course_permission_from_locking_topics()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // No tutor-course relationship created
        
        // Act
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topic/{$topic->id}/lock", [
                             'reason' => 'Unauthorized lock attempt'
                         ]);
        
        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You do not have permission to lock/unlock topics in this course'
        ]);
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
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topics/bulk-lock", [
                             'topic_ids' => $topicIds,
                             'reason' => 'Bulk lock test via API'
                         ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'All topics locked successfully'
        ]);
        
        foreach ($topicIds as $topicId) {
            $topic = Topic::find($topicId);
            $this->assertTrue($topic->is_locked);
            $this->assertEquals($tutor->id, $topic->locked_by_tutor_id);
            $this->assertEquals('Bulk lock test via API', $topic->lock_reason);
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
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topics/bulk-unlock", [
                             'topic_ids' => $topicIds
                         ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'All topics unlocked successfully'
        ]);
        
        foreach ($topicIds as $topicId) {
            $topic = Topic::find($topicId);
            $this->assertFalse($topic->is_locked);
            $this->assertNull($topic->locked_by_tutor_id);
            $this->assertNull($topic->lock_reason);
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
        $response = $this->actingAs($tutor)
                         ->getJson("/api/course/{$course->id}/locked-topics");
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        
        $responseData = $response->json();
        $this->assertCount(2, $responseData['locked_topics']);
    }
    
    /** @test */
    public function it_gets_topics_locked_by_current_tutor()
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
        $response = $this->actingAs($tutor1)
                         ->getJson("/api/my-locked-topics");
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        
        $responseData = $response->json();
        $this->assertCount(2, $responseData['locked_topics']);
        
        foreach ($responseData['locked_topics'] as $topic) {
            $this->assertEquals($tutor1->id, $topic['lock_info']['locked_by_tutor_id']);
        }
    }
    
    /** @test */
    public function it_gets_lock_info_for_a_topic()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor', 'name' => 'Test Tutor']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Testing lock info API',
            'locked_at' => now()
        ]);
        
        // Act
        $response = $this->actingAs($tutor)
                         ->getJson("/api/topic/{$topic->id}/lock-info");
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_locked' => true
        ]);
        
        $responseData = $response->json();
        $this->assertEquals($tutor->id, $responseData['lock_info']['locked_by_tutor_id']);
        $this->assertEquals('Test Tutor', $responseData['lock_info']['locked_by_tutor_name']);
        $this->assertEquals('Testing lock info API', $responseData['lock_info']['lock_reason']);
    }
    
    /** @test */
    public function it_validates_topic_ids_for_bulk_operations()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        
        // Act
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topics/bulk-lock", [
                             'topic_ids' => 'not-an-array',
                             'reason' => 'Invalid data test'
                         ]);
        
        // Assert
        $response->assertStatus(400);
        $response->assertJsonValidationErrors(['topic_ids']);
    }
    
    /** @test */
    public function it_returns_not_found_for_nonexistent_topic()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        
        // Act
        $response = $this->actingAs($tutor)
                         ->postJson("/api/topic/99999/lock", [
                             'reason' => 'Nonexistent topic test'
                         ]);
        
        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Topic not found'
        ]);
    }
}