<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 TESTING REGULAR QUIZ FIX\n";
echo "===========================\n\n";

echo "🚨 CRITICAL BUG IDENTIFIED & FIXED:\n";
echo "====================================\n";
echo "The isAssignmentQuiz() method was incorrectly identifying regular quizzes as assignments!\n\n";

echo "BROKEN LOGIC (BEFORE):\n";
echo "----------------------\n";
echo "- Check if ANY assignment exists for user/topic/course\n";
echo "- If assignments exist → mark ALL quizzes as assignment type\n";
echo "- Result: Regular quizzes marked as 'assignment' type\n";
echo "- Consequence: Regular topic progress never updates!\n\n";

echo "FIXED LOGIC (AFTER):\n";
echo "--------------------\n";
echo "- Check if assignment_id is explicitly provided\n";
echo "- If assignment_id provided → verify it's a valid assignment\n";
echo "- If no assignment_id → treat as regular quiz\n";
echo "- Result: Proper quiz type detection\n\n";

echo "🧪 TESTING THE FIX:\n";
echo "===================\n";

$user = \App\Models\User::find(2);
$topic = \App\Models\Topic::find(1);
$course = \App\Models\Course::find(1);

if ($user && $topic && $course) {
    // Create a mock GradingService to test the method
    $gradingService = new \App\Services\GradingService(
        app(\App\Services\CacheService::class),
        app(\App\Services\UserStatsService::class),
        app(\App\Services\NotificationService::class),
        app(\App\Services\WrittenAnswerEvaluationService::class)
    );
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($gradingService);
    $method = $reflection->getMethod('isAssignmentQuiz');
    $method->setAccessible(true);
    
    echo "TEST 1: Regular Quiz (no assignment_id)\n";
    echo "---------------------------------------\n";
    $isAssignment1 = $method->invoke($gradingService, $user, $topic, $course, null);
    echo "Result: " . ($isAssignment1 ? "ASSIGNMENT" : "REGULAR") . "\n";
    echo "Expected: REGULAR\n";
    echo "Status: " . ($isAssignment1 ? "❌ FAILED" : "✅ PASSED") . "\n\n";
    
    echo "TEST 2: Assignment Quiz (with assignment_id=2)\n";
    echo "----------------------------------------------\n";
    $isAssignment2 = $method->invoke($gradingService, $user, $topic, $course, 2);
    echo "Result: " . ($isAssignment2 ? "ASSIGNMENT" : "REGULAR") . "\n";
    echo "Expected: ASSIGNMENT\n";
    echo "Status: " . ($isAssignment2 ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    
    echo "TEST 3: Invalid Assignment ID (assignment_id=999)\n";
    echo "------------------------------------------------\n";
    $isAssignment3 = $method->invoke($gradingService, $user, $topic, $course, 999);
    echo "Result: " . ($isAssignment3 ? "ASSIGNMENT" : "REGULAR") . "\n";
    echo "Expected: REGULAR\n";
    echo "Status: " . ($isAssignment3 ? "❌ FAILED" : "✅ PASSED") . "\n\n";
    
    if (!$isAssignment1 && $isAssignment2 && !$isAssignment3) {
        echo "🎉 ALL TESTS PASSED!\n";
        echo "✅ Regular quizzes will now be properly detected\n";
        echo "✅ Assignment quizzes will still work correctly\n";
        echo "✅ Regular topic progress should update properly\n\n";
    } else {
        echo "❌ SOME TESTS FAILED - NEEDS INVESTIGATION\n\n";
    }
    
} else {
    echo "❌ Test data not found\n";
}

echo "🎯 EXPECTED BEHAVIOR NOW:\n";
echo "=========================\n";
echo "1. Regular quiz (no assignment_id) → quiz_type = 'normal'\n";
echo "2. Assignment quiz (with assignment_id) → quiz_type = 'assignment'\n";
echo "3. Regular topic progress updates for regular quizzes\n";
echo "4. Assignment progress updates for assignment quizzes\n";
echo "5. Perfect separation between systems\n\n";

echo "✅ REGULAR QUIZ FIX TEST COMPLETE\n";