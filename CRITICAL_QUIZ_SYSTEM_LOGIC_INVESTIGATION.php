<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 CRITICAL QUIZ SYSTEM LOGIC INVESTIGATION\n";
echo "===========================================\n\n";

echo "🔍 ISSUES IDENTIFIED:\n";
echo "=====================\n";
echo "1. Quiz type detection not working properly\n";
echo "2. Regular topic progress not updating\n";
echo "3. Assignment locking logic missing\n";
echo "4. Same topic/questions used for both assignment and regular\n\n";

echo "🎯 HOW SYSTEM SHOULD WORK:\n";
echo "==========================\n";
echo "REGULAR QUIZ:\n";
echo "- URL: /quiz/1/1 (no assignment_id parameter)\n";
echo "- Creates: QuizAttempt with quiz_type='normal'\n";
echo "- Updates: Regular topic progress\n";
echo "- Repeatable: Yes, unlimited attempts\n\n";

echo "ASSIGNMENT QUIZ:\n";
echo "- URL: /quiz/1/1?assignment_id=2\n";
echo "- Creates: QuizAttempt with quiz_type='assignment' + AssignmentAttempt\n";
echo "- Updates: Assignment status to 'completed'\n";
echo "- Repeatable: NO - should be locked after first attempt\n\n";

echo "🔍 CHECKING CURRENT SYSTEM STATE:\n";
echo "=================================\n";

// Check recent quiz attempts
$recentAttempts = \App\Models\QuizAttempt::where('topic_id', 1)
    ->where('user_id', 2)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "RECENT QUIZ ATTEMPTS:\n";
echo "--------------------\n";
foreach ($recentAttempts as $attempt) {
    echo "ID: {$attempt->id} | Type: {$attempt->quiz_type} | Score: {$attempt->score}/{$attempt->total_questions} | Source: {$attempt->source} | Date: {$attempt->created_at}\n";
}

// Check assignment attempts
echo "\nASSIGNMENT ATTEMPTS:\n";
echo "-------------------\n";
$assignmentAttempts = \App\Models\AssignmentAttempt::with('assignment')->get();
foreach ($assignmentAttempts as $attempt) {
    echo "Assignment: {$attempt->assignment->title} | Status: {$attempt->status} | Score: {$attempt->score_percentage}% | Date: {$attempt->created_at}\n";
}

// Check assignment statuses
echo "\nASSIGNMENT STATUSES:\n";
echo "-------------------\n";
$assignments = \App\Models\Assignment::all();
foreach ($assignments as $assignment) {
    echo "ID: {$assignment->id} | Title: {$assignment->title} | Status: {$assignment->status} | Completed: " . ($assignment->completed_at ?: 'No') . "\n";
}

echo "\n🚨 CRITICAL ANALYSIS:\n";
echo "=====================\n";

$regularAttempts = $recentAttempts->where('quiz_type', '!=', 'assignment');
$assignmentQuizAttempts = $recentAttempts->where('quiz_type', 'assignment');

echo "Regular Quiz Attempts: " . $regularAttempts->count() . "\n";
echo "Assignment Quiz Attempts: " . $assignmentQuizAttempts->count() . "\n";

if ($regularAttempts->count() == 0) {
    echo "❌ CRITICAL: No regular quiz attempts found!\n";
    echo "   This means regular quizzes are being marked as assignments!\n";
} else {
    echo "✅ Regular quiz attempts found\n";
}

if ($assignmentAttempts->count() == 0) {
    echo "❌ CRITICAL: No AssignmentAttempt records found!\n";
    echo "   Assignment completion not being recorded properly!\n";
} else {
    echo "✅ AssignmentAttempt records found\n";
}

echo "\n🎯 REQUIRED FIXES:\n";
echo "==================\n";
echo "1. Fix quiz type detection based on assignment_id parameter\n";
echo "2. Ensure regular quizzes create 'normal' type QuizAttempts\n";
echo "3. Ensure assignment quizzes create AssignmentAttempt records\n";
echo "4. Implement assignment locking after first attempt\n";
echo "5. Fix regular topic progress updates\n\n";

echo "✅ INVESTIGATION COMPLETE - READY FOR FIXES\n";