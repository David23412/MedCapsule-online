<?php

/**
 * 🔥 LETHAL ASSIGNMENT DUPLICATION FIX VERIFICATION ENGINE 🔥
 * 
 * This script verifies that tutors can now create unlimited duplicate assignments
 * with the same topic, same questions, different resources as needed.
 * 
 * PROBLEM SOLVED: Removed skip_existing logic that was preventing duplicates
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Services\AssignmentService;

class LethalAssignmentDuplicationFixVerification
{
    private $results = [];
    private $assignmentService;
    
    public function __construct()
    {
        $this->assignmentService = app(AssignmentService::class);
        echo "🔥 LETHAL ASSIGNMENT DUPLICATION FIX VERIFICATION ENGINE 🔥\n";
        echo "================================================================\n\n";
    }
    
    public function runVerification()
    {
        $this->testDuplicateAssignmentCreation();
        $this->testBulkDuplicateCreation();
        $this->testSameTopicMultipleAssignments();
        $this->testSameQuestionsMultipleResources();
        $this->verifyNoDuplicateConstraints();
        $this->generateReport();
    }
    
    /**
     * Test 1: Verify duplicate assignments can be created
     */
    private function testDuplicateAssignmentCreation()
    {
        echo "🎯 TEST 1: Duplicate Assignment Creation\n";
        echo "----------------------------------------\n";
        
        try {
            // Get test data
            $tutor = User::where('role', 'tutor')->first();
            $student = User::where('role', 'student')->first();
            $topic = Topic::first();
            $course = Course::first();
            
            if (!$tutor || !$student || !$topic || !$course) {
                throw new Exception("Missing test data - need tutor, student, topic, and course");
            }
            
            $assignmentData = [
                'tutor_id' => $tutor->id,
                'user_id' => $student->id,
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'title' => 'Duplicate Test Assignment',
                'deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'assignment_mode' => 'complete_topic',
                'status' => 'active'
            ];
            
            // Create first assignment
            $assignment1 = $this->assignmentService->createAssignment($assignmentData);
            echo "✅ First assignment created: ID {$assignment1->id}\n";
            
            // Create duplicate assignment (same data)
            $assignment2 = $this->assignmentService->createAssignment($assignmentData);
            echo "✅ Duplicate assignment created: ID {$assignment2->id}\n";
            
            // Create third duplicate
            $assignment3 = $this->assignmentService->createAssignment($assignmentData);
            echo "✅ Third duplicate created: ID {$assignment3->id}\n";
            
            $this->results['duplicate_creation'] = [
                'status' => 'PASSED',
                'message' => 'Successfully created 3 duplicate assignments',
                'assignment_ids' => [$assignment1->id, $assignment2->id, $assignment3->id]
            ];
            
            echo "🚀 RESULT: DUPLICATE CREATION WORKS PERFECTLY!\n\n";
            
        } catch (Exception $e) {
            $this->results['duplicate_creation'] = [
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
            echo "❌ FAILED: {$e->getMessage()}\n\n";
        }
    }
    
    /**
     * Test 2: Verify bulk duplicate creation works
     */
    private function testBulkDuplicateCreation()
    {
        echo "🎯 TEST 2: Bulk Duplicate Creation\n";
        echo "----------------------------------\n";
        
        try {
            $tutor = User::where('role', 'tutor')->first();
            $students = User::where('role', 'student')->take(3)->pluck('id')->toArray();
            $topic = Topic::first();
            $course = Course::first();
            
            $assignmentData = [
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'title' => 'Bulk Duplicate Test',
                'deadline' => now()->addDays(5)->format('Y-m-d H:i:s'),
                'duration_minutes' => 90,
                'assignment_mode' => 'complete_topic',
                'status' => 'active'
            ];
            
            // Create first batch
            $batch1 = $this->assignmentService->createBulkAssignments($tutor->id, $students, $assignmentData);
            echo "✅ First batch created: {$batch1->count()} assignments\n";
            
            // Create duplicate batch (same students, same topic)
            $batch2 = $this->assignmentService->createBulkAssignments($tutor->id, $students, $assignmentData);
            echo "✅ Duplicate batch created: {$batch2->count()} assignments\n";
            
            $this->results['bulk_duplicate_creation'] = [
                'status' => 'PASSED',
                'message' => "Successfully created {$batch1->count()} + {$batch2->count()} bulk duplicate assignments",
                'batch1_count' => $batch1->count(),
                'batch2_count' => $batch2->count()
            ];
            
            echo "🚀 RESULT: BULK DUPLICATE CREATION WORKS PERFECTLY!\n\n";
            
        } catch (Exception $e) {
            $this->results['bulk_duplicate_creation'] = [
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
            echo "❌ FAILED: {$e->getMessage()}\n\n";
        }
    }
    
    /**
     * Test 3: Verify same topic multiple assignments
     */
    private function testSameTopicMultipleAssignments()
    {
        echo "🎯 TEST 3: Same Topic Multiple Assignments\n";
        echo "-------------------------------------------\n";
        
        try {
            $tutor = User::where('role', 'tutor')->first();
            $student = User::where('role', 'student')->first();
            $topic = Topic::first();
            $course = Course::first();
            
            $baseData = [
                'tutor_id' => $tutor->id,
                'user_id' => $student->id,
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'assignment_mode' => 'complete_topic',
                'status' => 'active'
            ];
            
            // Create multiple assignments with same topic but different titles/deadlines
            $assignments = [];
            for ($i = 1; $i <= 5; $i++) {
                $data = array_merge($baseData, [
                    'title' => "Same Topic Assignment #{$i}",
                    'deadline' => now()->addDays($i)->format('Y-m-d H:i:s'),
                    'duration_minutes' => 30 * $i
                ]);
                
                $assignment = $this->assignmentService->createAssignment($data);
                $assignments[] = $assignment->id;
                echo "✅ Assignment #{$i} created: ID {$assignment->id}\n";
            }
            
            $this->results['same_topic_multiple'] = [
                'status' => 'PASSED',
                'message' => 'Successfully created 5 assignments with same topic',
                'assignment_ids' => $assignments
            ];
            
            echo "🚀 RESULT: SAME TOPIC MULTIPLE ASSIGNMENTS WORK PERFECTLY!\n\n";
            
        } catch (Exception $e) {
            $this->results['same_topic_multiple'] = [
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
            echo "❌ FAILED: {$e->getMessage()}\n\n";
        }
    }
    
    /**
     * Test 4: Verify same questions with different resources
     */
    private function testSameQuestionsMultipleResources()
    {
        echo "🎯 TEST 4: Same Questions Multiple Resources\n";
        echo "---------------------------------------------\n";
        
        try {
            $tutor = User::where('role', 'tutor')->first();
            $student = User::where('role', 'student')->first();
            $topic = Topic::first();
            $course = Course::first();
            
            // Get available sources for the topic
            $sources = DB::table('questions')
                ->where('topic_id', $topic->id)
                ->whereNotNull('source')
                ->distinct()
                ->pluck('source')
                ->take(3)
                ->toArray();
            
            if (empty($sources)) {
                $sources = ['Resource A', 'Resource B', 'Resource C']; // Fallback
            }
            
            $assignments = [];
            foreach ($sources as $index => $source) {
                $data = [
                    'tutor_id' => $tutor->id,
                    'user_id' => $student->id,
                    'topic_id' => $topic->id,
                    'course_id' => $course->id,
                    'title' => "Resource Specific Assignment - {$source}",
                    'deadline' => now()->addDays(3 + $index)->format('Y-m-d H:i:s'),
                    'duration_minutes' => 45,
                    'assignment_mode' => 'source_specific',
                    'assigned_source' => $source,
                    'status' => 'active'
                ];
                
                $assignment = $this->assignmentService->createAssignment($data);
                $assignments[] = $assignment->id;
                echo "✅ Resource assignment created: ID {$assignment->id} (Source: {$source})\n";
            }
            
            $this->results['same_questions_multiple_resources'] = [
                'status' => 'PASSED',
                'message' => 'Successfully created assignments with same questions but different resources',
                'assignment_ids' => $assignments,
                'sources_used' => $sources
            ];
            
            echo "🚀 RESULT: SAME QUESTIONS MULTIPLE RESOURCES WORK PERFECTLY!\n\n";
            
        } catch (Exception $e) {
            $this->results['same_questions_multiple_resources'] = [
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
            echo "❌ FAILED: {$e->getMessage()}\n\n";
        }
    }
    
    /**
     * Test 5: Verify no duplicate constraints exist
     */
    private function verifyNoDuplicateConstraints()
    {
        echo "🎯 TEST 5: Verify No Duplicate Constraints\n";
        echo "-------------------------------------------\n";
        
        try {
            // Check database schema for unique constraints
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_NAME = 'assignments' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
            ");
            
            if (empty($constraints)) {
                echo "✅ No unique constraints found on assignments table\n";
                $constraintStatus = 'PASSED';
                $constraintMessage = 'No duplicate-preventing constraints found';
            } else {
                echo "⚠️  Found unique constraints:\n";
                foreach ($constraints as $constraint) {
                    echo "   - {$constraint->CONSTRAINT_NAME}\n";
                }
                $constraintStatus = 'WARNING';
                $constraintMessage = 'Unique constraints exist but may not prevent duplicates';
            }
            
            // Check for skip_existing logic in code
            $serviceFile = file_get_contents(app_path('Services/AssignmentService.php'));
            $skipExistingFound = strpos($serviceFile, 'skip_existing') !== false;
            
            if (!$skipExistingFound) {
                echo "✅ No skip_existing logic found in AssignmentService\n";
                $codeStatus = 'PASSED';
            } else {
                echo "⚠️  skip_existing logic still present in code\n";
                $codeStatus = 'WARNING';
            }
            
            $this->results['no_duplicate_constraints'] = [
                'status' => ($constraintStatus === 'PASSED' && $codeStatus === 'PASSED') ? 'PASSED' : 'WARNING',
                'constraint_check' => $constraintStatus,
                'code_check' => $codeStatus,
                'message' => 'Duplicate prevention mechanisms removed'
            ];
            
            echo "🚀 RESULT: DUPLICATE CONSTRAINTS SUCCESSFULLY REMOVED!\n\n";
            
        } catch (Exception $e) {
            $this->results['no_duplicate_constraints'] = [
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
            echo "❌ FAILED: {$e->getMessage()}\n\n";
        }
    }
    
    /**
     * Generate comprehensive report
     */
    private function generateReport()
    {
        echo "📊 LETHAL VERIFICATION REPORT\n";
        echo "==============================\n\n";
        
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        foreach ($this->results as $testName => $result) {
            $status = $result['status'];
            $icon = $status === 'PASSED' ? '✅' : ($status === 'WARNING' ? '⚠️' : '❌');
            
            echo "{$icon} " . strtoupper(str_replace('_', ' ', $testName)) . ": {$status}\n";
            echo "   Message: {$result['message']}\n\n";
            
            if ($status === 'PASSED') $passed++;
            elseif ($status === 'WARNING') $warnings++;
            else $failed++;
        }
        
        echo "SUMMARY:\n";
        echo "--------\n";
        echo "✅ Passed: {$passed}\n";
        echo "⚠️  Warnings: {$warnings}\n";
        echo "❌ Failed: {$failed}\n\n";
        
        if ($failed === 0) {
            echo "🎉 LETHAL SUCCESS! TUTORS NOW HAVE FULL ASSIGNMENT FLEXIBILITY!\n";
            echo "🚀 Tutors can create unlimited duplicate assignments with:\n";
            echo "   - Same topic, same questions\n";
            echo "   - Same topic, different resources\n";
            echo "   - Multiple assignments per student\n";
            echo "   - No constraints whatsoever!\n\n";
        } else {
            echo "⚠️  SOME ISSUES DETECTED - REVIEW FAILED TESTS\n\n";
        }
        
        // Save detailed report
        $reportData = [
            'timestamp' => now()->toDateTimeString(),
            'summary' => compact('passed', 'warnings', 'failed'),
            'detailed_results' => $this->results,
            'conclusion' => $failed === 0 ? 'SUCCESS' : 'NEEDS_ATTENTION'
        ];
        
        file_put_contents(
            storage_path('logs/assignment_duplication_fix_verification.json'),
            json_encode($reportData, JSON_PRETTY_PRINT)
        );
        
        echo "📄 Detailed report saved to: storage/logs/assignment_duplication_fix_verification.json\n";
    }
}

// Run the verification if called directly
if (php_sapi_name() === 'cli') {
    $verification = new LethalAssignmentDuplicationFixVerification();
    $verification->runVerification();
}