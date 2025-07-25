<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 DEBUG ASSIGNMENT COMPLETION FLOW\n";
echo "===================================\n\n";

echo "🎯 INVESTIGATING WHY ASSIGNMENTATTEMPT RECORDS ARE MISSING\n";
echo "=========================================================\n\n";

// Reset assignment for testing
echo "🔄 RESETTING ASSIGNMENT FOR TESTING:\n";
echo "====================================\n";

$assignment = \App\Models\Assignment::find(2);
if ($assignment) {
    echo "BEFORE RESET:\n";
    echo "  Status: {$assignment->status}\n";
    echo "  Completed At: " . ($assignment->completed_at ?: 'NULL') . "\n";
    echo "  Is Locked: " . ($assignment->isLocked() ? 'YES' : 'NO') . "\n\n";
    
    // Reset to active state for testing
    $assignment->update([
        'status' => \App\Models\Assignment::STATUS_ACTIVE,
        'completed_at' => null,
        'score' => null
    ]);
    
    echo "AFTER RESET:\n";
    echo "  Status: {$assignment->fresh()->status}\n";
    echo "  Completed At: " . ($assignment->fresh()->completed_at ?: 'NULL') . "\n";
    echo "  Is Locked: " . ($assignment->fresh()->isLocked() ? 'YES' : 'NO') . "\n";
    echo "  Can Be Completed: " . ($assignment->fresh()->canBeCompleted() ? 'YES' : 'NO') . "\n\n";
}

echo "🧪 TESTING ASSIGNMENT COMPLETION METHODS:\n";
echo "=========================================\n";

$userId = 2;
$topicId = 1;
$assignmentId = 2;
$percentageGrade = 85.0;

// Test the QuizController's handleAssignmentCompletion method
echo "TEST 1: QuizController->handleAssignmentCompletion()\n";
echo "---------------------------------------------------\n";

try {
    // Properly instantiate QuizController with dependencies
    $controller = app(\App\Http\Controllers\QuizController::class);
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('handleAssignmentCompletion');
    $method->setAccessible(true);
    
    echo "Calling handleAssignmentCompletion($userId, $topicId, $percentageGrade, $assignmentId)...\n";
    
    // Mock a request with assignment data for createAssignmentAttempt
    $mockRequest = new \Illuminate\Http\Request();
    $mockRequest->merge([
        'answers' => [
            '1' => 'A',
            '2' => 'B',
            '3' => 'C',
            '4' => 'A'
        ],
        'time_taken' => 120,
        'assignment_id' => $assignmentId
    ]);
    
    // Set the request in the container
    app()->instance('request', $mockRequest);
    
    $method->invoke($controller, $userId, $topicId, $percentageGrade, $assignmentId);
    
    echo "✅ Method executed successfully\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
}

echo "🔍 CHECKING RESULTS:\n";
echo "====================\n";

// Check assignment status after completion
$assignment = \App\Models\Assignment::find(2);
if ($assignment) {
    echo "ASSIGNMENT STATUS:\n";
    echo "  Status: {$assignment->status}\n";
    echo "  Completed At: " . ($assignment->completed_at ?: 'NULL') . "\n";
    echo "  Score: " . ($assignment->score ?: 'NULL') . "\n";
    echo "  Is Locked: " . ($assignment->isLocked() ? 'YES' : 'NO') . "\n\n";
}

// Check for AssignmentAttempt records
$attempts = \App\Models\AssignmentAttempt::where('assignment_id', 2)->get();
echo "ASSIGNMENT ATTEMPTS:\n";
echo "  Count: " . $attempts->count() . "\n";

if ($attempts->count() > 0) {
    foreach ($attempts as $attempt) {
        echo "  Attempt ID: {$attempt->id}\n";
        echo "  Status: {$attempt->status}\n";
        echo "  Score: {$attempt->score_percentage}%\n";
        echo "  Created: {$attempt->created_at}\n";
        echo "  ---\n";
    }
} else {
    echo "  ❌ No AssignmentAttempt records found!\n";
}

echo "\n🚨 ANALYSIS:\n";
echo "============\n";

if ($assignment && $assignment->status === \App\Models\Assignment::STATUS_COMPLETED && $attempts->count() > 0) {
    echo "✅ SUCCESS: Assignment completed and AssignmentAttempt record created\n";
} else if ($assignment && $assignment->status === \App\Models\Assignment::STATUS_COMPLETED && $attempts->count() == 0) {
    echo "❌ PARTIAL: Assignment completed but no AssignmentAttempt record\n";
    echo "   This indicates createAssignmentAttempt() was not called or failed\n";
} else {
    echo "❌ FAILURE: Assignment completion flow broken\n";
}

echo "\n✅ DEBUG ASSIGNMENT COMPLETION FLOW COMPLETE\n";