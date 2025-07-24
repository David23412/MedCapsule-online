<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Assignment;
use App\Models\AssignmentAttempt;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Services\AssignmentService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Mockery;

class EnhancedAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentService $service;
    private NotificationService $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the notification service
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        $this->mockNotificationService->shouldReceive('notifyAssignmentCreated')->andReturn(true);
        $this->mockNotificationService->shouldReceive('notifyAssignmentCompleted')->andReturn(true);
        $this->mockNotificationService->shouldReceive('notifyAssignmentExpired')->andReturn(true);
        $this->mockNotificationService->shouldReceive('notifyAssignmentAutoSubmitted')->andReturn(true);
        
        $this->service = new AssignmentService($this->mockNotificationService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_source_specific_assignment()
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
        
        // Create tutor-student relationship
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $data = [
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Assignment',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7),
            'duration_minutes' => 60,

            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'Kaplan'
        ];
        
        // Act
        $assignment = $this->service->createAssignment($data);
        
        // Assert
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(Assignment::MODE_SOURCE_SPECIFIC, $assignment->assignment_mode);
        $this->assertEquals('Kaplan', $assignment->assigned_source);
        $this->assertEquals(60, $assignment->duration_minutes);
        $this->assertEquals(Assignment::STATUS_ACTIVE, $assignment->status);
    }
    
    /** @test */
    public function it_creates_complete_topic_assignment()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create tutor-student relationship
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $data = [
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Assignment',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7),
            'duration_minutes' => 60,

            'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC
        ];
        
        // Act
        $assignment = $this->service->createAssignment($data);
        
        // Assert
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(Assignment::MODE_COMPLETE_TOPIC, $assignment->assignment_mode);
        $this->assertNull($assignment->assigned_source);
        $this->assertEquals(60, $assignment->duration_minutes);
        $this->assertEquals(Assignment::STATUS_ACTIVE, $assignment->status);
    }
    
    /** @test */
    public function it_requires_source_for_source_specific_assignment()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create tutor-student relationship
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $data = [
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Assignment',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7),
            'duration_minutes' => 60,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC
            // Missing assigned_source
        ];
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be specified for source-specific assignments');
        
        // Act
        $this->service->createAssignment($data);
    }
    
    /** @test */
    public function it_validates_source_exists_for_topic()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create tutor-student relationship
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $data = [
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Assignment',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7),
            'duration_minutes' => 60,
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => 'NonExistentSource'
        ];
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Source 'NonExistentSource' does not exist for this topic");
        
        // Act
        $this->service->createAssignment($data);
    }
    
    /** @test */
    public function it_calculates_source_specific_score()
    {
        // Arrange
        $topic = Topic::factory()->create();
        
        // Create questions with a specific source
        $questionIds = [];
        for ($i = 0; $i < 5; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'topic_id' => $topic->id,
                'question_text' => "Question {$i}",
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_answer' => 'A',
                'source' => 'Kaplan',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $questionIds[] = $questionId;
        }
        
        $assignment = Assignment::factory()
            ->sourceSpecific()
            ->create([
                'topic_id' => $topic->id,
                'assigned_source' => 'Kaplan'
            ]);
        
        // 3 correct answers out of 5 questions
        $answers = [
            $questionIds[0] => 'A', // Correct
            $questionIds[1] => 'A', // Correct
            $questionIds[2] => 'A', // Correct
            $questionIds[3] => 'B', // Incorrect
            $questionIds[4] => 'C'  // Incorrect
        ];
        
        // Act
        $result = $this->service->calculateAssignmentScore($assignment, $answers);
        
        // Assert
        $this->assertEquals(60, $result['score']); // 3/5 = 60%
        $this->assertEquals(60, $result['score_percentage']);
        $this->assertEquals(3, $result['correct_answers']);
        $this->assertEquals(5, $result['total_questions']);
        $this->assertEquals('Kaplan', $result['source']);
    }
    
    /** @test */
    public function it_calculates_complete_topic_score()
    {
        // Arrange
        $topic = Topic::factory()->create();
        
        // Create questions with different sources
        $questionIds = [];
        $sources = ['Kaplan', 'Practice Sheet', 'UWorld'];
        
        foreach ($sources as $source) {
            for ($i = 0; $i < 3; $i++) { // 3 questions per source = 9 total
                $questionId = DB::table('questions')->insertGetId([
                    'topic_id' => $topic->id,
                    'question_text' => "Question {$source} {$i}",
                    'option_a' => 'A',
                    'option_b' => 'B',
                    'option_c' => 'C',
                    'option_d' => 'D',
                    'correct_answer' => 'A',
                    'source' => $source,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $questionIds[] = $questionId;
            }
        }
        
        $assignment = Assignment::factory()
            ->completeTopic()
            ->create([
                'topic_id' => $topic->id
            ]);
        
        // 6 correct answers out of 9 questions
        $answers = [];
        for ($i = 0; $i < 9; $i++) {
            $answers[$questionIds[$i]] = $i < 6 ? 'A' : 'B'; // First 6 correct, last 3 incorrect
        }
        
        // Act
        $result = $this->service->calculateAssignmentScore($assignment, $answers);
        
        // Assert
        $this->assertEquals(67, $result['score']); // 6/9 = 66.67% rounded to 67%
        $this->assertEqualsWithDelta(66.67, $result['score_percentage'], 0.01);
        $this->assertEquals(6, $result['correct_answers']);
        $this->assertEquals(9, $result['total_questions']);
        $this->assertEquals('all', $result['source']);
    }
    
    /** @test */
    public function it_handles_assignment_expiry()
    {
        // Arrange
        $assignment = Assignment::factory()->active()->withTimeLimit(30)->create();
        
        // Act
        $this->service->handleAssignmentExpiry($assignment);
        
        // Assert
        $assignment->refresh();
        $this->assertEquals(Assignment::STATUS_EXPIRED, $assignment->status);
        $this->assertNotNull($assignment->expired_at);
    }
    
    /** @test */
    public function it_auto_submits_assignment_with_partial_answers()
    {
        // Arrange
        $topic = Topic::factory()->create();
        
        // Create questions
        $questionIds = [];
        for ($i = 0; $i < 5; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'topic_id' => $topic->id,
                'question_text' => "Question {$i}",
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_answer' => 'A',
                'source' => 'Kaplan',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $questionIds[] = $questionId;
        }
        
        $assignment = Assignment::factory()
            ->sourceSpecific()
            ->withTimeLimit(30)
            ->create([
                'topic_id' => $topic->id,
                'assigned_source' => 'Kaplan'
            ]);
        
        // Only 3 out of 5 questions answered, 2 correct
        $answers = [
            $questionIds[0] => 'A', // Correct
            $questionIds[1] => 'A', // Correct
            $questionIds[2] => 'B', // Incorrect
        ];
        
        // Act
        $result = $this->service->autoSubmitAssignment($assignment, $answers);
        
        // Assert
        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals(Assignment::STATUS_EXPIRED, $assignment->status);
        $this->assertNotNull($assignment->expired_at);
        $this->assertEquals(40, $assignment->score); // 2/5 = 40%
        $this->assertEquals(40, $assignment->score_percentage);
        
        // Check that an assignment attempt was created
        $attempt = DB::table('assignment_attempts')
            ->where('assignment_id', $assignment->id)
            ->first();
            
        $this->assertNotNull($attempt);
        $this->assertEquals(3, $attempt->questions_answered);
        $this->assertEquals(2, $attempt->correct_answers);
        $this->assertEquals(40, $attempt->score_percentage);
        $this->assertTrue((bool)$attempt->auto_submitted);
    }
    
    /** @test */
    public function it_completes_assignment_with_score_calculation()
    {
        // Arrange
        $topic = Topic::factory()->create();
        
        // Create questions
        $questionIds = [];
        for ($i = 0; $i < 5; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'topic_id' => $topic->id,
                'question_text' => "Question {$i}",
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_answer' => 'A',
                'source' => 'Kaplan',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $questionIds[] = $questionId;
        }
        
        $assignment = Assignment::factory()
            ->sourceSpecific()
            ->active()
            ->create([
                'topic_id' => $topic->id,
                'assigned_source' => 'Kaplan'
            ]);
        
        // 4 out of 5 correct
        $answers = [
            $questionIds[0] => 'A', // Correct
            $questionIds[1] => 'A', // Correct
            $questionIds[2] => 'A', // Correct
            $questionIds[3] => 'A', // Correct
            $questionIds[4] => 'B', // Incorrect
        ];
        
        // Act
        $result = $this->service->completeAssignment($assignment, $answers);
        
        // Assert
        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals(Assignment::STATUS_COMPLETED, $assignment->status);
        $this->assertNotNull($assignment->completed_at);
        $this->assertEquals(80, $assignment->score); // 4/5 = 80%
        $this->assertEquals(80, $assignment->score_percentage);
    }
    
    /** @test */
    public function it_gets_assignment_reports_for_tutor()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor', 'name' => 'Test Tutor']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Test Student']);
        $course = Course::factory()->create(['name' => 'Test Course']);
        $topic = Topic::factory()->create(['course_id' => $course->id, 'title' => 'Test Topic']);
        
        // Create questions with a specific source
        $questionIds = [];
        for ($i = 0; $i < 5; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'topic_id' => $topic->id,
                'question_text' => "Question {$i}",
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_answer' => 'A',
                'source' => 'Kaplan',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $questionIds[] = $questionId;
        }
        
        // Create an assignment
        $assignment = Assignment::factory()
            ->sourceSpecific()
            ->create([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'title' => 'Test Assignment',
                'assigned_source' => 'Kaplan',
                'status' => Assignment::STATUS_ACTIVE
            ]);
        
        // Create an assignment attempt
        AssignmentAttempt::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'source' => 'Kaplan',
            'questions_answered' => 3,
            'correct_answers' => 2,
            'score_percentage' => 40.0,
            'time_spent_seconds' => 600,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(5),
            'auto_submitted' => false,
            'answers' => json_encode([
                $questionIds[0] => 'A',
                $questionIds[1] => 'A',
                $questionIds[2] => 'B'
            ])
        ]);
        
        // Act
        $reports = $this->service->getAssignmentReports($tutor);
        
        // Assert
        $this->assertCount(1, $reports);
        $report = $reports[0];
        
        $this->assertEquals($assignment->id, $report['id']);
        $this->assertEquals('Test Assignment', $report['title']);
        $this->assertEquals('Test Student', $report['student']['name']);
        $this->assertEquals('Test Topic', $report['topic']['title']);
        $this->assertEquals('Test Course', $report['course']['name']);
        $this->assertEquals(Assignment::STATUS_ACTIVE, $report['status']);
        $this->assertEquals(Assignment::MODE_SOURCE_SPECIFIC, $report['assignment_mode']);
        
        // Check source details
        $this->assertEquals('Kaplan', $report['source_details']['source']);
        $this->assertTrue($report['source_details']['completed']);
        $this->assertEquals(40.0, $report['source_details']['score_percentage']);
        $this->assertEquals(3, $report['source_details']['questions_answered']);
        $this->assertEquals(2, $report['source_details']['correct_answers']);
        $this->assertEquals(600, $report['source_details']['time_spent_seconds']);
        
        // Check partial completion
        $this->assertEquals(60.0, $report['partial_completion']['percentage']);
        $this->assertEquals(3, $report['partial_completion']['questions_answered']);
        $this->assertEquals(5, $report['partial_completion']['total_questions']);
    }
    
    /** @test */
    public function it_gets_assignment_reports_with_filters()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course1->id]);
        
        // Create assignments
        $assignment1 = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student1->id,
            'topic_id' => $topic->id,
            'course_id' => $course1->id,
            'status' => Assignment::STATUS_ACTIVE,
            'created_at' => now()->subDays(5)
        ]);
        
        $assignment2 = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student2->id,
            'topic_id' => $topic->id,
            'course_id' => $course2->id,
            'status' => Assignment::STATUS_COMPLETED,
            'created_at' => now()->subDays(2)
        ]);
        
        // Act - Filter by student
        $studentReports = $this->service->getAssignmentReports($tutor, [
            'student_id' => $student1->id
        ]);
        
        // Assert
        $this->assertCount(1, $studentReports);
        $this->assertEquals($assignment1->id, $studentReports[0]['id']);
        
        // Act - Filter by course
        $courseReports = $this->service->getAssignmentReports($tutor, [
            'course_id' => $course2->id
        ]);
        
        // Assert
        $this->assertCount(1, $courseReports);
        $this->assertEquals($assignment2->id, $courseReports[0]['id']);
        
        // Act - Filter by status
        $statusReports = $this->service->getAssignmentReports($tutor, [
            'status' => Assignment::STATUS_COMPLETED
        ]);
        
        // Assert
        $this->assertCount(1, $statusReports);
        $this->assertEquals($assignment2->id, $statusReports[0]['id']);
        
        // Act - Filter by date range
        $dateReports = $this->service->getAssignmentReports($tutor, [
            'date_from' => now()->subDays(3)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        
        // Assert
        $this->assertCount(1, $dateReports);
        $this->assertEquals($assignment2->id, $dateReports[0]['id']);
    }
    
    /** @test */
    public function it_gets_complete_topic_assignment_reports()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Create questions with different sources
        $sources = ['Kaplan', 'UWorld', 'Practice'];
        foreach ($sources as $source) {
            for ($i = 0; $i < 3; $i++) {
                DB::table('questions')->insert([
                    'topic_id' => $topic->id,
                    'question_text' => "Question {$source} {$i}",
                    'option_a' => 'A',
                    'option_b' => 'B',
                    'option_c' => 'C',
                    'option_d' => 'D',
                    'correct_answer' => 'A',
                    'source' => $source,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Create a complete topic assignment
        $assignment = Assignment::factory()
            ->completeTopic()
            ->create([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'status' => Assignment::STATUS_ACTIVE
            ]);
        
        // Create attempts for different sources
        AssignmentAttempt::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'source' => 'Kaplan',
            'questions_answered' => 3,
            'correct_answers' => 2,
            'score_percentage' => 66.67,
            'time_spent_seconds' => 300,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(25),
            'auto_submitted' => false
        ]);
        
        AssignmentAttempt::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'source' => 'UWorld',
            'questions_answered' => 3,
            'correct_answers' => 3,
            'score_percentage' => 100.0,
            'time_spent_seconds' => 250,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(15),
            'auto_submitted' => false
        ]);
        
        // Act
        $reports = $this->service->getAssignmentReports($tutor);
        
        // Assert
        $this->assertCount(1, $reports);
        $report = $reports[0];
        
        $this->assertEquals($assignment->id, $report['id']);
        $this->assertEquals(Assignment::MODE_COMPLETE_TOPIC, $report['assignment_mode']);
        
        // Check source details - should have all sources
        $sourceDetails = $report['source_details'];
        $this->assertArrayHasKey('Kaplan', $sourceDetails);
        $this->assertArrayHasKey('UWorld', $sourceDetails);
        $this->assertArrayHasKey('Practice', $sourceDetails);
        
        // Check Kaplan source details
        $this->assertTrue($sourceDetails['Kaplan']['completed']);
        $this->assertEquals(3, $sourceDetails['Kaplan']['total_questions']);
        $this->assertEquals(3, $sourceDetails['Kaplan']['questions_answered']);
        $this->assertEquals(2, $sourceDetails['Kaplan']['correct_answers']);
        $this->assertEquals(66.67, $sourceDetails['Kaplan']['score_percentage']);
        
        // Check UWorld source details
        $this->assertTrue($sourceDetails['UWorld']['completed']);
        $this->assertEquals(3, $sourceDetails['UWorld']['total_questions']);
        $this->assertEquals(3, $sourceDetails['UWorld']['questions_answered']);
        $this->assertEquals(3, $sourceDetails['UWorld']['correct_answers']);
        $this->assertEquals(100.0, $sourceDetails['UWorld']['score_percentage']);
        
        // Check Practice source details - not attempted
        $this->assertFalse($sourceDetails['Practice']['completed']);
        $this->assertEquals(3, $sourceDetails['Practice']['total_questions']);
        $this->assertEquals(0, $sourceDetails['Practice']['questions_answered']);
        $this->assertEquals(0, $sourceDetails['Practice']['correct_answers']);
        $this->assertEquals(0, $sourceDetails['Practice']['score_percentage']);
        
        // Check partial completion
        $this->assertEquals(66.67, $report['partial_completion']['percentage']);
        $this->assertEquals(6, $report['partial_completion']['questions_answered']);
        $this->assertEquals(9, $report['partial_completion']['total_questions']);
    }
}