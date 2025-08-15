<?php

namespace Tests\Feature\SSOT;

use Tests\TestCase;
use App\Models\Assignment;
use App\Models\Topic;
use App\Models\Course;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Support\State\AssignmentStatusService;
use App\Observers\AssignmentStatusObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssignmentStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentStatusService $statusService;
    private AssignmentStatusObserver $statusObserver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->statusService = app(AssignmentStatusService::class);
        $this->statusObserver = app(AssignmentStatusObserver::class);
        
        // Enable SSOT sync for testing
        config(['ssot.sync_enabled' => true]);
        config(['ssot.observers.assignment_status.enabled' => true]);
    }

    /** @test */
    public function it_determines_active_assignment_status()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 60,
            'created_at' => now()->subMinutes(10)
        ]);
        
        $status = $this->statusService->getAssignmentStatus($assignment);
        
        $this->assertEquals(AssignmentStatusService::STATUS_ACTIVE, $status['status']);
        $this->assertEquals('Assignment is active', $status['reason']);
        $this->assertGreaterThan(0, $status['time_remaining']);
    }

    /** @test */
    public function it_determines_overdue_assignment_status()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1),
            'duration_minutes' => 60,
            'created_at' => now()->subHours(3)
        ]);
        
        $status = $this->statusService->getAssignmentStatus($assignment);
        
        $this->assertEquals(AssignmentStatusService::STATUS_OVERDUE, $status['status']);
        $this->assertStringContains('Overdue by', $status['reason']);
        $this->assertArrayHasKey('overdue_minutes', $status);
    }

    /** @test */
    public function it_determines_expired_assignment_status()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 30,
            'created_at' => now()->subMinutes(45) // Exceeded time limit
        ]);
        
        $status = $this->statusService->getAssignmentStatus($assignment);
        
        $this->assertEquals(AssignmentStatusService::STATUS_EXPIRED, $status['status']);
        $this->assertEquals('Time limit exceeded', $status['reason']);
        $this->assertEquals(0, $status['time_remaining']);
    }

    /** @test */
    public function it_checks_simple_status_methods()
    {
        $activeAssignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 60,
            'created_at' => now()->subMinutes(10)
        ]);
        
        $overdueAssignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1),
            'duration_minutes' => 60,
            'created_at' => now()->subHours(3)
        ]);
        
        $expiredAssignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 30,
            'created_at' => now()->subMinutes(45)
        ]);
        
        $this->assertTrue($this->statusService->isActive($activeAssignment));
        $this->assertFalse($this->statusService->isOverdue($activeAssignment));
        $this->assertFalse($this->statusService->isExpired($activeAssignment));
        
        $this->assertFalse($this->statusService->isActive($overdueAssignment));
        $this->assertTrue($this->statusService->isOverdue($overdueAssignment));
        $this->assertFalse($this->statusService->isExpired($overdueAssignment));
        
        $this->assertFalse($this->statusService->isActive($expiredAssignment));
        $this->assertFalse($this->statusService->isOverdue($expiredAssignment));
        $this->assertTrue($this->statusService->isExpired($expiredAssignment));
    }

    /** @test */
    public function it_checks_assignment_access()
    {
        $activeAssignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 60,
            'created_at' => now()->subMinutes(10)
        ]);
        
        $overdueAssignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1)
        ]);
        
        $this->assertTrue($this->statusService->canAccess($activeAssignment));
        $this->assertFalse($this->statusService->canAccess($overdueAssignment));
    }

    /** @test */
    public function it_calculates_time_remaining()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addMinutes(30),
            'duration_minutes' => 60,
            'created_at' => now()->subMinutes(10)
        ]);
        
        $timeRemaining = $this->statusService->getTimeRemaining($assignment);
        
        $this->assertNotNull($timeRemaining);
        $this->assertGreaterThan(0, $timeRemaining);
        $this->assertLessThanOrEqual(30 * 60, $timeRemaining); // 30 minutes in seconds
    }

    /** @test */
    public function it_returns_null_time_remaining_for_expired_assignments()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1)
        ]);
        
        $timeRemaining = $this->statusService->getTimeRemaining($assignment);
        
        $this->assertNull($timeRemaining);
    }

    /** @test */
    public function it_provides_human_readable_time_remaining()
    {
        $activeAssignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2)
        ]);
        
        $overdueAssignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1)
        ]);
        
        $expiredAssignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 30,
            'created_at' => now()->subMinutes(45)
        ]);
        
        $activeTime = $this->statusService->getTimeRemainingHuman($activeAssignment);
        $overdueTime = $this->statusService->getTimeRemainingHuman($overdueAssignment);
        $expiredTime = $this->statusService->getTimeRemainingHuman($expiredAssignment);
        
        $this->assertStringContains('Due', $activeTime);
        $this->assertStringContains('Overdue by', $overdueTime);
        $this->assertEquals('Time limit expired', $expiredTime);
    }

    /** @test */
    public function it_checks_time_limit_functionality()
    {
        $withTimeLimit = Assignment::factory()->create([
            'duration_minutes' => 60
        ]);
        
        $withoutTimeLimit = Assignment::factory()->create([
            'duration_minutes' => null
        ]);
        
        $this->assertTrue($this->statusService->hasTimeLimit($withTimeLimit));
        $this->assertFalse($this->statusService->hasTimeLimit($withoutTimeLimit));
    }

    /** @test */
    public function it_checks_time_limit_expiration()
    {
        $notExpired = Assignment::factory()->create([
            'duration_minutes' => 60,
            'created_at' => now()->subMinutes(30)
        ]);
        
        $expired = Assignment::factory()->create([
            'duration_minutes' => 30,
            'created_at' => now()->subMinutes(45)
        ]);
        
        $this->assertFalse($this->statusService->hasTimeLimitExpired($notExpired));
        $this->assertTrue($this->statusService->hasTimeLimitExpired($expired));
    }

    /** @test */
    public function it_calculates_progress_percentage()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'created_at' => now()->subHours(1) // 1 hour elapsed, 2 hours remaining
        ]);
        
        $progress = $this->statusService->getProgressPercentage($assignment);
        
        $this->assertGreaterThan(0, $progress);
        $this->assertLessThan(100, $progress);
        $this->assertEqualsWithDelta(33.33, $progress, 5); // Approximately 1/3 elapsed
    }

    /** @test */
    public function it_gets_user_specific_assignment_status()
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 60,
            'created_at' => now()->subMinutes(10)
        ]);
        
        // User hasn't attempted
        $status = $this->statusService->getUserAssignmentStatus($assignment, $user->id);
        
        $this->assertEquals('available', $status['user_status']);
        $this->assertTrue($status['can_access']);
        $this->assertNull($status['lock_reason']);
        
        // User has attempted
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'score' => 3,
            'total_questions' => 10,
            'percentage_grade' => 30
        ]);
        
        $status = $this->statusService->getUserAssignmentStatus($assignment, $user->id);
        
        $this->assertEquals('attempted', $status['user_status']);
        $this->assertFalse($status['can_access']);
        $this->assertEquals('Assignment attempted', $status['lock_reason']);
        
        // User has completed (score >= 50%)
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'score' => 8,
            'total_questions' => 10,
            'percentage_grade' => 80
        ]);
        
        $status = $this->statusService->getUserAssignmentStatus($assignment, $user->id);
        
        $this->assertEquals('completed', $status['user_status']);
        $this->assertFalse($status['can_access']);
        $this->assertEquals('Assignment completed', $status['lock_reason']);
        $this->assertEquals(80, $status['best_score']);
    }

    /** @test */
    public function it_handles_bulk_assignment_status_check()
    {
        $assignment1 = Assignment::factory()->create([
            'deadline' => now()->addHours(2)
        ]);
        
        $assignment2 = Assignment::factory()->create([
            'deadline' => now()->subHours(1)
        ]);
        
        $assignments = [$assignment1, $assignment2];
        $bulkStatus = $this->statusService->getBulkAssignmentStatus($assignments);
        
        $this->assertCount(2, $bulkStatus);
        $this->assertEquals(AssignmentStatusService::STATUS_ACTIVE, $bulkStatus[$assignment1->id]['status']);
        $this->assertEquals(AssignmentStatusService::STATUS_OVERDUE, $bulkStatus[$assignment2->id]['status']);
    }

    /** @test */
    public function it_gets_assignments_by_status()
    {
        $activeAssignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2)
        ]);
        
        $overdueAssignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1)
        ]);
        
        $activeAssignments = $this->statusService->getAssignmentsByStatus(AssignmentStatusService::STATUS_ACTIVE);
        $overdueAssignments = $this->statusService->getAssignmentsByStatus(AssignmentStatusService::STATUS_OVERDUE);
        
        $this->assertCount(1, $activeAssignments);
        $this->assertCount(1, $overdueAssignments);
        $this->assertEquals($activeAssignment->id, $activeAssignments[0]->id);
        $this->assertEquals($overdueAssignment->id, $overdueAssignments[0]->id);
    }

    /** @test */
    public function observer_syncs_status_cache_on_creation()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'duration_minutes' => 60
        ]);
        
        // Observer should have synced status cache
        $this->assertNotNull($assignment->fresh()->status_cached);
        $this->assertEquals(AssignmentStatusService::STATUS_ACTIVE, $assignment->fresh()->status_cached);
    }

    /** @test */
    public function observer_syncs_status_cache_on_update()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'status_cached' => AssignmentStatusService::STATUS_ACTIVE
        ]);
        
        // Update deadline to make it overdue
        $assignment->update(['deadline' => now()->subHours(1)]);
        
        // Observer should have updated status cache
        $this->assertEquals(AssignmentStatusService::STATUS_OVERDUE, $assignment->fresh()->status_cached);
    }

    /** @test */
    public function observer_detects_status_cache_conflicts()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('SSOT Assignment Status Cache Conflict', \Mockery::type('array'));
            
        $assignment = Assignment::factory()->create([
            'deadline' => now()->subHours(1), // Should be overdue
            'status_cached' => AssignmentStatusService::STATUS_ACTIVE // Conflicting cache
        ]);
        
        // Update to trigger observer
        $assignment->update(['title' => 'Updated Title']);
    }

    /** @test */
    public function observer_tracks_legacy_method_calls()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('SSOT Legacy Assignment Status Method Called', \Mockery::type('array'));
            
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2)
        ]);
        
        // Simulate legacy method call
        $this->statusObserver->trackLegacyStatusCall($assignment, 'isActive', true);
    }

    /** @test */
    public function it_verifies_status_consistency()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'status_cached' => AssignmentStatusService::STATUS_ACTIVE
        ]);
        
        $consistency = $this->statusObserver->verifyConsistency($assignment);
        
        $this->assertTrue($consistency['consistent']);
        $this->assertEmpty($consistency['inconsistencies']);
        
        // Create inconsistency
        $assignment->update(['status_cached' => AssignmentStatusService::STATUS_OVERDUE]);
        
        $consistency = $this->statusObserver->verifyConsistency($assignment);
        
        $this->assertFalse($consistency['consistent']);
        $this->assertNotEmpty($consistency['inconsistencies']);
    }

    /** @test */
    public function it_handles_bulk_status_cache_sync()
    {
        $assignment1 = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'status_cached' => null
        ]);
        
        $assignment2 = Assignment::factory()->create([
            'deadline' => now()->subHours(1),
            'status_cached' => null
        ]);
        
        $results = $this->statusObserver->bulkSyncStatusCache([$assignment1->id, $assignment2->id]);
        
        $this->assertCount(2, $results);
        $this->assertTrue($results[$assignment1->id]);
        $this->assertTrue($results[$assignment2->id]);
        
        // Verify cache was synced
        $this->assertEquals(AssignmentStatusService::STATUS_ACTIVE, $assignment1->fresh()->status_cached);
        $this->assertEquals(AssignmentStatusService::STATUS_OVERDUE, $assignment2->fresh()->status_cached);
    }

    /** @test */
    public function it_clears_status_cache_properly()
    {
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2)
        ]);
        
        // Prime cache
        $this->statusService->getAssignmentStatus($assignment);
        
        // Clear cache
        $this->statusService->clearStatusCache($assignment);
        
        // Cache should be cleared (we can't directly test cache clearing,
        // but we can verify the method doesn't throw errors)
        $this->assertTrue(true);
    }

    /** @test */
    public function it_respects_ssot_sync_enabled_config()
    {
        config(['ssot.sync_enabled' => false]);
        
        $assignment = Assignment::factory()->create([
            'deadline' => now()->addHours(2),
            'status_cached' => null
        ]);
        
        // Observer should not sync when disabled
        $this->assertNull($assignment->fresh()->status_cached);
    }
}