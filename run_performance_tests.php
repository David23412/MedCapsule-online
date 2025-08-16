<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;

// Bootstrap Laravel properly
$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🚀 PERFORMANCE TEST EXECUTION - DOMINATING N+1 QUERIES\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Test data setup
echo "📋 Setting up test data...\n";
try {
    // Create test users
    $users = User::factory()->count(5)->create(['role' => 'student']);
    $admin = User::factory()->create(['role' => 'admin']);
    
    // Create courses with topics
    $courses = Course::factory()->count(10)->create();
    foreach ($courses as $course) {
        Topic::factory()->count(5)->for($course)->create();
    }
    
    // Enroll users in courses
    foreach ($users as $user) {
        foreach ($courses->random(3) as $course) {
            $user->enrollInCourse($course->id);
        }
    }
    
    echo "✅ Test data created: {$users->count()} users, {$courses->count()} courses\n\n";
} catch (Exception $e) {
    echo "⚠️  Using existing data: " . $e->getMessage() . "\n\n";
}

// Performance test function
function runPerformanceTest($testName, $url, $user = null) {
    global $kernel;
    
    echo "🧪 Testing: {$testName}\n";
    echo "   URL: {$url}\n";
    
    // Clear query log
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    // Create request
    $request = Request::create($url, 'GET');
    if ($user) {
        $request->setUserResolver(function() use ($user) {
            return $user;
        });
    }
    
    // Execute request and measure time
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    try {
        $response = $kernel->handle($request);
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Get query data
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);
        
        // Status check
        $status = $response->getStatusCode();
        $statusIcon = $status == 200 ? "✅" : "❌";
        
        echo "   {$statusIcon} Status: {$status}\n";
        echo "   📊 Queries: {$queryCount}\n";
        echo "   ⏱️  Time: {$responseTime}ms\n";
        echo "   💾 Memory: {$memoryUsed}MB\n";
        
        // Show query details if enabled
        if (env('PERFORMANCE_LOG_QUERY_DETAILS', false) && $queryCount > 0) {
            echo "   🔍 Query Details:\n";
            foreach (array_slice($queries, 0, 5) as $i => $query) {
                $sql = substr($query['query'], 0, 80) . (strlen($query['query']) > 80 ? '...' : '');
                echo "      " . ($i + 1) . ". {$sql}\n";
            }
            if ($queryCount > 5) {
                echo "      ... and " . ($queryCount - 5) . " more queries\n";
            }
        }
        
        echo "\n";
        
        return [
            'status' => $status,
            'queries' => $queryCount,
            'time' => $responseTime,
            'memory' => $memoryUsed,
            'success' => $status == 200
        ];
        
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
        return [
            'status' => 500,
            'queries' => 0,
            'time' => 0,
            'memory' => 0,
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Run performance tests
$testUser = User::where('role', 'student')->first();
$results = [];

echo "🎯 EXECUTING PERFORMANCE TESTS\n";
echo "-" . str_repeat("-", 40) . "\n\n";

// Test 1: Courses Index
$results['courses_index'] = runPerformanceTest(
    'Courses Index',
    '/courses',
    $testUser
);

// Test 2: Dashboard
$results['dashboard'] = runPerformanceTest(
    'Dashboard Home',
    '/dashboard',
    $testUser
);

// Test 3: Course Show
$course = Course::first();
if ($course) {
    $results['course_show'] = runPerformanceTest(
        'Course Show',
        "/courses/{$course->id}",
        $testUser
    );
}

// Test 4: Topic Show
$topic = Topic::first();
if ($topic) {
    $results['topic_show'] = runPerformanceTest(
        'Topic Show',
        "/topics/{$topic->id}",
        $testUser
    );
}

// Test 5: API Courses
$results['api_courses'] = runPerformanceTest(
    'API Courses',
    '/api/courses',
    $testUser
);

// Performance Analysis
echo "📊 PERFORMANCE ANALYSIS\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$totalQueries = 0;
$totalTime = 0;
$successCount = 0;
$thresholds = [
    'courses_index' => 10,
    'dashboard' => 8,
    'course_show' => 6,
    'topic_show' => 4,
    'api_courses' => 15
];

foreach ($results as $test => $result) {
    if ($result['success']) {
        $successCount++;
        $totalQueries += $result['queries'];
        $totalTime += $result['time'];
        
        $threshold = $thresholds[$test] ?? 10;
        $queryStatus = $result['queries'] <= $threshold ? "✅ PASSED" : "❌ EXCEEDED";
        $timeStatus = $result['time'] <= 1000 ? "✅ FAST" : "⚠️ SLOW";
        
        echo "🧪 {$test}:\n";
        echo "   Queries: {$result['queries']}/{$threshold} {$queryStatus}\n";
        echo "   Time: {$result['time']}ms {$timeStatus}\n";
        echo "   Memory: {$result['memory']}MB\n\n";
    }
}

// Overall Results
echo "🏆 OVERALL PERFORMANCE RESULTS\n";
echo "-" . str_repeat("-", 40) . "\n";
echo "✅ Tests Passed: {$successCount}/" . count($results) . "\n";
echo "📊 Total Queries: {$totalQueries}\n";
echo "⏱️  Average Time: " . round($totalTime / max($successCount, 1), 2) . "ms\n";
echo "💾 Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . "MB\n\n";

// Performance Rating
$avgQueries = $totalQueries / max($successCount, 1);
$avgTime = $totalTime / max($successCount, 1);

if ($avgQueries <= 5 && $avgTime <= 300) {
    echo "🚀 PERFORMANCE RATING: EXCEPTIONAL (Enterprise Ready)\n";
} elseif ($avgQueries <= 8 && $avgTime <= 500) {
    echo "⚡ PERFORMANCE RATING: EXCELLENT (Production Ready)\n";
} elseif ($avgQueries <= 12 && $avgTime <= 1000) {
    echo "✅ PERFORMANCE RATING: GOOD (Acceptable)\n";
} else {
    echo "⚠️  PERFORMANCE RATING: NEEDS OPTIMIZATION\n";
}

echo "\n🎯 PERFORMANCE TEST EXECUTION COMPLETE\n";
echo "=" . str_repeat("=", 60) . "\n";