<?php

require_once 'vendor/autoload.php';

echo "🎯 TARGETED 404 HUNTER - PRECISION STRIKE 🎯\n";
echo "=============================================\n\n";

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;

// 🔍 PHASE 1: ENABLE DETAILED LOGGING
echo "🔍 PHASE 1: ENABLING DETAILED LOGGING\n";
echo "=====================================\n";

// Clear existing logs
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    file_put_contents($logFile, ''); // Clear log file
    echo "✅ Cleared existing log file\n";
}

// Enable detailed logging
config(['app.debug' => true]);
config(['logging.default' => 'single']);
config(['logging.channels.single.level' => 'debug']);

echo "✅ Debug logging enabled\n\n";

// 🔍 PHASE 2: SIMULATE TUTOR MODE ACCESS
echo "🔍 PHASE 2: SIMULATING TUTOR MODE ACCESS\n";
echo "========================================\n";

try {
    // Simulate accessing tutor mode
    $user = App\Models\User::find(2);
    $topic = App\Models\Topic::find(2);
    $course = App\Models\Course::find(6);
    
    if ($user && $topic && $course) {
        auth()->login($user);
        
        echo "Simulating tutor mode page load...\n";
        
        // Test 1: Tutor mode start
        echo "TEST 1: Tutor Mode Start\n";
        $controller = new App\Http\Controllers\Quiz\QuizTutorController(
            app(App\Services\QuizService::class),
            app(App\Services\CacheService::class)
        );
        
        // Mock request
        request()->merge(['sources' => ['all']]);
        
        try {
            $response = $controller->startTutorQuiz($course, $topic);
            echo "  ✅ Tutor mode start: Success\n";
        } catch (Exception $e) {
            echo "  ❌ Tutor mode start error: " . $e->getMessage() . "\n";
        }
        
        // Test 2: Individual answer check
        echo "TEST 2: Individual Answer Check\n";
        $question = App\Models\Question::where('topic_id', $topic->id)->first();
        if ($question) {
            request()->merge(['answer' => 'A']);
            
            try {
                $response = $controller->checkTutorAnswer(request(), $question);
                echo "  ✅ Answer check: Success\n";
            } catch (Exception $e) {
                echo "  ❌ Answer check error: " . $e->getMessage() . "\n";
            }
        }
        
        // Test 3: Source progress fetch
        echo "TEST 3: Source Progress Fetch\n";
        request()->merge(['mode' => 'tutor']);
        
        $topicController = new App\Http\Controllers\TopicController();
        try {
            $response = $topicController->getSourceProgress($topic);
            echo "  ✅ Source progress: Success\n";
        } catch (Exception $e) {
            echo "  ❌ Source progress error: " . $e->getMessage() . "\n";
        }
        
        // Test 4: Quiz grading
        echo "TEST 4: Quiz Grading\n";
        $questions = App\Models\Question::where('topic_id', $topic->id)->limit(2)->get();
        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = 'test';
        }
        
        request()->merge([
            'answers' => $answers,
            'time_taken' => 300,
            'mode' => 'tutor'
        ]);
        
        $gradeController = new App\Http\Controllers\Quiz\QuizExecutionController(
            app(App\Services\GradingService::class),
            app(App\Services\QuizService::class),
            app(App\Services\CacheService::class),
            app(App\Services\UserStatsService::class)
        );
        
        try {
            $response = $gradeController->grade(request(), $course, $topic);
            echo "  ✅ Quiz grading: Success\n";
        } catch (Exception $e) {
            echo "  ❌ Quiz grading error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Test data not available\n";
    }
    
} catch (Exception $e) {
    echo "❌ Simulation error: " . $e->getMessage() . "\n";
}

echo "\n";

// 🔍 PHASE 3: CHECK FOR RECENT 404 ERRORS
echo "🔍 PHASE 3: CHECKING FOR RECENT 404 ERRORS\n";
echo "===========================================\n";

// Check Laravel logs
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    if (!empty($logContent)) {
        echo "Recent log entries:\n";
        echo "-------------------\n";
        echo $logContent;
    } else {
        echo "No new log entries generated\n";
    }
} else {
    echo "Log file not found\n";
}

// 🔍 PHASE 4: CHECK NGINX/APACHE ACCESS LOGS
echo "\n🔍 PHASE 4: CHECKING WEB SERVER LOGS\n";
echo "====================================\n";

$possibleLogPaths = [
    '/var/log/nginx/access.log',
    '/var/log/nginx/error.log',
    '/var/log/apache2/access.log',
    '/var/log/apache2/error.log',
    '/usr/local/var/log/nginx/access.log',
    '/usr/local/var/log/nginx/error.log'
];

foreach ($possibleLogPaths as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        echo "Found web server log: $logPath\n";
        
        // Get last 20 lines
        $lines = [];
        $handle = fopen($logPath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $lines[] = $line;
                if (count($lines) > 100) {
                    array_shift($lines); // Keep only last 100 lines
                }
            }
            fclose($handle);
            
            // Look for 404 errors in recent lines
            $found404 = false;
            foreach (array_slice($lines, -20) as $line) {
                if (str_contains($line, ' 404 ') || str_contains($line, '"404"')) {
                    echo "  🔍 404 Found: " . trim($line) . "\n";
                    $found404 = true;
                }
            }
            
            if (!$found404) {
                echo "  ✅ No recent 404 errors in this log\n";
            }
        }
    }
}

// 🔍 PHASE 5: ROUTE DEBUGGING
echo "\n🔍 PHASE 5: DETAILED ROUTE DEBUGGING\n";
echo "====================================\n";

// Check specific routes that might be causing issues
$routesToTest = [
    ['GET', '/topics/2/source-progress', ['mode' => 'tutor']],
    ['POST', '/quiz/tutor/check/7', ['answer' => 'A']],
    ['POST', '/quiz/6/2/grade', ['answers' => [7 => 'A'], 'time_taken' => 300, 'mode' => 'tutor']]
];

foreach ($routesToTest as [$method, $uri, $params]) {
    echo "Testing $method $uri\n";
    
    try {
        // Create a mock request
        $request = \Illuminate\Http\Request::create($uri, $method, $params);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        // Get the route
        $route = app('router')->getRoutes()->match($request);
        
        if ($route) {
            echo "  ✅ Route matched: " . $route->getName() . "\n";
            echo "  📋 Controller: " . $route->getActionName() . "\n";
        } else {
            echo "  ❌ No route matched\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Route test error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "🎯 TARGETED INVESTIGATION COMPLETE 🎯\n";
echo "=====================================\n";
echo "If no specific 404 sources were found, the error might be:\n";
echo "1. Browser cache issues\n";
echo "2. CDN/external resource failures\n";
echo "3. JavaScript-generated requests\n";
echo "4. Intermittent network issues\n";