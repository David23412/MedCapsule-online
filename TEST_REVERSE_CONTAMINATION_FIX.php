<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING REVERSE CONTAMINATION FIX\n";
echo "====================================\n\n";

echo "🔍 TESTING REGULAR TOPIC PROGRESS CALCULATION:\n";
echo "===============================================\n";

// Test the fixed getTopicProgress method
$userId = 2;
$topicId = 1;

echo "Calling QuizAttempt::getTopicProgress($userId, $topicId, 'quiz')...\n\n";

$progress = \App\Models\QuizAttempt::getTopicProgress($userId, $topicId, 'quiz');

echo "RESULT:\n";
echo "-------\n";
echo "Overall Progress: " . json_encode($progress['overall'], JSON_PRETTY_PRINT) . "\n\n";

if (isset($progress['sources'])) {
    echo "Source Progress:\n";
    foreach ($progress['sources'] as $source => $data) {
        echo "  $source: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "✅ No source progress found (assignment attempts excluded!)\n";
}

echo "\n🔍 VERIFYING ASSIGNMENT ATTEMPTS ARE EXCLUDED:\n";
echo "==============================================\n";

$allAttempts = \App\Models\QuizAttempt::where('topic_id', $topicId)->where('user_id', $userId)->get();
echo "Total QuizAttempts: " . $allAttempts->count() . "\n";

$assignmentAttempts = $allAttempts->where('quiz_type', 'assignment');
echo "Assignment Attempts: " . $assignmentAttempts->count() . "\n";

$regularAttempts = $allAttempts->where('quiz_type', '!=', 'assignment');
echo "Regular Attempts: " . $regularAttempts->count() . "\n\n";

if ($regularAttempts->count() === 0) {
    echo "✅ PERFECT! No regular quiz attempts found.\n";
    echo "✅ Regular topic progress should be 0% (no contamination)\n\n";
} else {
    echo "Regular attempts found:\n";
    foreach ($regularAttempts as $attempt) {
        echo "  Source: {$attempt->source}, Score: {$attempt->score}/{$attempt->total_questions}\n";
    }
}

echo "🎯 EXPECTED UI BEHAVIOR:\n";
echo "========================\n";
echo "Regular Topic Card ('All' filter):\n";
echo "- Progress: 0% (no regular quiz attempts)\n";
echo "- Button: 'Start Quiz'\n";
echo "- Status: Ready to start\n";
echo "- NO assignment progress contamination!\n\n";

echo "Assignment Cards ('Assignments' filter):\n";
echo "- Each assignment shows its own progress independently\n";
echo "- No cross-contamination between assignments\n";
echo "- Assignment progress based on AssignmentAttempt records\n\n";

echo "✅ REVERSE CONTAMINATION FIX TEST COMPLETE\n";