<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING ASSIGNMENT ATTEMPT CREATION\n";
echo "======================================\n\n";

echo "🔍 TESTING createAssignmentAttempt METHOD:\n";
echo "==========================================\n";

$assignment = \App\Models\Assignment::find(2);
$userId = 2;
$percentageGrade = 85.0;

if (!$assignment) {
    echo "❌ Assignment not found!\n";
    exit;
}

// Reset assignment to active state
$assignment->update([
    'status' => \App\Models\Assignment::STATUS_ACTIVE,
    'completed_at' => null,
    'score' => null
]);

echo "Assignment reset to active state\n\n";

// Create a proper request context
$mockRequest = new \Illuminate\Http\Request();
$mockRequest->merge([
    'answers' => [
        '1' => 'A',
        '2' => 'B', 
        '3' => 'C',
        '4' => 'A'
    ],
    'time_taken' => 120,
    'assignment_id' => 2
]);

// Set the request in the container
app()->instance('request', $mockRequest);

echo "🧪 TEST 1: Direct createAssignmentAttempt call\n";
echo "----------------------------------------------\n";

try {
    $controller = app(\App\Http\Controllers\QuizController::class);
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('createAssignmentAttempt');
    $method->setAccessible(true);
    
    echo "Calling createAssignmentAttempt(assignment, $userId, $percentageGrade)...\n";
    $method->invoke($controller, $assignment, $userId, $percentageGrade);
    
    echo "✅ Method executed successfully\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
}

echo "🔍 CHECKING RESULTS:\n";
echo "====================\n";

// Check for AssignmentAttempt records
$attempts = \App\Models\AssignmentAttempt::where('assignment_id', 2)->get();
echo "Assignment attempts for Assignment ID 2: " . $attempts->count() . "\n";

if ($attempts->count() > 0) {
    foreach ($attempts as $attempt) {
        echo "✅ FOUND AssignmentAttempt:\n";
        echo "  ID: {$attempt->id}\n";
        echo "  Status: {$attempt->status}\n";
        echo "  Score: {$attempt->score_percentage}%\n";
        echo "  Total Questions: {$attempt->total_questions}\n";
        echo "  Correct Answers: {$attempt->correct_answers}\n";
        echo "  Time Taken: {$attempt->time_taken_seconds}s\n";
        echo "  Created: {$attempt->created_at}\n";
        echo "  ---\n";
    }
} else {
    echo "❌ No AssignmentAttempt records created!\n";
}

echo "\n🧪 TEST 2: Full handleAssignmentCompletion flow\n";
echo "===============================================\n";

// Reset assignment again
$assignment->update([
    'status' => \App\Models\Assignment::STATUS_ACTIVE,
    'completed_at' => null,
    'score' => null
]);

// Delete any existing attempts
\App\Models\AssignmentAttempt::where('assignment_id', 2)->delete();

echo "Assignment and attempts reset\n";

try {
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('handleAssignmentCompletion');
    $method->setAccessible(true);
    
    echo "Calling handleAssignmentCompletion($userId, 1, $percentageGrade, 2)...\n";
    $method->invoke($controller, $userId, 1, $percentageGrade, 2);
    
    echo "✅ Method executed successfully\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
}

echo "🔍 FINAL RESULTS:\n";
echo "=================\n";

$assignment = $assignment->fresh();
$attempts = \App\Models\AssignmentAttempt::where('assignment_id', 2)->get();

echo "ASSIGNMENT STATUS:\n";
echo "  Status: {$assignment->status}\n";
echo "  Completed At: " . ($assignment->completed_at ?: 'NULL') . "\n";
echo "  Score: " . ($assignment->score ?: 'NULL') . "\n";
echo "  Is Locked: " . ($assignment->isLocked() ? 'YES' : 'NO') . "\n\n";

echo "ASSIGNMENT ATTEMPTS:\n";
echo "  Count: " . $attempts->count() . "\n";

if ($attempts->count() > 0) {
    echo "✅ SUCCESS: AssignmentAttempt records created!\n";
} else {
    echo "❌ FAILURE: No AssignmentAttempt records created!\n";
}

echo "\n✅ ASSIGNMENT ATTEMPT CREATION TEST COMPLETE\n";