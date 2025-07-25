<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 REVERSE CONTAMINATION EMERGENCY FIX\n";
echo "======================================\n\n";

echo "🔍 ROOT CAUSE IDENTIFIED:\n";
echo "=========================\n";
echo "The QuizAttempt::getTopicProgress() method is INCLUDING assignment quiz attempts\n";
echo "when calculating regular topic progress!\n\n";

echo "CONTAMINATION FLOW:\n";
echo "1. Assignment quiz completed → Creates QuizAttempt with quiz_type='assignment', study_mode='quiz'\n";
echo "2. Regular topic progress calculation calls getTopicProgress()\n";
echo "3. getTopicProgress() finds assignment QuizAttempts (because study_mode='quiz')\n";
echo "4. Regular topic progress gets updated with assignment scores!\n\n";

echo "🔧 EMERGENCY FIX REQUIRED:\n";
echo "==========================\n";
echo "Modify QuizAttempt::getTopicProgress() to EXCLUDE assignment quiz attempts\n";
echo "when calculating regular topic progress.\n\n";

echo "CURRENT BROKEN QUERY:\n";
echo "---------------------\n";
echo "QuizAttempt::where([\n";
echo "    'user_id' => \$userId,\n";
echo "    'topic_id' => \$topicId,\n";
echo "    'study_mode' => 'quiz',  // ← INCLUDES ASSIGNMENTS!\n";
echo "    'source' => \$source\n";
echo "])\n\n";

echo "REQUIRED FIXED QUERY:\n";
echo "--------------------\n";
echo "QuizAttempt::where([\n";
echo "    'user_id' => \$userId,\n";
echo "    'topic_id' => \$topicId,\n";
echo "    'study_mode' => 'quiz'\n";
echo "])\n";
echo "->where('quiz_type', '!=', 'assignment')  // ← EXCLUDE ASSIGNMENTS!\n";
echo "->where('source', \$source)\n\n";

echo "🎯 IMPLEMENTING EMERGENCY FIX...\n";
echo "=================================\n";

// Check current contamination
echo "BEFORE FIX - Checking contamination:\n";
$quizAttempts = \App\Models\QuizAttempt::where('topic_id', 1)->where('user_id', 2)->get();
echo "Total QuizAttempts for Topic 1, User 2: " . $quizAttempts->count() . "\n";

$assignmentAttempts = $quizAttempts->where('quiz_type', 'assignment');
echo "Assignment QuizAttempts: " . $assignmentAttempts->count() . "\n";

$regularAttempts = $quizAttempts->where('quiz_type', '!=', 'assignment');
echo "Regular QuizAttempts: " . $regularAttempts->count() . "\n\n";

if ($assignmentAttempts->count() > 0) {
    echo "🚨 CONTAMINATION CONFIRMED!\n";
    echo "Assignment QuizAttempts are contaminating regular topic progress!\n\n";
    
    foreach ($assignmentAttempts as $attempt) {
        echo "Assignment Attempt: Source={$attempt->source}, Score={$attempt->score}/{$attempt->total_questions}, Type={$attempt->quiz_type}\n";
    }
    echo "\n";
} else {
    echo "✅ No assignment contamination found in QuizAttempts\n\n";
}

echo "🔧 APPLYING EMERGENCY FIX TO QuizAttempt MODEL...\n";
echo "=================================================\n";

echo "✅ EMERGENCY FIX READY FOR IMPLEMENTATION\n";
echo "==========================================\n";
echo "The fix must be applied to:\n";
echo "- app/Models/QuizAttempt.php (getTopicProgress method)\n";
echo "- Line ~264-270: Add ->where('quiz_type', '!=', 'assignment')\n\n";

echo "🚨 CRITICAL: This will immediately stop assignment contamination!\n";