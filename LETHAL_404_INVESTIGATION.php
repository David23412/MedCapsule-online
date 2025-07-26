<?php

require_once 'vendor/autoload.php';

echo "💀 LETHAL 404 INVESTIGATION - NUCLEAR DEBUGGING PROTOCOL 💀\n";
echo "================================================================\n\n";

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;

// 🔍 PHASE 1: ROUTE VERIFICATION
echo "🔍 PHASE 1: ROUTE VERIFICATION\n";
echo "==============================\n";

$suspiciousRoutes = [
    '/topics/2/source-progress?mode=tutor',
    '/quiz/tutor/check/7',
    '/quiz/6/2/grade',
    '/sounds/correct-chime.mp3',
    '/sounds/quiz-wrong.mp3',
    '/sounds/quiz-perfect.mp3',
    '/sounds/quiz-partial.mp3',
    '/sounds/Single-correct.mp3'
];

foreach ($suspiciousRoutes as $route) {
    echo "Testing route: $route\n";
    
    // Check if route exists in Laravel routes
    $routeExists = false;
    try {
        $routes = Route::getRoutes();
        foreach ($routes as $r) {
            if (str_contains($route, $r->uri()) || str_contains($r->uri(), explode('?', $route)[0])) {
                $routeExists = true;
                echo "  ✅ Route pattern found: {$r->uri()}\n";
                break;
            }
        }
        
        if (!$routeExists && str_starts_with($route, '/sounds/')) {
            // Check if file exists
            $filePath = public_path(ltrim($route, '/'));
            if (file_exists($filePath)) {
                echo "  ✅ Static file exists: $filePath\n";
            } else {
                echo "  ❌ Static file MISSING: $filePath\n";
            }
        } elseif (!$routeExists) {
            echo "  ❌ Route NOT FOUND\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error checking route: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// 🔍 PHASE 2: CONTROLLER METHOD VERIFICATION
echo "🔍 PHASE 2: CONTROLLER METHOD VERIFICATION\n";
echo "==========================================\n";

$controllers = [
    'App\Http\Controllers\TopicController' => 'getSourceProgress',
    'App\Http\Controllers\Quiz\QuizTutorController' => 'checkTutorAnswer',
    'App\Http\Controllers\Quiz\QuizExecutionController' => 'grade',
];

foreach ($controllers as $controller => $method) {
    echo "Testing controller: $controller::$method\n";
    
    try {
        if (class_exists($controller)) {
            echo "  ✅ Controller class exists\n";
            
            if (method_exists($controller, $method)) {
                echo "  ✅ Method exists\n";
                
                // Try to instantiate
                $reflection = new ReflectionClass($controller);
                $constructor = $reflection->getConstructor();
                
                if ($constructor) {
                    $params = $constructor->getParameters();
                    echo "  📋 Constructor requires " . count($params) . " parameters\n";
                    foreach ($params as $param) {
                        echo "    - {$param->getName()}: {$param->getType()}\n";
                    }
                } else {
                    echo "  ✅ No constructor parameters required\n";
                }
            } else {
                echo "  ❌ Method MISSING\n";
            }
        } else {
            echo "  ❌ Controller class MISSING\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// 🔍 PHASE 3: LIVE ROUTE TESTING
echo "🔍 PHASE 3: LIVE ROUTE TESTING\n";
echo "===============================\n";

// Get test data
$user = User::find(2);
$topic = Topic::find(2);
$course = Course::find(6);

if ($user && $topic && $course) {
    echo "Test data found:\n";
    echo "  User: {$user->name} (ID: {$user->id})\n";
    echo "  Topic: {$topic->name} (ID: {$topic->id})\n";
    echo "  Course: {$course->name} (ID: {$course->id})\n\n";
    
    // Test 1: Source Progress Route
    echo "TEST 1: Source Progress Route\n";
    echo "-----------------------------\n";
    try {
        auth()->login($user);
        request()->merge(['mode' => 'tutor']);
        
        $controller = new App\Http\Controllers\TopicController();
        $response = $controller->getSourceProgress($topic);
        
        echo "  ✅ Status: " . $response->getStatusCode() . "\n";
        $data = json_decode($response->getContent(), true);
        echo "  ✅ Response keys: " . implode(', ', array_keys($data)) . "\n";
        
        if (isset($data['progress'])) {
            echo "  ✅ Progress data found for sources: " . implode(', ', array_keys($data['progress'])) . "\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        echo "  📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    echo "\n";
    
    // Test 2: Tutor Answer Check Route
    echo "TEST 2: Tutor Answer Check Route\n";
    echo "--------------------------------\n";
    try {
        $question = App\Models\Question::where('topic_id', $topic->id)->first();
        if ($question) {
            request()->merge(['answer' => 'A']);
            
            $controller = new App\Http\Controllers\Quiz\QuizTutorController(
                app(App\Services\QuizService::class),
                app(App\Services\CacheService::class)
            );
            
            $response = $controller->checkTutorAnswer(request(), $question);
            echo "  ✅ Status: " . $response->getStatusCode() . "\n";
            
            $data = json_decode($response->getContent(), true);
            echo "  ✅ Response success: " . ($data['success'] ? 'true' : 'false') . "\n";
        } else {
            echo "  ❌ No questions found for topic\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        echo "  📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    echo "\n";
    
    // Test 3: Quiz Grade Route
    echo "TEST 3: Quiz Grade Route\n";
    echo "------------------------\n";
    try {
        $questions = App\Models\Question::where('topic_id', $topic->id)->limit(2)->get();
        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = 'test answer';
        }
        
        request()->merge([
            'answers' => $answers,
            'time_taken' => 300,
            'mode' => 'tutor'
        ]);
        
        $controller = new App\Http\Controllers\Quiz\QuizExecutionController(
            app(App\Services\GradingService::class),
            app(App\Services\QuizService::class),
            app(App\Services\CacheService::class),
            app(App\Services\UserStatsService::class)
        );
        
        $response = $controller->grade(request(), $course, $topic);
        echo "  ✅ Status: " . $response->getStatusCode() . "\n";
        
        $data = json_decode($response->getContent(), true);
        echo "  ✅ Response success: " . ($data['success'] ? 'true' : 'false') . "\n";
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        echo "  📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    echo "\n";
    
} else {
    echo "❌ Test data not found\n";
    echo "  User 2: " . ($user ? "✅" : "❌") . "\n";
    echo "  Topic 2: " . ($topic ? "✅" : "❌") . "\n";
    echo "  Course 6: " . ($course ? "✅" : "❌") . "\n\n";
}

// 🔍 PHASE 4: STATIC ASSET VERIFICATION
echo "🔍 PHASE 4: STATIC ASSET VERIFICATION\n";
echo "=====================================\n";

$staticAssets = [
    'sounds/correct-chime.mp3',
    'sounds/quiz-wrong.mp3',
    'sounds/quiz-perfect.mp3',
    'sounds/quiz-partial.mp3',
    'sounds/Single-correct.mp3',
    'css/app.css',
    'js/app.js'
];

foreach ($staticAssets as $asset) {
    $fullPath = public_path($asset);
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "  ✅ $asset (Size: " . number_format($size) . " bytes)\n";
    } else {
        echo "  ❌ $asset MISSING\n";
    }
}

// 🔍 PHASE 5: LOG FILE ANALYSIS
echo "\n🔍 PHASE 5: RECENT LOG ANALYSIS\n";
echo "===============================\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "Analyzing recent log entries...\n";
    
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -50); // Last 50 lines
    
    $errorCount = 0;
    $notFoundCount = 0;
    
    foreach ($recentLines as $line) {
        if (str_contains($line, '404') || str_contains($line, 'NotFoundHttpException')) {
            $notFoundCount++;
            echo "  🔍 404 Error: " . trim($line) . "\n";
        } elseif (str_contains($line, 'ERROR') || str_contains($line, 'Exception')) {
            $errorCount++;
            if ($errorCount <= 5) { // Show only first 5 errors
                echo "  ❌ Error: " . trim($line) . "\n";
            }
        }
    }
    
    echo "\nSummary:\n";
    echo "  404 Errors found: $notFoundCount\n";
    echo "  Other errors found: $errorCount\n";
} else {
    echo "❌ Log file not found: $logFile\n";
}

// 🔍 PHASE 6: MIDDLEWARE ANALYSIS
echo "\n🔍 PHASE 6: MIDDLEWARE ANALYSIS\n";
echo "===============================\n";

$middlewareGroups = [
    'web' => ['web'],
    'api' => ['api'],
    'auth' => ['auth'],
    'throttle' => ['throttle:60,1']
];

foreach ($middlewareGroups as $name => $middleware) {
    echo "Middleware group '$name':\n";
    try {
        $middlewareInstance = app('Illuminate\Contracts\Http\Kernel');
        echo "  ✅ Kernel accessible\n";
    } catch (Exception $e) {
        echo "  ❌ Kernel error: " . $e->getMessage() . "\n";
    }
}

echo "\n💀 LETHAL INVESTIGATION COMPLETE 💀\n";
echo "===================================\n";
echo "Check the output above for any ❌ indicators that might explain the 404 error.\n";