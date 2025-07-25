<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🎯 FINAL VERIFICATION: REGULAR TOPIC PROGRESS CALCULATION\n";
echo "=========================================================\n\n";

// Get quiz attempts for regular topic
$quizAttempts = \App\Models\QuizAttempt::where('topic_id', 1)->where('user_id', 2)->get();

echo "📊 REGULAR TOPIC QUIZ ATTEMPTS (User ID: 2, Topic ID: 1):\n";
echo "=========================================================\n";

$totalScore = 0;
$totalQuestions = 0;
$attemptCount = 0;

foreach ($quizAttempts as $attempt) {
    $attemptCount++;
    echo "Attempt #{$attemptCount}:\n";
    echo "  Score: " . ($attempt->score ? $attempt->score : 'N/A') . "\n";
    echo "  Total Questions: " . ($attempt->total_questions ? $attempt->total_questions : 'N/A') . "\n";
    echo "  Date: {$attempt->created_at}\n";
    echo "  ---\n";
    
    if ($attempt->score && $attempt->total_questions) {
        $totalScore += $attempt->score;
        $totalQuestions += $attempt->total_questions;
    }
}

echo "\n📈 REGULAR TOPIC PROGRESS CALCULATION:\n";
echo "======================================\n";

if ($totalQuestions > 0) {
    $regularTopicProgress = ($totalScore / $totalQuestions) * 100;
    echo "Total Correct: {$totalScore}\n";
    echo "Total Questions: {$totalQuestions}\n";
    echo "Calculated Progress: " . round($regularTopicProgress, 1) . "%\n";
} else {
    $regularTopicProgress = 0;
    echo "No valid quiz attempts found\n";
    echo "Calculated Progress: 0%\n";
}

echo "\n🎯 EXPECTED UI BEHAVIOR:\n";
echo "========================\n";

echo "BEFORE COMPLETING ASSIGNMENT #2:\n";
echo "---------------------------------\n";
echo "🔍 'All' Filter (Regular Topic Card):\n";
echo "  📚 'Topic 1 for Internal Medicine'\n";
echo "      → Progress: " . round($regularTopicProgress, 1) . "% (based on QuizAttempt data)\n";
echo "      → This is the BASELINE - should NOT change after assignment completion\n\n";

echo "🔍 'Assignments' Filter (Assignment Cards):\n";
echo "  📋 Assignment #1: Status=OVERDUE, Progress=0%\n";
echo "  📋 Assignment #2: Status=ACTIVE, Progress=0%\n";
echo "  📋 Assignment #3: Status=OVERDUE, Progress=0%\n\n";

echo "AFTER COMPLETING ASSIGNMENT #2:\n";
echo "===============================\n";
echo "🔍 'All' Filter (Regular Topic Card) - CRITICAL TEST:\n";
echo "  📚 'Topic 1 for Internal Medicine'\n";
echo "      → Progress: " . round($regularTopicProgress, 1) . "% (MUST REMAIN THE SAME!) ✅\n";
echo "      → 🚨 IF THIS CHANGES = CONTAMINATION BUG!\n\n";

echo "🔍 'Assignments' Filter (Assignment Cards):\n";
echo "  📋 Assignment #1: Status=OVERDUE, Progress=0% (UNCHANGED)\n";
echo "  📋 Assignment #2: Status=COMPLETED, Progress=100% (ONLY THIS CHANGES)\n";
echo "  📋 Assignment #3: Status=OVERDUE, Progress=0% (UNCHANGED)\n\n";

echo "🚨 CRITICAL SUCCESS CRITERIA:\n";
echo "=============================\n";
echo "✅ Regular topic progress BEFORE: " . round($regularTopicProgress, 1) . "%\n";
echo "✅ Regular topic progress AFTER:  " . round($regularTopicProgress, 1) . "% (MUST BE IDENTICAL!)\n";
echo "✅ Only Assignment #2 shows 100% progress\n";
echo "✅ Database: Only Assignment #2 status = 'completed'\n\n";

echo "🎯 TESTING INSTRUCTIONS:\n";
echo "========================\n";
echo "1. Note the regular topic progress: " . round($regularTopicProgress, 1) . "%\n";
echo "2. Complete Assignment #2\n";
echo "3. Return to topics page\n";
echo "4. Verify regular topic STILL shows " . round($regularTopicProgress, 1) . "%\n";
echo "5. If it shows different progress = CONTAMINATION BUG!\n\n";

echo "✅ VERIFICATION TEST READY\n";
echo "==========================\n";
echo "The regular topic should maintain its current progress of " . round($regularTopicProgress, 1) . "%\n";
echo "regardless of any assignment completion activities.\n";