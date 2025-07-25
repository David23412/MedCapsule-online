<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 REGULAR TOPIC PROGRESS UPDATE INVESTIGATION\n";
echo "==============================================\n\n";

echo "🔍 ISSUE REPORTED:\n";
echo "==================\n";
echo "1. Regular topic progress bar didn't update after completing a regular quiz\n";
echo "2. Missing assets: topic-cards.css, assignment-cards.css, topics-page.js\n\n";

echo "🎯 POTENTIAL CAUSES:\n";
echo "====================\n";
echo "1. ❓ Regular quiz completion not updating UserTopicProgress\n";
echo "2. ❓ Frontend JavaScript not receiving progress update events\n";
echo "3. ❓ Missing CSS/JS files breaking the update mechanism\n";
echo "4. ❓ GradingService not calling updateTopicProgress for regular quizzes\n";
echo "5. ❓ Event dispatching not working for regular quizzes\n\n";

echo "🔍 CHECKING CURRENT STATE:\n";
echo "==========================\n";

$userId = 2;
$topicId = 1;

// Check recent quiz attempts
echo "RECENT QUIZ ATTEMPTS:\n";
echo "--------------------\n";
$recentAttempts = \App\Models\QuizAttempt::where('topic_id', $topicId)
    ->where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recentAttempts as $attempt) {
    echo "ID: {$attempt->id} | Type: {$attempt->quiz_type} | Score: {$attempt->score}/{$attempt->total_questions} | Date: {$attempt->created_at}\n";
}

// Check UserTopicProgress
echo "\nUSER TOPIC PROGRESS:\n";
echo "-------------------\n";
$userProgress = \App\Models\UserTopicProgress::where('user_id', $userId)
    ->where('topic_id', $topicId)
    ->first();

if ($userProgress) {
    echo "Progress: {$userProgress->percentage_grade}%\n";
    echo "Last Updated: {$userProgress->updated_at}\n";
} else {
    echo "No UserTopicProgress record found\n";
}

// Check if there are any regular quiz attempts
echo "\nREGULAR QUIZ ATTEMPTS:\n";
echo "---------------------\n";
$regularAttempts = $recentAttempts->where('quiz_type', '!=', 'assignment');
echo "Count: " . $regularAttempts->count() . "\n";

if ($regularAttempts->count() > 0) {
    echo "Latest regular attempt:\n";
    $latest = $regularAttempts->first();
    echo "  Score: {$latest->score}/{$latest->total_questions}\n";
    echo "  Percentage: {$latest->percentage_grade}%\n";
    echo "  Date: {$latest->created_at}\n";
} else {
    echo "No regular quiz attempts found - only assignment attempts exist\n";
}

echo "\n🚨 CRITICAL ANALYSIS:\n";
echo "=====================\n";

if ($regularAttempts->count() == 0) {
    echo "❌ ISSUE IDENTIFIED: No regular quiz attempts found!\n";
    echo "   This means the user completed an ASSIGNMENT quiz, not a regular quiz.\n";
    echo "   Assignment completion should NOT update regular topic progress.\n";
    echo "   The system is working CORRECTLY by not updating regular progress.\n\n";
    
    echo "🎯 EXPECTED BEHAVIOR:\n";
    echo "   - Assignment completion: Updates assignment card only\n";
    echo "   - Regular quiz completion: Updates regular topic card only\n";
    echo "   - Current state: Assignment completed, regular topic unchanged (CORRECT)\n\n";
} else {
    echo "🔍 Regular quiz attempts found - investigating why progress didn't update...\n";
}

echo "✅ INVESTIGATION COMPLETE - READY FOR DETAILED ANALYSIS\n";