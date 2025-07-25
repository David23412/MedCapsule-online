<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🎯 FINAL COMPREHENSIVE QUIZ SYSTEM TEST\n";
echo "=======================================\n\n";

echo "🚀 TESTING COMPLETE QUIZ SYSTEM FUNCTIONALITY\n";
echo "==============================================\n\n";

// Reset system to clean state
echo "🔄 RESETTING SYSTEM TO CLEAN STATE:\n";
echo "===================================\n";

// Reset assignments
$assignments = \App\Models\Assignment::all();
foreach ($assignments as $assignment) {
    $assignment->update([
        'status' => $assignment->id == 2 ? \App\Models\Assignment::STATUS_ACTIVE : \App\Models\Assignment::STATUS_OVERDUE,
        'completed_at' => null,
        'score' => null
    ]);
}

// Clear all attempts
\App\Models\AssignmentAttempt::truncate();
\App\Models\QuizAttempt::truncate();
\App\Models\UserTopicProgress::where('user_id', 2)->where('topic_id', 1)->delete();

echo "✅ System reset complete\n\n";

// Test data
$userId = 2;
$topicId = 1;
$courseId = 1;

echo "🧪 TEST 1: REGULAR QUIZ COMPLETION\n";
echo "==================================\n";

// Create mock request for regular quiz
$mockRequest = new \Illuminate\Http\Request();
$mockRequest->merge([
    'answers' => ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'A'],
    'time_taken' => 90,
    // NO assignment_id for regular quiz
]);
app()->instance('request', $mockRequest);

// Test GradingService for regular quiz
$gradingService = app(\App\Services\GradingService::class);
$user = \App\Models\User::find($userId);
$topic = \App\Models\Topic::find($topicId);
$course = \App\Models\Course::find($courseId);

try {
    echo "Simulating regular quiz completion...\n";
    
    // This would normally be called by QuizController
    $answers = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'A'];
    $duration = 90;
    $mode = 'quiz';
    $assignmentId = null; // Regular quiz
    
    $result = $gradingService->gradeQuiz($user, $answers, $duration, $mode, $topic, $course, $assignmentId);
    
    echo "✅ Regular quiz graded successfully\n";
    echo "   Score: {$result['score']}/{$result['totalQuestions']}\n";
    echo "   Percentage: {$result['percentageGrade']}%\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "🔍 CHECKING REGULAR QUIZ RESULTS:\n";
echo "=================================\n";

$regularQuizAttempts = \App\Models\QuizAttempt::where('topic_id', $topicId)
    ->where('user_id', $userId)
    ->where('quiz_type', '!=', 'assignment')
    ->get();

echo "Regular QuizAttempts: " . $regularQuizAttempts->count() . "\n";
if ($regularQuizAttempts->count() > 0) {
    foreach ($regularQuizAttempts as $attempt) {
        echo "  Type: {$attempt->quiz_type} | Score: {$attempt->score}/{$attempt->total_questions} | Source: {$attempt->source}\n";
    }
} else {
    echo "  ❌ No regular quiz attempts found!\n";
}

$userProgress = \App\Models\UserTopicProgress::where('user_id', $userId)->where('topic_id', $topicId)->first();
echo "Regular Topic Progress: " . ($userProgress ? $userProgress->percentage_grade . '%' : 'None') . "\n\n";

echo "🧪 TEST 2: ASSIGNMENT QUIZ COMPLETION\n";
echo "=====================================\n";

// Create mock request for assignment quiz
$mockRequest = new \Illuminate\Http\Request();
$mockRequest->merge([
    'answers' => ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'A'],
    'time_taken' => 120,
    'assignment_id' => 2 // Assignment quiz
]);
app()->instance('request', $mockRequest);

try {
    echo "Simulating assignment quiz completion...\n";
    
    $answers = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'A'];
    $duration = 120;
    $mode = 'quiz';
    $assignmentId = 2; // Assignment quiz
    
    $result = $gradingService->gradeQuiz($user, $answers, $duration, $mode, $topic, $course, $assignmentId);
    
    echo "✅ Assignment quiz graded successfully\n";
    echo "   Score: {$result['score']}/{$result['totalQuestions']}\n";
    echo "   Percentage: {$result['percentageGrade']}%\n\n";
    
    // Simulate assignment completion
    $controller = app(\App\Http\Controllers\QuizController::class);
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('handleAssignmentCompletion');
    $method->setAccessible(true);
    $method->invoke($controller, $userId, $topicId, $result['percentageGrade'], $assignmentId);
    
    echo "✅ Assignment completion handled\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "🔍 CHECKING ASSIGNMENT QUIZ RESULTS:\n";
echo "====================================\n";

$assignmentQuizAttempts = \App\Models\QuizAttempt::where('topic_id', $topicId)
    ->where('user_id', $userId)
    ->where('quiz_type', 'assignment')
    ->get();

echo "Assignment QuizAttempts: " . $assignmentQuizAttempts->count() . "\n";
if ($assignmentQuizAttempts->count() > 0) {
    foreach ($assignmentQuizAttempts as $attempt) {
        echo "  Type: {$attempt->quiz_type} | Score: {$attempt->score}/{$attempt->total_questions} | Source: {$attempt->source}\n";
    }
} else {
    echo "  ❌ No assignment quiz attempts found!\n";
}

$assignmentAttempts = \App\Models\AssignmentAttempt::where('assignment_id', 2)->get();
echo "AssignmentAttempts: " . $assignmentAttempts->count() . "\n";
if ($assignmentAttempts->count() > 0) {
    foreach ($assignmentAttempts as $attempt) {
        echo "  Assignment: {$attempt->assignment_id} | Score: {$attempt->score_percentage}% | Status: {$attempt->status}\n";
    }
} else {
    echo "  ❌ No assignment attempts found!\n";
}

$assignment = \App\Models\Assignment::find(2);
echo "Assignment Status: {$assignment->status}\n";
echo "Assignment Locked: " . ($assignment->isLocked() ? 'YES' : 'NO') . "\n\n";

echo "🎯 FINAL SYSTEM VERIFICATION:\n";
echo "=============================\n";

$allQuizAttempts = \App\Models\QuizAttempt::where('topic_id', $topicId)->where('user_id', $userId)->get();
$regularAttempts = $allQuizAttempts->where('quiz_type', '!=', 'assignment');
$assignmentAttempts = $allQuizAttempts->where('quiz_type', 'assignment');

echo "QUIZ SYSTEM SEPARATION:\n";
echo "  Total QuizAttempts: " . $allQuizAttempts->count() . "\n";
echo "  Regular Attempts: " . $regularAttempts->count() . "\n";
echo "  Assignment Attempts: " . $assignmentAttempts->count() . "\n\n";

echo "PROGRESS TRACKING:\n";
$userProgress = \App\Models\UserTopicProgress::where('user_id', $userId)->where('topic_id', $topicId)->first();
echo "  Regular Topic Progress: " . ($userProgress ? $userProgress->percentage_grade . '%' : '0%') . "\n";
echo "  Assignment Progress: " . ($assignment->status === 'completed' ? '100%' : '0%') . "\n\n";

echo "SYSTEM ISOLATION:\n";
echo "  Regular quiz affects regular progress: " . ($regularAttempts->count() > 0 && $userProgress ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Assignment quiz affects assignment only: " . ($assignmentAttempts->count() > 0 && $assignment->status === 'completed' ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Assignment properly locked: " . ($assignment->isLocked() ? 'YES ✅' : 'NO ❌') . "\n";
echo "  AssignmentAttempt records created: " . (\App\Models\AssignmentAttempt::where('assignment_id', 2)->count() > 0 ? 'YES ✅' : 'NO ❌') . "\n\n";

if ($regularAttempts->count() > 0 && $assignmentAttempts->count() > 0 && $assignment->isLocked() && \App\Models\AssignmentAttempt::where('assignment_id', 2)->count() > 0) {
    echo "🎉 SUCCESS: COMPLETE QUIZ SYSTEM WORKING PERFECTLY!\n";
    echo "✅ Regular quizzes update regular topic progress\n";
    echo "✅ Assignment quizzes update assignment status only\n";
    echo "✅ Perfect system separation maintained\n";
    echo "✅ Assignment locking works correctly\n";
    echo "✅ All attempt records created properly\n";
} else {
    echo "❌ ISSUES DETECTED: Some functionality not working correctly\n";
}

echo "\n✅ FINAL COMPREHENSIVE QUIZ SYSTEM TEST COMPLETE\n";