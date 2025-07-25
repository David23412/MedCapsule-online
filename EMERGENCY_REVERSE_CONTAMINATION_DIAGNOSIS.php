<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 EMERGENCY REVERSE CONTAMINATION DIAGNOSIS\n";
echo "============================================\n\n";

echo "🔍 PROBLEM IDENTIFIED:\n";
echo "======================\n";
echo "❌ Assignment completion is updating REGULAR TOPIC progress\n";
echo "✅ Assignment card progress is NOT updating (correct)\n";
echo "❌ Regular topic card progress IS updating (WRONG!)\n\n";

echo "🎯 ROOT CAUSE ANALYSIS:\n";
echo "=======================\n";
echo "There must be ANOTHER system updating regular topic progress\n";
echo "when assignments are completed. Possible sources:\n\n";

echo "1. ❓ QuizController grade() method updating topic progress\n";
echo "2. ❓ Assignment completion triggering topic progress update\n";
echo "3. ❓ Quiz grading service updating topic data\n";
echo "4. ❓ Topic progress calculation including assignment data\n";
echo "5. ❓ UserProgress model being updated during assignment completion\n\n";

echo "🔍 CHECKING CURRENT DATABASE STATE:\n";
echo "===================================\n";

// Check assignments
$assignments = \App\Models\Assignment::all();
echo "ASSIGNMENTS:\n";
foreach ($assignments as $assignment) {
    echo "ID: {$assignment->id} | Title: {$assignment->title} | Status: {$assignment->status}\n";
}

echo "\nQUIZ ATTEMPTS (Regular Topic Data):\n";
$quizAttempts = \App\Models\QuizAttempt::where('topic_id', 1)->where('user_id', 2)->orderBy('created_at', 'desc')->get();
foreach ($quizAttempts as $attempt) {
    echo "Score: " . ($attempt->score ?: 'N/A') . "/" . ($attempt->total_questions ?: 'N/A') . " | Date: {$attempt->created_at}\n";
}

echo "\nASSIGNMENT ATTEMPTS:\n";
$assignmentAttempts = \App\Models\AssignmentAttempt::with(['assignment'])->orderBy('created_at', 'desc')->get();
foreach ($assignmentAttempts as $attempt) {
    echo "Assignment: {$attempt->assignment->title} | Score: " . ($attempt->score_percentage ?: 'N/A') . "% | Date: {$attempt->created_at}\n";
}

echo "\n🚨 CONTAMINATION SOURCES TO INVESTIGATE:\n";
echo "========================================\n";

echo "1. CHECK: Does QuizController->grade() update topic progress?\n";
echo "2. CHECK: Does assignment completion create QuizAttempt records?\n";
echo "3. CHECK: Does topic progress calculation include assignment data?\n";
echo "4. CHECK: Are there any other progress update mechanisms?\n\n";

echo "🎯 INVESTIGATION TARGETS:\n";
echo "========================\n";
echo "- QuizController->grade() method\n";
echo "- Topic progress calculation logic\n";
echo "- UserProgress model updates\n";
echo "- Any services that update topic data\n\n";

echo "✅ DIAGNOSIS READY FOR SURGICAL INVESTIGATION\n";