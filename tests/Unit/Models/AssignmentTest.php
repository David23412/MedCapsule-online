<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private $tutor;
    private $student;
    private $course;
    private $topic;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
    }

    /** @test */
    public function it_can_create_an_assignment()
    {
        $assignment = Assignment::create([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'topic_id' => $this->topic->id,
            'course_id' => $this->course->id,
            'title' => 'Test Assignment',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7),
            'duration_minutes' => 60,
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals('Test Assignment', $assignment->title);
        $this->assertEquals(Assignment::STATUS_ACTIVE, $assignment->status);
    }

    /** @test */
    public function it_has_proper_relationships()
    {
        $assignment = Assignment::factory()->create([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'topic_id' => $this->topic->id,
            'course_id' => $this->course->id
        ]);

        $this->assertEquals($this->tutor->id, $assignment->tutor->id);
        $this->assertEquals($this->student->id, $assignment->student->id);
        $this->assertEquals($this->topic->id, $assignment->topic->id);
        $this->assertEquals($this->course->id, $assignment->course->id);
    }

    /** @test */
    public function it_can_check_if_assignment_is_overdue()
    {
        $overdueAssignment = Assignment::factory()->create([
            'deadline' => now()->subDays(1),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $activeAssignment = Assignment::factory()->create([
            'deadline' => now()->addDays(1),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $this->assertTrue($overdueAssignment->isOverdue());
        $this->assertFalse($activeAssignment->isOverdue());
    }

    /** @test */
    public function it_can_get_time_remaining()
    {
        $assignment = Assignment::factory()->active()->create([
            'deadline' => now()->addHours(2)
        ]);

        $timeRemaining = $assignment->getTimeRemaining();
        $this->assertIsInt($timeRemaining);
        $this->assertGreaterThan(0, $timeRemaining);

        $overdueAssignment = Assignment::factory()->active()->create([
            'deadline' => now()->subHours(1)
        ]);

        $this->assertNull($overdueAssignment->getTimeRemaining());
    }

    /** @test */
    public function it_can_check_if_assignment_can_be_completed()
    {
        $activeAssignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $completedAssignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_COMPLETED
        ]);

        $lockedAssignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_LOCKED
        ]);

        $this->assertTrue($activeAssignment->canBeCompleted());
        $this->assertFalse($completedAssignment->canBeCompleted());
        $this->assertFalse($lockedAssignment->canBeCompleted());
    }

    /** @test */
    public function it_can_mark_assignment_as_completed()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $result = $assignment->markAsCompleted(85);

        $this->assertTrue($result);
        $this->assertEquals(Assignment::STATUS_COMPLETED, $assignment->fresh()->status);
        $this->assertEquals(85, $assignment->fresh()->score);
        $this->assertNotNull($assignment->fresh()->completed_at);
    }

    /** @test */
    public function it_can_mark_assignment_as_overdue()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $result = $assignment->markAsOverdue();

        $this->assertTrue($result);
        $this->assertEquals(Assignment::STATUS_OVERDUE, $assignment->fresh()->status);
    }

    /** @test */
    public function it_can_lock_and_unlock_assignments()
    {
        $assignment = Assignment::factory()->create([
            'status' => Assignment::STATUS_ACTIVE
        ]);

        // Test locking
        $lockResult = $assignment->lock();
        $this->assertTrue($lockResult);
        $this->assertEquals(Assignment::STATUS_LOCKED, $assignment->fresh()->status);

        // Test unlocking
        $unlockResult = $assignment->unlock();
        $this->assertTrue($unlockResult);
        $this->assertEquals(Assignment::STATUS_ACTIVE, $assignment->fresh()->status);
    }

    /** @test */
    public function it_has_proper_query_scopes()
    {
        Assignment::factory()->create(['status' => Assignment::STATUS_ACTIVE]);
        Assignment::factory()->create(['status' => Assignment::STATUS_COMPLETED]);
        Assignment::factory()->create(['status' => Assignment::STATUS_OVERDUE]);

        $this->assertEquals(1, Assignment::active()->count());
        $this->assertEquals(1, Assignment::completed()->count());
        $this->assertEquals(1, Assignment::overdue()->count());
    }

    /** @test */
    public function it_can_get_progress_percentage()
    {
        $assignment = Assignment::factory()->create([
            'created_at' => now()->subHours(5),
            'deadline' => now()->addHours(5),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $progress = $assignment->getProgressPercentage();
        $this->assertGreaterThan(0, $progress);
        $this->assertLessThanOrEqual(100, $progress);
    }

    /** @test */
    public function it_can_get_difficulty_level()
    {
        $urgentAssignment = Assignment::factory()->active()->create([
            'deadline' => now()->addHour()
        ]);

        $highAssignment = Assignment::factory()->active()->create([
            'deadline' => now()->addHours(12)
        ]);

        $mediumAssignment = Assignment::factory()->active()->create([
            'deadline' => now()->addHours(48)
        ]);

        $lowAssignment = Assignment::factory()->active()->create([
            'deadline' => now()->addDays(7)
        ]);

        $this->assertEquals('urgent', $urgentAssignment->getDifficultyLevel());
        $this->assertEquals('high', $highAssignment->getDifficultyLevel());
        $this->assertEquals('medium', $mediumAssignment->getDifficultyLevel());
        $this->assertEquals('low', $lowAssignment->getDifficultyLevel());
    }

    /** @test */
    public function it_can_update_overdue_assignments_in_bulk()
    {
        Assignment::factory()->count(3)->create([
            'deadline' => now()->subDays(1),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        Assignment::factory()->create([
            'deadline' => now()->addDays(1),
            'status' => Assignment::STATUS_ACTIVE
        ]);

        $updated = Assignment::updateOverdueAssignments();

        $this->assertEquals(3, $updated);
        $this->assertEquals(3, Assignment::where('status', Assignment::STATUS_OVERDUE)->count());
    }

    /** @test */
    public function it_can_get_assignment_statistics()
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        
        Assignment::factory()->active()->create(['tutor_id' => $tutor->id]);
        Assignment::factory()->completed()->create(['tutor_id' => $tutor->id, 'score' => 85]);
        Assignment::factory()->overdue()->create(['tutor_id' => $tutor->id]);

        $stats = Assignment::getStatistics($tutor->id);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['active']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['overdue']);
        $this->assertEquals(85, $stats['average_score']);
        $this->assertGreaterThan(0, $stats['completion_rate']);
    }
}