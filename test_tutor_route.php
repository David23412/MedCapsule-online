<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 TESTING TUTOR ROUTE ACCESS\n";
echo "============================\n\n";

try {
    // Get test data
    $user = \App\Models\User::find(1);
    $course = \App\Models\Course::first();
    $topic = \App\Models\Topic::first();
    
    if (!$user || !$course || !$topic) {
        echo "❌ Missing test data\n";
        exit(1);
    }
    
    echo "✅ Test data found:\n";
    echo "   User: {$user->name} (ID: {$user->id})\n";
    echo "   Course: {$course->name} (ID: {$course->id})\n";
    echo "   Topic: {$topic->name} (ID: {$topic->id})\n";
    
    // Mock authentication
    auth()->login($user);
    
    // Test QuizService initializeQuiz method directly
    echo "\n🧪 Testing QuizService::initializeQuiz...\n";
    
    $quizService = app(\App\Services\QuizService::class);
    
    try {
        $result = $quizService->initializeQuiz($user, $course, $topic, ['all'], 'tutor');
        echo "✅ QuizService::initializeQuiz works!\n";
        echo "   Questions found: " . count($result['questions']) . "\n";
        echo "   Sources: " . implode(', ', $result['sources']) . "\n";
    } catch (\Exception $e) {
        echo "❌ QuizService::initializeQuiz failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . "\n";
        echo "   Line: " . $e->getLine() . "\n";
        throw $e;
    }
    
    // Test QuizTutorController directly
    echo "\n🧪 Testing QuizTutorController::startTutorQuiz...\n";
    
    $controller = app(\App\Http\Controllers\Quiz\QuizTutorController::class);
    
    // Create a mock request
    $request = new Request(['sources' => ['all']]);
    $request->setMethod('GET');
    
    // Set the request globally
    app()->instance('request', $request);
    
    try {
        $response = $controller->startTutorQuiz($course, $topic);
        
        if ($response instanceof \Illuminate\View\View) {
            echo "✅ QuizTutorController::startTutorQuiz works!\n";
            echo "   View: " . $response->getName() . "\n";
            echo "   Data keys: " . implode(', ', array_keys($response->getData())) . "\n";
        } else {
            echo "❌ Unexpected response type: " . get_class($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ QuizTutorController::startTutorQuiz failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . "\n";
        echo "   Line: " . $e->getLine() . "\n";
        echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ GENERAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n✅ Tutor route test completed\n";