<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Services\AssignmentService;
use App\Services\NotificationService;

echo "=== Assignment Creation Test ===\n\n";

try {
    // Get test data
    $tutor = User::where('role', 'tutor')->first();
    $student = User::where('role', 'student')->first();
    $course = Course::first();
    $topic = Topic::where('course_id', $course->id)->first();

    if (!$tutor || !$student || !$course || !$topic) {
        echo "❌ Missing test data (tutor, student, course, or topic)\n";
        exit(1);
    }

    echo "Using:\n";
    echo "- Tutor: {$tutor->name}\n";
    echo "- Student: {$student->name}\n";
    echo "- Course: {$course->name}\n";
    echo "- Topic: {$topic->name}\n\n";

    // Initialize service
    $notificationService = new NotificationService();
    $assignmentService = new AssignmentService($notificationService);

    // Test 1: Complete topic assignment (should work)
    echo "Test 1: Creating complete topic assignment...\n";
    try {
        $assignment1 = $assignmentService->createAssignment([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Complete Topic Assignment',
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'assignment_mode' => 'complete_topic'
        ]);
        echo "✅ Complete topic assignment created successfully (ID: {$assignment1->id})\n";
    } catch (Exception $e) {
        echo "❌ Complete topic assignment failed: " . $e->getMessage() . "\n";
    }

    // Test 2: Source-specific assignment without source (should fail)
    echo "\nTest 2: Creating source-specific assignment without source...\n";
    try {
        $assignment2 = $assignmentService->createAssignment([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Source Specific Assignment',
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'assignment_mode' => 'source_specific'
            // Missing assigned_source
        ]);
        echo "❌ Source-specific assignment should have failed but didn't\n";
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'Source must be specified')) {
            echo "✅ Source-specific assignment correctly failed: " . $e->getMessage() . "\n";
        } else {
            echo "❌ Source-specific assignment failed with unexpected error: " . $e->getMessage() . "\n";
        }
    }

    // Test 3: Source-specific assignment with valid source (should work)
    echo "\nTest 3: Creating source-specific assignment with valid source...\n";
    
    // Get a valid source for this topic
    $validSource = DB::table('questions')
        ->where('topic_id', $topic->id)
        ->whereNotNull('source')
        ->where('source', '!=', '')
        ->value('source');

    if ($validSource) {
        try {
            $assignment3 = $assignmentService->createAssignment([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'title' => 'Test Source Specific Assignment with Source',
                'deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'assignment_mode' => 'source_specific',
                'assigned_source' => $validSource
            ]);
            echo "✅ Source-specific assignment with source created successfully (ID: {$assignment3->id})\n";
        } catch (Exception $e) {
            echo "❌ Source-specific assignment with source failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️  No valid sources found for topic {$topic->name}\n";
    }

    // Test 4: Default assignment mode (should work)
    echo "\nTest 4: Creating assignment with default mode...\n";
    try {
        $assignment4 = $assignmentService->createAssignment([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Test Default Assignment',
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s')
            // No assignment_mode specified - should default to complete_topic
        ]);
        echo "✅ Default assignment created successfully (ID: {$assignment4->id}, Mode: {$assignment4->assignment_mode})\n";
    } catch (Exception $e) {
        echo "❌ Default assignment failed: " . $e->getMessage() . "\n";
    }

    echo "\n=== Test Complete ===\n";

} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}