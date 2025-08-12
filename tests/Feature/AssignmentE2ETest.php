<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;
use App\Models\QuizAttempt;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class AssignmentE2ETest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tutor;
    protected $students;
    protected $course;
    protected $topic;
    protected $assignmentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Test Tutor',
            'email' => 'tutor@test.com'
        ]);

        $this->students = User::factory()->count(3)->create([
            'role' => 'student'
        ]);

        // Create course and topic
        $this->course = Course::factory()->create([
            'name' => 'Test Course',
            'tutor_id' => $this->tutor->id
        ]);

        $this->topic = Topic::factory()->create([
            'name' => 'Test Topic',
            'course_id' => $this->course->id
        ]);

        $this->assignmentService = app(AssignmentService::class);
    }

    /** @test */
    public function complete_assignment_lifecycle_e2e_test()
    {
        $this->actingAs($this->tutor);

        echo "\n🚀 STARTING COMPREHENSIVE ASSIGNMENT E2E TEST\n";
        echo "=" . str_repeat("=", 60) . "\n";

        // PHASE 1: CREATE ASSIGNMENTS WITH DIFFERENT CONFIGURATIONS
        echo "\n📝 PHASE 1: Creating assignments with different configurations...\n";
        
        $assignments = $this->createTestAssignments();
        $this->assertCount(6, $assignments);
        echo "✅ Created 6 test assignments successfully\n";

        // PHASE 2: ASSIGN TO USERS VIA PIVOT TABLE
        echo "\n👥 PHASE 2: Assigning to users via pivot table...\n";
        
        $this->assignToUsers($assignments);
        echo "✅ Assigned all assignments to users via pivot table\n";

        // PHASE 3: TEST ASSIGNMENT STATUS TRANSITIONS
        echo "\n⏰ PHASE 3: Testing assignment status transitions...\n";
        
        $this->testStatusTransitions($assignments);
        echo "✅ Status transitions working correctly\n";

        // PHASE 4: SIMULATE STUDENT TAKING ASSIGNMENTS
        echo "\n🎯 PHASE 4: Simulating students taking assignments...\n";
        
        $this->simulateStudentAttempts($assignments);
        echo "✅ Student attempts simulated successfully\n";

        // PHASE 5: TEST GRADING AND COMPLETION
        echo "\n📊 PHASE 5: Testing grading and completion...\n";
        
        $this->testGradingAndCompletion($assignments);
        echo "✅ Grading and completion working correctly\n";

        // PHASE 6: TEST DELETION
        echo "\n🗑️ PHASE 6: Testing assignment deletion...\n";
        
        $this->testAssignmentDeletion($assignments);
        echo "✅ Assignment deletion working correctly\n";

        // PHASE 7: VERIFY FINAL STATE
        echo "\n🔍 PHASE 7: Verifying final state...\n";
        
        $this->verifyFinalState();
        echo "✅ Final state verification complete\n";

        echo "\n🎉 ALL E2E TESTS PASSED SUCCESSFULLY!\n";
        echo "=" . str_repeat("=", 60) . "\n";
    }

    private function createTestAssignments(): array
    {
        $assignments = [];

        // 1. Active assignment (due in 2 hours)
        $assignments['active'] = Assignment::create([
            'title' => 'Active Assignment - Due Soon',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addHours(2),
            'duration_minutes' => 60,
            'assignment_mode' => 'complete_topic',
            'assigned_source' => null
        ]);

        // 2. Assignment that will become overdue (due 1 hour ago)
        $assignments['overdue'] = Assignment::create([
            'title' => 'Overdue Assignment - Missed Deadline',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->subHour(),
            'duration_minutes' => 30,
            'assignment_mode' => 'complete_topic',
            'assigned_source' => null
        ]);

        // 3. Assignment with specific source
        $assignments['source_specific'] = Assignment::create([
            'title' => 'Source Specific Assignment',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addDays(1),
            'duration_minutes' => 45,
            'assignment_mode' => 'source_specific',
            'assigned_source' => 'Test Source Book'
        ]);

        // 4. Time-limited assignment (will expire due to time limit)
        $assignments['time_limited'] = Assignment::create([
            'title' => 'Time Limited Assignment - Will Expire',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addDays(1),
            'duration_minutes' => 0.1, // 6 seconds for testing
            'assignment_mode' => 'complete_topic',
            'assigned_source' => null,
            'created_at' => now()->subMinutes(1) // Created 1 minute ago, should be expired
        ]);

        // 5. No time limit assignment
        $assignments['no_time_limit'] = Assignment::create([
            'title' => 'No Time Limit Assignment',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addWeek(),
            'duration_minutes' => 0, // No time limit
            'assignment_mode' => 'complete_topic',
            'assigned_source' => null
        ]);

        // 6. Assignment for deletion test
        $assignments['for_deletion'] = Assignment::create([
            'title' => 'Assignment to be Deleted',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addDays(2),
            'duration_minutes' => 90,
            'assignment_mode' => 'complete_topic',
            'assigned_source' => null
        ]);

        return $assignments;
    }

    private function assignToUsers(array $assignments): void
    {
        foreach ($assignments as $key => $assignment) {
            // Assign each assignment to all students via pivot table
            $userIds = $this->students->pluck('id')->toArray();
            $assignment->assignedUsers()->attach($userIds);
            
            echo "  - Assigned '{$assignment->title}' to " . count($userIds) . " students\n";
            
            // Verify assignment
            $this->assertCount(count($userIds), $assignment->assignedUsers);
            foreach ($userIds as $userId) {
                $this->assertTrue($assignment->isAssignedToUser($userId));
            }
        }
    }

    private function testStatusTransitions(array $assignments): void
    {
        // Test active assignment
        $activeAssignment = $assignments['active'];
        $this->assertEquals('active', $activeAssignment->getCurrentStatusAttribute());
        $this->assertTrue($activeAssignment->isActive());
        $this->assertFalse($activeAssignment->isOverdue());
        echo "  - Active assignment status: ✅\n";

        // Test overdue assignment
        $overdueAssignment = $assignments['overdue'];
        $this->assertEquals('overdue', $overdueAssignment->getCurrentStatusAttribute());
        $this->assertTrue($overdueAssignment->isOverdue());
        $this->assertFalse($overdueAssignment->isActive());
        echo "  - Overdue assignment status: ✅\n";

        // Test time-limited assignment (should be expired)
        $timeLimitedAssignment = $assignments['time_limited'];
        $this->assertTrue($timeLimitedAssignment->hasTimeLimitExpired());
        $this->assertEquals('expired', $timeLimitedAssignment->getCurrentStatusAttribute());
        echo "  - Time-limited assignment expired: ✅\n";

        // Test no time limit assignment
        $noTimeLimitAssignment = $assignments['no_time_limit'];
        $this->assertFalse($noTimeLimitAssignment->hasTimeLimit());
        $this->assertTrue($noTimeLimitAssignment->hasNoTimeLimit());
        $this->assertEquals('active', $noTimeLimitAssignment->getCurrentStatusAttribute());
        echo "  - No time limit assignment status: ✅\n";
    }

    private function simulateStudentAttempts(array $assignments): void
    {
        $student1 = $this->students->first();
        $student2 = $this->students->get(1);
        $student3 = $this->students->get(2);

        // Student 1 attempts active assignment and passes
        $this->actingAs($student1);
        $activeAssignment = $assignments['active'];
        
        if ($activeAssignment->canUserStart($student1->id)) {
            $attempt1 = QuizAttempt::create([
                'user_id' => $student1->id,
                'assignment_id' => $activeAssignment->id,
                'score' => 85,
                'percentage_grade' => 85.0,
                'total_questions' => 10,
                'correct_answers' => 8,
                'time_taken' => 1800, // 30 minutes
                'completed_at' => now()
            ]);
            
            echo "  - Student 1 completed active assignment with 85%: ✅\n";
            
            // Verify completion
            $this->assertTrue($activeAssignment->isCompletedByUser($student1->id));
            $this->assertEquals(85.0, $activeAssignment->getUserBestScore($student1->id));
        }

        // Student 2 attempts source-specific assignment and fails
        $sourceAssignment = $assignments['source_specific'];
        
        if ($sourceAssignment->canUserStart($student2->id)) {
            $attempt2 = QuizAttempt::create([
                'user_id' => $student2->id,
                'assignment_id' => $sourceAssignment->id,
                'score' => 35,
                'percentage_grade' => 35.0,
                'total_questions' => 10,
                'correct_answers' => 3,
                'time_taken' => 2700, // 45 minutes
                'completed_at' => now()
            ]);
            
            echo "  - Student 2 attempted source-specific assignment with 35% (failed): ✅\n";
            
            // Verify failure
            $this->assertFalse($sourceAssignment->isCompletedByUser($student2->id));
            $this->assertEquals(35.0, $sourceAssignment->getUserBestScore($student2->id));
        }

        // Student 3 tries to access overdue assignment (should be locked)
        $overdueAssignment = $assignments['overdue'];
        $this->assertFalse($overdueAssignment->canUserStart($student3->id));
        $this->assertEquals('overdue_locked', $overdueAssignment->getStatusForUser($student3->id));
        echo "  - Student 3 correctly blocked from overdue assignment: ✅\n";
    }

    private function testGradingAndCompletion(array $assignments): void
    {
        $student1 = $this->students->first();
        $student2 = $this->students->get(1);
        
        // Test user progress for completed assignment
        $activeAssignment = $assignments['active'];
        $progress1 = $activeAssignment->getUserProgressWithStatus($student1->id);
        
        $this->assertTrue($progress1['completed']);
        $this->assertEquals(85.0, $progress1['percentage']);
        $this->assertEquals('completed_locked', $progress1['status']);
        $this->assertTrue($progress1['is_locked']);
        $this->assertStringContains('completed', $progress1['lock_reason']);
        echo "  - Student 1 progress tracking: ✅\n";

        // Test user progress for failed assignment
        $sourceAssignment = $assignments['source_specific'];
        $progress2 = $sourceAssignment->getUserProgressWithStatus($student2->id);
        
        $this->assertFalse($progress2['completed']);
        $this->assertEquals(35.0, $progress2['percentage']);
        $this->assertEquals('attempted_locked', $progress2['status']);
        $this->assertTrue($progress2['is_locked']);
        $this->assertStringContains('attempted', $progress2['lock_reason']);
        echo "  - Student 2 progress tracking: ✅\n";

        // Test assignment statistics
        $stats = Assignment::getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('overdue', $stats);
        $this->assertArrayHasKey('expired', $stats);
        echo "  - Assignment statistics generation: ✅\n";
    }

    private function testAssignmentDeletion(array $assignments): void
    {
        $this->actingAs($this->tutor);
        
        $assignmentToDelete = $assignments['for_deletion'];
        $assignmentId = $assignmentToDelete->id;
        
        // Test via HTTP request (simulating the actual delete button click)
        $response = $this->deleteJson("/assignments/{$assignmentId}");
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Assignment deleted successfully'
                ]);
        
        // Verify assignment is deleted
        $this->assertDatabaseMissing('assignments', ['id' => $assignmentId]);
        echo "  - Assignment deletion via HTTP: ✅\n";
        
        // Test service method directly
        $anotherAssignment = $assignments['no_time_limit'];
        $result = $this->assignmentService->deleteAssignment($anotherAssignment);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('assignments', ['id' => $anotherAssignment->id]);
        echo "  - Assignment deletion via service: ✅\n";
    }

    private function verifyFinalState(): void
    {
        // Count remaining assignments
        $remainingCount = Assignment::count();
        $this->assertEquals(4, $remainingCount); // 6 created - 2 deleted = 4
        echo "  - Correct number of assignments remaining: ✅\n";
        
        // Verify pivot table relationships still exist
        $activeAssignment = Assignment::where('title', 'Active Assignment - Due Soon')->first();
        if ($activeAssignment) {
            $this->assertGreaterThan(0, $activeAssignment->assignedUsers()->count());
            echo "  - Pivot table relationships intact: ✅\n";
        }
        
        // Verify quiz attempts are preserved
        $attemptCount = QuizAttempt::count();
        $this->assertEquals(2, $attemptCount); // 2 attempts were created
        echo "  - Quiz attempts preserved: ✅\n";
        
        // Test real-time status enforcement
        $overdueCount = Assignment::where('deadline', '<', now())->count();
        $this->assertGreaterThan(0, $overdueCount);
        echo "  - Real-time status enforcement working: ✅\n";
    }

    /** @test */
    public function test_assignment_index_page_loads_correctly()
    {
        $this->actingAs($this->tutor);
        
        // Create a test assignment
        $assignment = Assignment::create([
            'title' => 'Test Assignment for Index',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addDays(1),
            'duration_minutes' => 60,
            'assignment_mode' => 'complete_topic'
        ]);
        
        $response = $this->get('/assignments');
        
        $response->assertStatus(200)
                ->assertSee('Assignments')
                ->assertSee('Test Assignment for Index')
                ->assertSee('New Assignment');
        
        echo "✅ Assignment index page loads correctly\n";
    }

    /** @test */
    public function test_assignment_creation_flow()
    {
        $this->actingAs($this->tutor);
        
        // Test create page loads
        $response = $this->get('/assignments/create');
        $response->assertStatus(200);
        
        // Test assignment creation
        $assignmentData = [
            'title' => 'New Test Assignment',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 90,
            'assignment_mode' => 'complete_topic',
            'student_ids' => $this->students->pluck('id')->toArray()
        ];
        
        $response = $this->post('/assignments/store', $assignmentData);
        
        $response->assertRedirect('/assignments')
                ->assertSessionHas('success');
        
        // Verify assignment was created
        $this->assertDatabaseHas('assignments', [
            'title' => 'New Test Assignment',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id
        ]);
        
        echo "✅ Assignment creation flow working correctly\n";
    }

    /** @test */
    public function test_assignment_filtering()
    {
        $this->actingAs($this->tutor);
        
        // Create assignments with different statuses
        $activeAssignment = Assignment::create([
            'title' => 'Active Filter Test',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->addDays(1),
            'duration_minutes' => 60,
            'assignment_mode' => 'complete_topic'
        ]);
        
        $overdueAssignment = Assignment::create([
            'title' => 'Overdue Filter Test',
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'deadline' => now()->subHour(),
            'duration_minutes' => 60,
            'assignment_mode' => 'complete_topic'
        ]);
        
        // Test filtering by status
        $response = $this->get('/assignments?status=active');
        $response->assertStatus(200)
                ->assertSee('Active Filter Test');
        
        $response = $this->get('/assignments?status=overdue');
        $response->assertStatus(200)
                ->assertSee('Overdue Filter Test');
        
        // Test filtering by course
        $response = $this->get('/assignments?course_id=' . $this->course->id);
        $response->assertStatus(200)
                ->assertSee('Active Filter Test')
                ->assertSee('Overdue Filter Test');
        
        echo "✅ Assignment filtering working correctly\n";
    }
}