<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING COMPLETE QUIZ FLOW\n";
echo "=============================\n\n";

echo "🎯 TESTING SCENARIO 1: REGULAR QUIZ (NO ASSIGNMENT_ID)\n";
echo "=======================================================\n";

$user = \App\Models\User::find(2);
$topic = \App\Models\Topic::find(1);
$course = \App\Models\Course::find(1);

// Test the GradingService with no assignment_id
$gradingService = new \App\Services\GradingService(
    app(\App\Services\CacheService::class),
    app(\App\Services\UserStatsService::class),
    app(\App\Services\NotificationService::class),
    app(\App\Services\WrittenAnswerEvaluationService::class)
);

// Use reflection to test isAssignmentQuiz
$reflection = new ReflectionClass($gradingService);
$method = $reflection->getMethod('isAssignmentQuiz');
$method->setAccessible(true);

echo "TEST: isAssignmentQuiz(user, topic, course, null)\n";
$isAssignment = $method->invoke($gradingService, $user, $topic, $course, null);
echo "Result: " . ($isAssignment ? "ASSIGNMENT" : "REGULAR") . "\n";
echo "Expected: REGULAR\n";
echo "Status: " . ($isAssignment ? "❌ FAILED" : "✅ PASSED") . "\n\n";

echo "🎯 TESTING SCENARIO 2: ASSIGNMENT QUIZ (WITH ASSIGNMENT_ID=2)\n";
echo "=============================================================\n";

echo "TEST: isAssignmentQuiz(user, topic, course, 2)\n";
$isAssignment2 = $method->invoke($gradingService, $user, $topic, $course, 2);
echo "Result: " . ($isAssignment2 ? "ASSIGNMENT" : "REGULAR") . "\n";
echo "Expected: ASSIGNMENT\n";
echo "Status: " . ($isAssignment2 ? "✅ PASSED" : "❌ FAILED") . "\n\n";

echo "🔍 CHECKING ASSIGNMENT STATUS:\n";
echo "==============================\n";
$assignment = \App\Models\Assignment::find(2);
if ($assignment) {
    echo "Assignment ID: {$assignment->id}\n";
    echo "Title: {$assignment->title}\n";
    echo "Status: {$assignment->status}\n";
    echo "Student ID: {$assignment->student_id}\n";
    echo "Topic ID: {$assignment->topic_id}\n";
    echo "Course ID: {$assignment->course_id}\n";
    echo "Completed At: " . ($assignment->completed_at ?: 'Not completed') . "\n\n";
    
    // Check if assignment can be completed
    echo "Can be completed: " . ($assignment->canBeCompleted() ? "YES" : "NO") . "\n";
    echo "Is overdue: " . ($assignment->isOverdue() ? "YES" : "NO") . "\n";
    echo "Is locked: " . ($assignment->isLocked() ? "YES" : "NO") . "\n\n";
} else {
    echo "❌ Assignment ID 2 not found!\n\n";
}

echo "🔍 CHECKING ASSIGNMENT ATTEMPTS:\n";
echo "================================\n";
$assignmentAttempts = \App\Models\AssignmentAttempt::where('assignment_id', 2)->get();
echo "Assignment attempts for Assignment ID 2: " . $assignmentAttempts->count() . "\n";

if ($assignmentAttempts->count() > 0) {
    foreach ($assignmentAttempts as $attempt) {
        echo "  Attempt ID: {$attempt->id}\n";
        echo "  Status: {$attempt->status}\n";
        echo "  Score: {$attempt->score_percentage}%\n";
        echo "  Completed: {$attempt->completed_at}\n";
        echo "  ---\n";
    }
} else {
    echo "  No assignment attempts found\n";
}

echo "\n🚨 CRITICAL ISSUES TO FIX:\n";
echo "==========================\n";

if (!$isAssignment && $isAssignment2) {
    echo "✅ Quiz type detection working correctly\n";
} else {
    echo "❌ Quiz type detection broken\n";
}

if ($assignment && $assignment->completed_at && $assignmentAttempts->count() == 0) {
    echo "❌ CRITICAL: Assignment marked as completed but no AssignmentAttempt records!\n";
    echo "   This means createAssignmentAttempt is not being called properly\n";
} else if ($assignmentAttempts->count() > 0) {
    echo "✅ AssignmentAttempt records found\n";
}

if ($assignment && $assignment->completed_at && !$assignment->isLocked()) {
    echo "❌ CRITICAL: Assignment completed but not locked!\n";
    echo "   Students can retake completed assignments\n";
} else if ($assignment && $assignment->isLocked()) {
    echo "✅ Assignment properly locked after completion\n";
}

echo "\n🎯 REQUIRED ACTIONS:\n";
echo "===================\n";
echo "1. Verify quiz type detection works in actual requests\n";
echo "2. Ensure AssignmentAttempt records are created\n";
echo "3. Implement proper assignment locking\n";
echo "4. Fix regular topic progress updates\n\n";

echo "✅ COMPLETE QUIZ FLOW TEST FINISHED\n";