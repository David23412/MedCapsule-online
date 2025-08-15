<?php

namespace Tests\Feature\SSOT;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Assignment;
use App\Models\QuizAttempt;
use App\Support\State\EnrollmentService;
use App\Support\State\UserProgressService;
use App\Support\State\AssignmentStatusService;

/**
 * SSOT Compliance Test Suite
 * 
 * This test suite enforces the critical requirement that NO legacy field reads
 * occur in controllers or models. It also verifies SSOT data consistency.
 */
class SSOTComplianceTest extends TestCase
{
    use RefreshDatabase;

    private EnrollmentService $enrollmentService;
    private UserProgressService $progressService;
    private AssignmentStatusService $assignmentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->enrollmentService = app(EnrollmentService::class);
        $this->progressService = app(UserProgressService::class);
        $this->assignmentService = app(AssignmentStatusService::class);
    }

    /**
     * @test
     * CRITICAL: Ensure no is_enrolled reads in controllers
     */
    public function it_prevents_is_enrolled_reads_in_controllers()
    {
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        $violations = [];

        foreach ($controllerFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Check for direct is_enrolled field access
            if (preg_match('/\$\w+->is_enrolled(?!\s*=)/', $content, $matches)) {
                $violations[] = [
                    'file' => $file->getRelativePathname(),
                    'violation' => 'Direct is_enrolled field read',
                    'match' => $matches[0]
                ];
            }
            
            // Check for query builder is_enrolled reads
            if (preg_match('/->where\([\'"]is_enrolled[\'"]/', $content, $matches)) {
                $violations[] = [
                    'file' => $file->getRelativePathname(),
                    'violation' => 'Query builder is_enrolled read',
                    'match' => $matches[0]
                ];
            }
        }

        $this->assertEmpty($violations, 
            'Found is_enrolled reads in controllers: ' . json_encode($violations, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @test
     * CRITICAL: Ensure no XP field reads in controllers
     */
    public function it_prevents_xp_reads_in_controllers()
    {
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        $violations = [];

        foreach ($controllerFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Check for direct XP field access (excluding assignments)
            if (preg_match('/\$\w+->xp(?!\s*=)/', $content, $matches)) {
                $violations[] = [
                    'file' => $file->getRelativePathname(),
                    'violation' => 'Direct xp field read',
                    'match' => $matches[0]
                ];
            }
            
            // Check for correct_answers_count reads
            if (preg_match('/\$\w+->correct_answers_count(?!\s*=)/', $content, $matches)) {
                $violations[] = [
                    'file' => $file->getRelativePathname(),
                    'violation' => 'Direct correct_answers_count field read',
                    'match' => $matches[0]
                ];
            }
        }

        $this->assertEmpty($violations, 
            'Found XP/progress reads in controllers: ' . json_encode($violations, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @test
     * CRITICAL: Ensure models use SSOT services instead of direct field access
     */
    public function it_prevents_legacy_reads_in_models()
    {
        $modelFiles = File::allFiles(app_path('Models'));
        $violations = [];

        foreach ($modelFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Skip if file contains SSOT service injection (allowed)
            if (strpos($content, 'EnrollmentService') !== false || 
                strpos($content, 'UserProgressService') !== false) {
                continue;
            }
            
            // Check for legacy field computations in accessors
            if (preg_match('/function\s+get\w*Attribute.*is_enrolled/', $content, $matches)) {
                $violations[] = [
                    'file' => $file->getRelativePathname(),
                    'violation' => 'Legacy is_enrolled in accessor',
                    'match' => trim($matches[0])
                ];
            }
            
            if (preg_match('/function\s+get\w*Attribute.*correct_answers_count/', $content, $matches)) {
                $violations[] = [
                    'file' => $file->getRelativePathname(),
                    'violation' => 'Legacy correct_answers_count in accessor',
                    'match' => trim($matches[0])
                ];
            }
        }

        $this->assertEmpty($violations, 
            'Found legacy reads in models: ' . json_encode($violations, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @test
     * CRITICAL: Verify SSOT data matches legacy data in dual-write mode
     */
    public function it_verifies_ssot_data_matches_legacy_data()
    {
        // Create test data
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Create completed payment (should trigger enrollment)
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed',
            'paid_at' => now()
        ]);

        // Allow observers to process
        $this->artisan('cache:clear');
        
        // Get SSOT status
        $ssotStatus = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        // Get legacy data
        $legacyEnrollment = \DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        // Verify consistency
        $this->assertTrue($ssotStatus['enrolled'], 'SSOT should show user as enrolled');
        
        if ($legacyEnrollment) {
            $this->assertEquals(
                $ssotStatus['enrolled'], 
                (bool) $legacyEnrollment->is_enrolled,
                'SSOT enrollment status must match legacy is_enrolled field'
            );
        }
    }

    /**
     * @test
     * CRITICAL: Verify user progress SSOT matches legacy cached fields
     */
    public function it_verifies_progress_ssot_matches_legacy_cache()
    {
        // Create user with quiz attempts
        $user = User::factory()->create();
        
        // Create quiz attempts with known scores
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'score' => 8,
            'total_questions' => 10
        ]);
        
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'score' => 6,
            'total_questions' => 10
        ]);

        // Get SSOT progress
        $ssotProgress = $this->progressService->getUserProgress($user->id);
        
        // Get legacy cached value
        $user->refresh();
        
        // Verify consistency (if legacy field exists)
        if ($user->correct_answers_count !== null) {
            $this->assertEquals(
                $ssotProgress['total_correct_answers'],
                $user->correct_answers_count,
                'SSOT total correct answers must match legacy cached field'
            );
        }
        
        // Verify SSOT calculation is correct
        $expectedCorrect = 8 + 6; // Sum of scores
        $this->assertEquals($expectedCorrect, $ssotProgress['total_correct_answers']);
    }

    /**
     * @test
     * CRITICAL: Verify assignment status SSOT works correctly
     */
    public function it_verifies_assignment_status_ssot_accuracy()
    {
        // Create active assignment
        $assignment = Assignment::factory()->create([
            'start_date' => now()->subHour(),
            'due_date' => now()->addHour(),
            'duration_minutes' => 120
        ]);

        // Get SSOT status
        $ssotStatus = $this->assignmentService->getAssignmentStatus($assignment);
        
        // Verify status is active
        $this->assertEquals('active', $ssotStatus['status']);
        $this->assertTrue($ssotStatus['is_active']);
        $this->assertFalse($ssotStatus['is_overdue']);
        $this->assertFalse($ssotStatus['is_expired']);
        
        // Create overdue assignment
        $overdueAssignment = Assignment::factory()->create([
            'start_date' => now()->subDays(2),
            'due_date' => now()->subDay(),
            'duration_minutes' => 60
        ]);

        $overdueStatus = $this->assignmentService->getAssignmentStatus($overdueAssignment);
        
        $this->assertEquals('overdue', $overdueStatus['status']);
        $this->assertFalse($overdueStatus['is_active']);
        $this->assertTrue($overdueStatus['is_overdue']);
    }

    /**
     * @test
     * CRITICAL: Ensure API endpoints use SSOT services
     */
    public function it_verifies_api_endpoints_use_ssot()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 0]); // Free course
        
        // Test enrollment API
        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/enrollment/{$course->id}");
        
        $response->assertOk();
        
        // Verify response structure matches SSOT service
        $ssotStatus = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        $response->assertJson([
            'enrolled' => $ssotStatus['enrolled'],
            'method' => $ssotStatus['method']
        ]);
    }

    /**
     * @test
     * CRITICAL: Verify no legacy field queries in recent code
     */
    public function it_prevents_new_legacy_queries()
    {
        // Get recent commits (simulate by checking for patterns)
        $recentFiles = [
            app_path('Http/Controllers'),
            app_path('Models'),
            app_path('Services')
        ];

        $violations = [];
        
        foreach ($recentFiles as $directory) {
            if (!is_dir($directory)) continue;
            
            $files = File::allFiles($directory);
            
            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                
                // Check for new legacy patterns
                $legacyPatterns = [
                    '/->where\([\'"]is_enrolled[\'"]/' => 'is_enrolled query',
                    '/->where\([\'"]correct_answers_count[\'"]/' => 'correct_answers_count query',
                    '/\$\w+->is_enrolled(?!\s*=)/' => 'is_enrolled field read',
                    '/\$\w+->correct_answers_count(?!\s*=)/' => 'correct_answers_count field read'
                ];
                
                foreach ($legacyPatterns as $pattern => $description) {
                    if (preg_match($pattern, $content, $matches)) {
                        // Skip if it's in a comment or SSOT service
                        $line = $this->getLineContaining($content, $matches[0]);
                        if (strpos($line, '//') === false && 
                            strpos($line, 'SSOT') === false &&
                            strpos($file->getPathname(), 'Observer') === false) {
                            
                            $violations[] = [
                                'file' => $file->getRelativePathname(),
                                'violation' => $description,
                                'match' => $matches[0],
                                'line' => trim($line)
                            ];
                        }
                    }
                }
            }
        }

        $this->assertEmpty($violations, 
            'Found new legacy field usage: ' . json_encode($violations, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Helper method to get the line containing a match
     */
    private function getLineContaining(string $content, string $match): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strpos($line, $match) !== false) {
                return $line;
            }
        }
        return '';
    }

    /**
     * @test
     * CRITICAL: Verify dual-write observers are working
     */
    public function it_verifies_dual_write_observers_are_active()
    {
        // Test enrollment observer
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Create payment - should trigger observer
        Log::shouldReceive('info')
            ->with('SSOT Payment Enrollment Processing', \Mockery::type('array'))
            ->once();
            
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed',
            'paid_at' => now()
        ]);

        // Verify enrollment was created
        $enrollment = \DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        $this->assertNotNull($enrollment, 'Observer should create enrollment record');
        $this->assertTrue((bool) $enrollment->is_enrolled);
    }
}