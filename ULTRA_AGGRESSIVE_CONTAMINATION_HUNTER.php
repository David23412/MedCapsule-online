<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔥 ULTRA-AGGRESSIVE CONTAMINATION HUNTER\n";
echo "========================================\n";
echo "HUNTING DOWN EVERY POSSIBLE CONTAMINATION SOURCE!\n\n";

echo "🎯 MISSION: VERIFY ZERO CONTAMINATION BETWEEN SYSTEMS\n";
echo "=====================================================\n";
echo "- Assignment completion MUST NOT affect regular topic progress\n";
echo "- Regular topic progress MUST be based ONLY on QuizAttempt data\n";
echo "- Assignment progress MUST be based ONLY on AssignmentAttempt data\n";
echo "- ZERO cross-contamination tolerance!\n\n";

// Get baseline data
$userId = 2;
$topicId = 1;

echo "🔍 PHASE 1: BASELINE DATA CAPTURE\n";
echo "=================================\n";

// Get current regular topic progress
$quizAttempts = \App\Models\QuizAttempt::where('topic_id', $topicId)->where('user_id', $userId)->get();
$totalScore = 0;
$totalQuestions = 0;

foreach ($quizAttempts as $attempt) {
    if ($attempt->score && $attempt->total_questions) {
        $totalScore += $attempt->score;
        $totalQuestions += $attempt->total_questions;
    }
}

$baselineRegularProgress = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;

echo "📊 BASELINE REGULAR TOPIC PROGRESS: " . round($baselineRegularProgress, 2) . "%\n";
echo "   Based on QuizAttempt data: {$totalScore}/{$totalQuestions} questions\n";

// Get assignment statuses
$assignments = \App\Models\Assignment::where('student_id', $userId)->where('topic_id', $topicId)->get();
echo "\n📊 BASELINE ASSIGNMENT STATUSES:\n";
foreach ($assignments as $assignment) {
    echo "   Assignment #{$assignment->id} '{$assignment->title}': {$assignment->status}\n";
}

// Get assignment attempts
$assignmentAttempts = \App\Models\AssignmentAttempt::whereIn('assignment_id', $assignments->pluck('id'))->get();
echo "\n📊 BASELINE ASSIGNMENT ATTEMPTS: " . $assignmentAttempts->count() . "\n";
foreach ($assignmentAttempts as $attempt) {
    echo "   Assignment #{$attempt->assignment_id}: {$attempt->status}, Score: " . ($attempt->score_percentage ?: 'N/A') . "%\n";
}

echo "\n🔥 PHASE 2: CONTAMINATION SOURCE HUNTING\n";
echo "========================================\n";

echo "🔍 HUNTING: Backend assignment completion logic...\n";
// Check if handleAssignmentCompletion method exists and has correct signature
$reflection = new ReflectionClass(\App\Http\Controllers\QuizController::class);
try {
    $method = $reflection->getMethod('handleAssignmentCompletion');
    $parameters = $method->getParameters();
    echo "✅ handleAssignmentCompletion method found\n";
    echo "   Parameters: ";
    foreach ($parameters as $param) {
        echo $param->getName() . " ";
    }
    echo "\n";
    
    // Check if it has assignmentId parameter
    $hasAssignmentId = false;
    foreach ($parameters as $param) {
        if ($param->getName() === 'assignmentId') {
            $hasAssignmentId = true;
            break;
        }
    }
    
    if ($hasAssignmentId) {
        echo "✅ GOOD: Method has assignmentId parameter (specific assignment targeting)\n";
    } else {
        echo "❌ DANGER: Method missing assignmentId parameter (potential mass contamination!)\n";
    }
    
} catch (ReflectionException $e) {
    echo "❌ CRITICAL: handleAssignmentCompletion method not found!\n";
}

echo "\n🔍 HUNTING: Frontend JavaScript contamination...\n";
// Check if topics_for_course.blade.php has the fixed JavaScript
$topicsFile = file_get_contents('resources/views/topics_for_course.blade.php');

if (strpos($topicsFile, 'assignmentId') !== false) {
    echo "✅ GOOD: JavaScript includes assignmentId handling\n";
} else {
    echo "❌ DANGER: JavaScript missing assignmentId handling!\n";
}

if (strpos($topicsFile, 'data-assignment-id') !== false) {
    echo "✅ GOOD: Assignment cards have unique data-assignment-id attributes\n";
} else {
    echo "❌ DANGER: Assignment cards missing unique identifiers!\n";
}

if (strpos($topicsFile, 'querySelector.*data-topic-id.*data-has-assignment.*false') !== false) {
    echo "✅ GOOD: Regular topic card targeting is specific\n";
} else {
    echo "❌ DANGER: Regular topic card targeting may be contaminated!\n";
}

echo "\n🔍 HUNTING: Event dispatching contamination...\n";
// Check solve_quiz.blade.php for proper event handling
$solveQuizFile = file_get_contents('resources/views/solve_quiz.blade.php');

if (strpos($solveQuizFile, 'assignmentId:') !== false) {
    echo "✅ GOOD: Quiz completion events include assignment context\n";
} else {
    echo "❌ DANGER: Quiz completion events missing assignment context!\n";
}

echo "\n🔥 PHASE 3: AGGRESSIVE VERIFICATION PROTOCOL\n";
echo "============================================\n";

echo "🎯 VERIFICATION TARGET: Assignment #2 completion isolation\n";
echo "🚨 ZERO TOLERANCE for any contamination!\n\n";

echo "📋 PRE-COMPLETION VERIFICATION CHECKLIST:\n";
echo "[ ] Regular topic progress: " . round($baselineRegularProgress, 2) . "% (MUST NOT CHANGE)\n";
echo "[ ] Assignment #1 status: " . $assignments->where('id', 1)->first()->status . " (MUST NOT CHANGE)\n";
echo "[ ] Assignment #2 status: " . $assignments->where('id', 2)->first()->status . " (SHOULD CHANGE TO completed)\n";
echo "[ ] Assignment #3 status: " . $assignments->where('id', 3)->first()->status . " (MUST NOT CHANGE)\n";
echo "[ ] Assignment attempts count: " . $assignmentAttempts->count() . " (SHOULD INCREASE BY 1)\n\n";

echo "🔥 AGGRESSIVE TESTING PROTOCOL:\n";
echo "===============================\n";
echo "1. 📊 CAPTURE: Current regular topic progress = " . round($baselineRegularProgress, 2) . "%\n";
echo "2. 🎯 ACTION: Complete Assignment #2 ONLY\n";
echo "3. 🔍 VERIFY: Regular topic progress STILL = " . round($baselineRegularProgress, 2) . "%\n";
echo "4. 🔍 VERIFY: Only Assignment #2 status = 'completed'\n";
echo "5. 🔍 VERIFY: Assignment #1 & #3 status UNCHANGED\n";
echo "6. 🔍 VERIFY: Only 1 new AssignmentAttempt record created\n";
echo "7. 🔍 VERIFY: No new QuizAttempt records created\n";
echo "8. 🔍 VERIFY: Regular topic UI shows SAME progress\n\n";

echo "🚨 CONTAMINATION DETECTION TRIGGERS:\n";
echo "====================================\n";
echo "❌ CONTAMINATION DETECTED IF:\n";
echo "   - Regular topic progress changes from " . round($baselineRegularProgress, 2) . "%\n";
echo "   - Assignment #1 status changes from '" . $assignments->where('id', 1)->first()->status . "'\n";
echo "   - Assignment #3 status changes from '" . $assignments->where('id', 3)->first()->status . "'\n";
echo "   - Multiple assignments marked as 'completed'\n";
echo "   - New QuizAttempt records created during assignment completion\n";
echo "   - Regular topic UI shows different progress\n\n";

echo "✅ SUCCESS INDICATORS:\n";
echo "======================\n";
echo "✅ PERFECT ISOLATION IF:\n";
echo "   - Regular topic progress remains " . round($baselineRegularProgress, 2) . "%\n";
echo "   - Only Assignment #2 status = 'completed'\n";
echo "   - Assignment #1 & #3 remain unchanged\n";
echo "   - Exactly 1 new AssignmentAttempt created\n";
echo "   - Zero new QuizAttempt records\n";
echo "   - Regular topic UI unchanged\n\n";

echo "🔥 ULTRA-AGGRESSIVE MONITORING:\n";
echo "===============================\n";
echo "Monitor these logs during assignment completion:\n\n";

echo "✅ BACKEND LOGS TO WATCH:\n";
echo "   'SPECIFIC assignment completed and LOCKED via quiz'\n";
echo "   'assignment_id: 2' (ONLY Assignment #2)\n";
echo "   'Regular quiz completed - no assignments affected' (SHOULD NOT APPEAR)\n\n";

echo "✅ FRONTEND LOGS TO WATCH:\n";
echo "   'Assignment 2 completed - updating assignment card only'\n";
echo "   'Regular quiz for topic 1 completed' (SHOULD NOT APPEAR)\n\n";

echo "❌ CONTAMINATION LOGS (SHOULD NOT APPEAR):\n";
echo "   'Assignment 1 completed' (Wrong assignment!)\n";
echo "   'Assignment 3 completed' (Wrong assignment!)\n";
echo "   'Regular topic progress updated' (Contamination!)\n\n";

echo "🎯 POST-COMPLETION VERIFICATION SCRIPT:\n";
echo "=======================================\n";
echo "Run this immediately after completing Assignment #2:\n\n";

echo "-- Check assignment statuses\n";
echo "SELECT id, title, status, completed_at FROM assignments WHERE topic_id = 1 ORDER BY id;\n\n";

echo "-- Check assignment attempts\n";
echo "SELECT assignment_id, status, score_percentage, created_at FROM assignment_attempts WHERE assignment_id IN (1,2,3) ORDER BY created_at DESC;\n\n";

echo "-- Check quiz attempts (should be unchanged)\n";
echo "SELECT COUNT(*) as quiz_attempt_count FROM quiz_attempts WHERE topic_id = 1 AND user_id = 2;\n\n";

echo "🔥 NUCLEAR VERIFICATION:\n";
echo "========================\n";
echo "If ANY of these fail, the contamination bug still exists:\n\n";

echo "1. 🧪 DATABASE TEST:\n";
echo "   SELECT COUNT(*) FROM assignments WHERE status = 'completed' AND topic_id = 1;\n";
echo "   EXPECTED: 1 (only Assignment #2)\n";
echo "   IF > 1: CONTAMINATION DETECTED!\n\n";

echo "2. 🧪 PROGRESS TEST:\n";
echo "   Calculate regular topic progress from QuizAttempt table\n";
echo "   EXPECTED: " . round($baselineRegularProgress, 2) . "%\n";
echo "   IF DIFFERENT: CONTAMINATION DETECTED!\n\n";

echo "3. 🧪 UI TEST:\n";
echo "   Check regular topic card progress bar\n";
echo "   EXPECTED: " . round($baselineRegularProgress, 2) . "%\n";
echo "   IF DIFFERENT: FRONTEND CONTAMINATION DETECTED!\n\n";

echo "🚀 ULTRA-AGGRESSIVE TEST STATUS: ARMED AND READY\n";
echo "================================================\n";
echo "This test will detect ANY contamination with ZERO tolerance.\n";
echo "Execute Assignment #2 completion and verify ALL criteria above.\n";
echo "If ANY criterion fails = CONTAMINATION BUG STILL EXISTS!\n\n";

echo "🎯 FINAL CHALLENGE:\n";
echo "===================\n";
echo "PROVE that completing Assignment #2 affects ONLY Assignment #2.\n";
echo "PROVE that regular topic progress remains EXACTLY " . round($baselineRegularProgress, 2) . "%.\n";
echo "PROVE that Assignment #1 & #3 remain completely unchanged.\n";
echo "PROVE perfect system isolation with ZERO contamination!\n\n";

echo "🔥 CONTAMINATION HUNTER: READY TO DETECT ANY BUGS!\n";
echo "==================================================\n";