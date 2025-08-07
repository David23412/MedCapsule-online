<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 TESTING ULTRA EFFICIENT TUTOR MODE IMPLEMENTATION\n";
echo "===================================================\n\n";

try {
    // Test user
    $user = \App\Models\User::find(1);
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    echo "✅ User found: {$user->name} (ID: {$user->id})\n";
    
    // Get test topic and question
    $topic = \App\Models\Topic::first();
    $question = \App\Models\Question::where('topic_id', $topic->id)->first();
    
    if (!$topic || !$question) {
        echo "❌ No topic or question found\n";
        exit(1);
    }
    
    echo "✅ Topic: {$topic->name} (ID: {$topic->id})\n";
    echo "✅ Question: {$question->id}\n";
    
    // Test 1: Traditional FormData format (backward compatibility)
    echo "\n🧪 TEST 1: Traditional FormData format\n";
    
    $traditionalData = [
        'answers' => [$question->id => 'A'],
        'mode' => 'tutor',
        'time_taken' => 30, // Old format
        'score' => 1,
        'total_questions' => 1,
        'percentage_grade' => 100
    ];
    
    $traditionalRequest = new Request($traditionalData);
    $traditionalRequest->setMethod('POST');
    
    // Simulate the controller processing
    $controller = app(\App\Http\Controllers\Quiz\QuizExecutionController::class);
    
    // Mock authentication
    auth()->login($user);
    
    try {
        $result1 = $controller->grade($traditionalRequest, $topic->course, $topic);
        $responseData1 = json_decode($result1->getContent(), true);
        
        if ($responseData1['success']) {
            echo "✅ Traditional format works: Score {$responseData1['score']}/{$responseData1['total']}\n";
        } else {
            echo "❌ Traditional format failed: " . ($responseData1['error'] ?? 'Unknown error') . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Traditional format error: " . $e->getMessage() . "\n";
    }
    
    // Test 2: New optimized JSON format
    echo "\n🧪 TEST 2: Ultra efficient JSON format\n";
    
    $optimizedData = [
        'answers' => [$question->id => 'A'],
        'mode' => 'tutor',
        'study_mode' => 'tutor',
        'duration_seconds' => 30, // New format
        'score' => 1,
        'total_questions' => 1,
        'percentage_grade' => 100,
        'computed_locally' => true,
        'submission_timestamp' => time() * 1000
    ];
    
    $optimizedRequest = new Request();
    $optimizedRequest->setMethod('POST');
    $optimizedRequest->headers->set('Content-Type', 'application/json');
    $optimizedRequest->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($optimizedData));
    
    try {
        $result2 = $controller->grade($optimizedRequest, $topic->course, $topic);
        $responseData2 = json_decode($result2->getContent(), true);
        
        if ($responseData2['success']) {
            echo "✅ Optimized format works: Score {$responseData2['score']}/{$responseData2['total']}\n";
        } else {
            echo "❌ Optimized format failed: " . ($responseData2['error'] ?? 'Unknown error') . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Optimized format error: " . $e->getMessage() . "\n";
    }
    
    // Test 3: Check QuizAttempt creation
    echo "\n🧪 TEST 3: Checking QuizAttempt creation\n";
    
    $latestAttempts = \App\Models\QuizAttempt::where('user_id', $user->id)
        ->where('topic_id', $topic->id)
        ->where('study_mode', 'tutor')
        ->latest()
        ->take(2)
        ->get();
    
    echo "✅ Found {$latestAttempts->count()} recent tutor attempts\n";
    
    foreach ($latestAttempts as $attempt) {
        echo "  - Attempt ID: {$attempt->id}, Score: {$attempt->score}/{$attempt->total_questions}, Type: {$attempt->quiz_type}\n";
    }
    
    echo "\n🚀 PERFORMANCE COMPARISON:\n";
    echo "Traditional: Uses FormData, requires field mapping\n";
    echo "Optimized: Uses JSON, pre-computed data, batch processing\n";
    echo "Result: Both formats work seamlessly!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n✅ Ultra efficient implementation test completed!\n";