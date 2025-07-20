<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AssignmentService;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private $assignmentService;
    private $tutor;
    private $student;
    private $course;
    private $topic;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assignmentService = new AssignmentService();
        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);

        // Create tutor-student relationship
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /** @test */
    public function it_can_create_assignment()
    {
        $data = [
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'topic_id' => $this->topic->id,
            'course_id' => $this->course->id,
            'title' => 'Test Assignment',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7)->toDateTimeString(),
            'duration_minutes' => 90
        ];

        $assignment = $this->assignmentService->createAssignment($data);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals('Test Assignment', $assignment->title);
        $this->assertEquals(90, $assignment->duration_minutes);
        $this->assertEquals(Assignment::STATUS_ACTIVE, $assignment->status);
    }

    /** @test */
    public function it_throws_exception_for_past_deadline()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Assignment deadline must be in the future');

        $data = [
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'topic_id' => $this->topic->id,
            'course_id' => $this->course->id,
            'title' => 'Test Assignment',
            'deadline' => now()->subDays(1)->toDateTimeString()
        ];

        $this->assignmentService->createAssignment($data);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = [
            'tutor_id' => $this->tutor->id,
            // Missing required fields
        ];

        $this->assignmentService->createAssignment($data);
    }

    /** @test */
    public function it_can_create_bulk_assignments()
    {
        $student2 = User::factory()->create(['role' => 'student']);
        
        // Create relationships
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $this->tutor->id,
            'student_id' => $student2->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $assignmentData = [
            'topic_id' => $this->topic->id,
            'course_id' => $this->course->id,
            'title' => 'Bulk Assignment',
            'deadline' => now()->addDays(7)->toDateTimeString()
        ];

        $assignments = $this->assignmentService->createBulkAssignments(
            $this->tutor->id,
            [$this->student->id, $student2->id],
            $assignmentData
        );

        $this->assertEquals(2, $assignments->count());
        $this->assertEquals('Bulk Assignment', $assignments->first()->title);
    }

    /** @test */
    public function it_throws_exception_for_invalid_tutor_student_relationship()
    {
        $invalidStudent = User::factory()->create(['role' => 'student']);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tutor-student relationship');

        $this->assignmentService->createBulkAssignments(
            $this->tutor->id,
            [$invalidStudent->id],
            [
                'topic_id' => $this->topic->id,
                'course_id' => $this->course->id,
                'title' => 'Test',
                'deadline' => now()->addDays(7)->toDateTimeString()
            ]
        );
    }

    /** @test */
    public function it_can_update_assignment()
    {
        $assignment = Assignment::factory()->create([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $updatedAssignment = $this->assignmentService->updateAssignment($assignment, [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ]);

        $this->assertEquals('Updated Title', $updatedAssignment->title);
        $this->assertEquals('Updated Description', $updatedAssignment->description);
    }

    /** @test */
    public function it_prevents_updating_completed_assignments()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_COMPLETED
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot update completed assignment except for score');

        $this->assignmentService->updateAssignment($assignment, [
            'title' => 'Should Not Update'
        ]);
    }

    /** @test */
    public function it_can_complete_assignment()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $result = $this->assignmentService->completeAssignment($assignment, 85);

        $this->assertTrue($result);
        $this->assertEquals(Assignment::STATUS_COMPLETED, $assignment->fresh()->status);
        $this->assertEquals(85, $assignment->fresh()->score);
    }

    /** @test */
    public function it_can_get_tutor_assignments_with_filters()
    {
        Assignment::factory()->create([
            'tutor_id' => $this->tutor->id,
            'course_id' => $this->course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);

        Assignment::factory()->create([
            'tutor_id' => $this->tutor->id,
            'course_id' => $this->course->id,
            'status' => Assignment::STATUS_COMPLETED
        ]);

        // Test without filters
        $allAssignments = $this->assignmentService->getTutorAssignments($this->tutor->id);
        $this->assertEquals(2, $allAssignments->count());

        // Test with status filter
        $activeAssignments = $this->assignmentService->getTutorAssignments($this->tutor->id, [
            'status' => Assignment::STATUS_ACTIVE
        ]);
        $this->assertEquals(1, $activeAssignments->count());

        // Test with course filter
        $courseAssignments = $this->assignmentService->getTutorAssignments($this->tutor->id, [
            'course_id' => $this->course->id
        ]);
        $this->assertEquals(2, $courseAssignments->count());
    }

    /** @test */
    public function it_can_get_student_assignments()
    {
        Assignment::factory()->create([
            'student_id' => $this->student->id,
            'status' => Assignment::STATUS_ACTIVE,
            'deadline' => now()->addHours(2)
        ]);

        Assignment::factory()->create([
            'student_id' => $this->student->id,
            'status' => Assignment::STATUS_OVERDUE,
            'deadline' => now()->subHours(1)
        ]);

        $assignments = $this->assignmentService->getStudentAssignments($this->student->id);

        $this->assertEquals(2, $assignments->count());
        // Should be ordered by urgency (overdue first)
        $this->assertEquals(Assignment::STATUS_OVERDUE, $assignments->first()->status);
    }

    /** @test */
    public function it_can_update_overdue_assignments()
    {
        Assignment::factory()->count(3)->create([
            'deadline' => now()->subDays(1),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $updated = $this->assignmentService->updateOverdueAssignments();

        $this->assertEquals(3, $updated);
    }

    /** @test */
    public function it_can_get_assignment_analytics()
    {
        Assignment::factory()->create([
            'tutor_id' => $this->tutor->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);

        Assignment::factory()->create([
            'tutor_id' => $this->tutor->id,
            'status' => Assignment::STATUS_COMPLETED,
            'score' => 90,
            'created_at' => now()->subDays(5),
            'completed_at' => now()->subDays(3)
        ]);

        $analytics = $this->assignmentService->getAssignmentAnalytics($this->tutor->id);

        $this->assertArrayHasKey('total', $analytics);
        $this->assertArrayHasKey('active', $analytics);
        $this->assertArrayHasKey('completed', $analytics);
        $this->assertArrayHasKey('recent_assignments', $analytics);
        $this->assertArrayHasKey('average_completion_time_hours', $analytics);
        $this->assertArrayHasKey('performance_trends', $analytics);

        $this->assertEquals(2, $analytics['total']);
        $this->assertEquals(1, $analytics['active']);
        $this->assertEquals(1, $analytics['completed']);
    }

    /** @test */
    public function it_can_get_student_performance_analytics()
    {
        Assignment::factory()->create([
            'student_id' => $this->student->id,
            'status' => Assignment::STATUS_COMPLETED,
            'score' => 85
        ]);

        Assignment::factory()->create([
            'student_id' => $this->student->id,
            'status' => Assignment::STATUS_OVERDUE
        ]);

        $analytics = $this->assignmentService->getStudentPerformanceAnalytics($this->student->id);

        $this->assertArrayHasKey('total_assignments', $analytics);
        $this->assertArrayHasKey('completed_assignments', $analytics);
        $this->assertArrayHasKey('overdue_assignments', $analytics);
        $this->assertArrayHasKey('completion_rate', $analytics);
        $this->assertArrayHasKey('average_score', $analytics);

        $this->assertEquals(2, $analytics['total_assignments']);
        $this->assertEquals(1, $analytics['completed_assignments']);
        $this->assertEquals(1, $analytics['overdue_assignments']);
        $this->assertEquals(85, $analytics['average_score']);
    }

    /** @test */
    public function it_can_extend_assignment_deadline()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addDays(1),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $newDeadline = now()->addDays(7);
        $updatedAssignment = $this->assignmentService->extendDeadline($assignment, $newDeadline);

        $this->assertEquals($newDeadline->format('Y-m-d H:i'), $updatedAssignment->deadline->format('Y-m-d H:i'));
    }

    /** @test */
    public function it_reactivates_overdue_assignment_when_extending_deadline()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->subDays(1),
            'status' => Assignment::STATUS_OVERDUE
        ]);

        $newDeadline = now()->addDays(7);
        $updatedAssignment = $this->assignmentService->extendDeadline($assignment, $newDeadline);

        $this->assertEquals(Assignment::STATUS_ACTIVE, $updatedAssignment->status);
        $this->assertEquals($newDeadline->format('Y-m-d H:i'), $updatedAssignment->deadline->format('Y-m-d H:i'));
    }

    /** @test */
    public function it_prevents_extending_deadline_to_past()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('New deadline must be in the future');

        $this->assignmentService->extendDeadline($assignment, now()->subDays(1));
    }

    /** @test */
    public function it_can_delete_assignment()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $result = $this->assignmentService->deleteAssignment($assignment);

        $this->assertTrue($result);
        $this->assertNull(Assignment::find($assignment->id));
    }

    /** @test */
    public function it_prevents_deleting_completed_assignments()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_COMPLETED
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete completed assignments');

        $this->assignmentService->deleteAssignment($assignment);
    }
}