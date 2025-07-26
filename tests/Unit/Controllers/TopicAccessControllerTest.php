<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Http\Controllers\TopicAccessController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class TopicAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    private TopicAccessController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TopicAccessController();
    }

    /** @test */
    public function it_allows_admin_access_to_any_topic()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $this->actingAs($admin);
        
        // Act
        $result = $this->controller->checkAccess($topic, $admin);
        
        // Assert
        $this->assertTrue($result['can_access']);
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals(Response::HTTP_OK, $result['status_code']);
    }
    
    /** @test */
    public function it_denies_access_to_locked_topics()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        
        // Create a locked topic
        $topic = Topic::factory()->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Topic is under maintenance',
            'locked_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->actingAs($student);
        
        // Act
        $result = $this->controller->checkAccess($topic, $student);
        
        // Assert
        $this->assertFalse($result['can_access']);
        $this->assertEquals('Topic is under maintenance', $result['reason']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result['status_code']);
    }
    
    /** @test */
    public function it_denies_access_to_unenrolled_users()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $this->actingAs($student);
        
        // Act
        $result = $this->controller->checkAccess($topic, $student);
        
        // Assert
        $this->assertFalse($result['can_access']);
        $this->assertEquals('User is not enrolled in this course', $result['reason']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result['status_code']);
    }
    
    /** @test */
    public function it_provides_assignment_details_for_active_assignments()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create a question with a specific source
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create an active assignment
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'Kaplan',
            'deadline' => now()->addDays(1),

        ]);
        
        $this->actingAs($student);
        
        // Act
        $result = $this->controller->checkAccess($topic, $student);
        
        // Assert
        $this->assertTrue($result['can_access']);
        $this->assertTrue($result['has_assignment']);
        $this->assertEquals($assignment->id, $result['assignment_id']);
        $this->assertEquals(Assignment::MODE_SOURCE_SPECIFIC, $result['assignment_mode']);
        $this->assertEquals(60, $result['duration_minutes']);
        $this->assertEquals(['Kaplan'], $result['sources']);
        $this->assertEquals(Response::HTTP_OK, $result['status_code']);
    }
    
    /** @test */
    public function it_provides_all_sources_for_complete_topic_assignments()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 1',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 2',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'B',
            'source' => 'UWorld',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create a complete topic assignment
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE,
            'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC,
            'deadline' => now()->addDays(1),

        ]);
        
        $this->actingAs($student);
        
        // Act
        $result = $this->controller->checkAccess($topic, $student);
        
        // Assert
        $this->assertTrue($result['can_access']);
        $this->assertTrue($result['has_assignment']);
        $this->assertEquals($assignment->id, $result['assignment_id']);
        $this->assertEquals(Assignment::MODE_COMPLETE_TOPIC, $result['assignment_mode']);
        $this->assertContains('Kaplan', $result['sources']);
        $this->assertContains('UWorld', $result['sources']);
        $this->assertEquals(Response::HTTP_OK, $result['status_code']);
    }
    
    /** @test */
    public function it_allows_practice_mode_after_assignment_expiry()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 1',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 2',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'B',
            'source' => 'UWorld',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create an expired assignment
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_EXPIRED,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'Kaplan',
            'deadline' => now()->subDays(1),
            'expired_at' => now()->subHours(12)
        ]);
        
        $this->actingAs($student);
        
        // Act
        $result = $this->controller->checkAccess($topic, $student);
        
        // Assert
        $this->assertTrue($result['can_access']);
        $this->assertTrue($result['has_expired_assignment']);
        $this->assertEquals($assignment->id, $result['assignment_id']);
        $this->assertTrue($result['practice_mode']);
        $this->assertContains('Kaplan', $result['sources']);
        $this->assertContains('UWorld', $result['sources']);
        $this->assertEquals(Response::HTTP_OK, $result['status_code']);
    }
    
    /** @test */
    public function it_allows_regular_access_for_enrolled_students_without_assignments()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 1',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->actingAs($student);
        
        // Act
        $result = $this->controller->checkAccess($topic, $student);
        
        // Assert
        $this->assertTrue($result['can_access']);
        $this->assertTrue($result['practice_mode']);
        $this->assertContains('Kaplan', $result['sources']);
        $this->assertEquals(Response::HTTP_OK, $result['status_code']);
    }
    
    /** @test */
    public function it_returns_available_sources_for_a_topic()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 1',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 2',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'B',
            'source' => 'UWorld',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->actingAs($student);
        
        // Act
        $sources = $this->controller->getTopicSources($topic, $student);
        
        // Assert
        $this->assertContains('Kaplan', $sources);
        $this->assertContains('UWorld', $sources);
        $this->assertCount(2, $sources);
    }
    
    /** @test */
    public function it_returns_empty_sources_for_unauthorized_access()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 1',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Student is not enrolled in the course
        $this->actingAs($student);
        
        // Act
        $sources = $this->controller->getTopicSources($topic, $student);
        
        // Assert
        $this->assertEmpty($sources);
    }
    
    /** @test */
    public function it_returns_only_assigned_source_for_source_specific_assignment()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 1',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Kaplan',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        DB::table('questions')->insert([
            'topic_id' => $topic->id,
            'question_text' => 'Test question 2',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'B',
            'source' => 'UWorld',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Enroll student in course
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'enrollment_status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create a source-specific assignment
        Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'Kaplan',
            'deadline' => now()->addDays(1)
        ]);
        
        $this->actingAs($student);
        
        // Act
        $sources = $this->controller->getTopicSources($topic, $student);
        
        // Assert
        $this->assertEquals(['Kaplan'], $sources);
        $this->assertNotContains('UWorld', $sources);
    }
}