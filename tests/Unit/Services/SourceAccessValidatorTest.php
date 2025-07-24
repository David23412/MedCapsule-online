<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Http\Controllers\TopicAccessController;
use App\Services\SourceAccessValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class SourceAccessValidatorTest extends TestCase
{
    use RefreshDatabase;

    private SourceAccessValidator $validator;
    private TopicAccessController $topicAccessController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->topicAccessController = new TopicAccessController();
        $this->validator = new SourceAccessValidator($this->topicAccessController);
    }

    /** @test */
    public function it_validates_source_access_for_authorized_users()
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
        $result = $this->validator->validateSourceAccess($topic, 'Kaplan', $student);
        
        // Assert
        $this->assertTrue($result['can_access']);
        $this->assertEquals(Response::HTTP_OK, $result['status_code']);
    }
    
    /** @test */
    public function it_denies_access_to_nonexistent_sources()
    {
        // Arrange
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
        
        $this->actingAs($student);
        
        // Act
        $result = $this->validator->validateSourceAccess($topic, 'NonExistentSource', $student);
        
        // Assert
        $this->assertFalse($result['can_access']);
        $this->assertEquals('Source not available for this topic or restricted by assignment', $result['reason']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result['status_code']);
    }
    
    /** @test */
    public function it_enforces_source_restrictions_for_assignments()
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
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'Kaplan',
            'deadline' => now()->addDays(1)
        ]);
        
        $this->actingAs($student);
        
        // Act - Try to access the assigned source
        $resultAllowed = $this->validator->validateSourceAccess($topic, 'Kaplan', $student);
        
        // Act - Try to access a different source
        $resultDenied = $this->validator->validateSourceAccess($topic, 'UWorld', $student);
        
        // Assert
        $this->assertTrue($resultAllowed['can_access']);
        $this->assertEquals(Response::HTTP_OK, $resultAllowed['status_code']);
        
        $this->assertFalse($resultDenied['can_access']);
        $this->assertEquals('This assignment requires you to complete a specific source', $resultDenied['reason']);
        $this->assertEquals('Kaplan', $resultDenied['required_source']);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $resultDenied['status_code']);
    }
    
    /** @test */
    public function it_allows_all_sources_for_complete_topic_assignments()
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
        Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE,
            'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC,
            'deadline' => now()->addDays(1)
        ]);
        
        $this->actingAs($student);
        
        // Act - Try to access different sources
        $resultKaplan = $this->validator->validateSourceAccess($topic, 'Kaplan', $student);
        $resultUWorld = $this->validator->validateSourceAccess($topic, 'UWorld', $student);
        
        // Assert
        $this->assertTrue($resultKaplan['can_access']);
        $this->assertEquals(Response::HTTP_OK, $resultKaplan['status_code']);
        
        $this->assertTrue($resultUWorld['can_access']);
        $this->assertEquals(Response::HTTP_OK, $resultUWorld['status_code']);
    }
    
    /** @test */
    public function it_validates_multiple_sources_at_once()
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
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'Kaplan',
            'deadline' => now()->addDays(1)
        ]);
        
        $this->actingAs($student);
        
        // Act
        $results = $this->validator->validateMultipleSources($topic, ['Kaplan', 'UWorld', 'NonExistent'], $student);
        
        // Assert
        $this->assertTrue($results['Kaplan']['can_access']);
        $this->assertFalse($results['UWorld']['can_access']);
        $this->assertFalse($results['NonExistent']['can_access']);
    }
    
    /** @test */
    public function it_returns_accessible_sources_for_a_user()
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
        $sources = $this->validator->getAccessibleSources($topic, $student);
        
        // Assert
        $this->assertContains('Kaplan', $sources);
        $this->assertContains('UWorld', $sources);
        $this->assertCount(2, $sources);
    }
    
    /** @test */
    public function it_checks_if_source_exists_for_topic()
    {
        // Arrange
        $topic = Topic::factory()->create();
        
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
        
        // Act & Assert
        $this->assertTrue($this->validator->sourceExists($topic, 'Kaplan'));
        $this->assertFalse($this->validator->sourceExists($topic, 'NonExistentSource'));
    }
}