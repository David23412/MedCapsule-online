<?php

/**
 * TASK 2 BASELINE QUERY MEASUREMENT SCRIPT
 * Measures query count for key endpoints before N+1 elimination
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create test user for authenticated endpoints
$testUser = User::factory()->create(['role' => User::ROLE_STUDENT]);

$endpoints = [
    'courses.index' => '/courses',
    'dashboard.home' => '/dashboard', 
    'course.show' => '/courses/1',
    'user.profile' => '/profile',
    'topic.show' => '/topics/1'
];

$baselineResults = [];

foreach ($endpoints as $name => $url) {
    echo "Measuring baseline for {$name}...\n";
    
    try {
        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        // Make request (simulate)
        $startTime = microtime(true);
        
        // Simulate the controller logic for each endpoint
        switch ($name) {
            case 'courses.index':
                $courses = \App\Models\Course::all();
                foreach ($courses as $course) {
                    $enrolledCount = $course->enrolled_students_count; // N+1 trigger
                    $completionPercentage = $course->completion_percentage; // N+1 trigger
                }
                break;
                
            case 'dashboard.home':
                $user = $testUser;
                $courses = $user->courses; // N+1 trigger
                foreach ($courses as $course) {
                    $progress = $course->completion_percentage; // N+1 trigger
                }
                break;
                
            case 'course.show':
                $course = \App\Models\Course::first();
                if ($course) {
                    $topics = $course->topics; // N+1 trigger
                    foreach ($topics as $topic) {
                        $progress = $topic->completion_percentage; // N+1 trigger
                    }
                }
                break;
                
            case 'user.profile':
                $user = $testUser;
                $progress = $user->course_progress; // N+1 trigger
                break;
                
            case 'topic.show':
                $topic = \App\Models\Topic::first();
                if ($topic) {
                    $questions = $topic->questions; // Potential N+1
                    $progress = $topic->completion_percentage; // N+1 trigger
                }
                break;
        }
        
        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        
        $result = [
            'endpoint' => $name,
            'url' => $url,
            'query_count' => count($queries),
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'queries' => $queries,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $baselineResults[$name] = $result;
        
        // Save individual endpoint results
        file_put_contents(
            __DIR__ . "/task2_baseline_{$name}.json",
            json_encode($result, JSON_PRETTY_PRINT)
        );
        
        echo "✅ {$name}: {$result['query_count']} queries, {$result['execution_time_ms']}ms\n";
        
    } catch (\Exception $e) {
        echo "❌ Error measuring {$name}: " . $e->getMessage() . "\n";
        $baselineResults[$name] = [
            'endpoint' => $name,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Save combined results
file_put_contents(
    __DIR__ . '/task2_baseline_combined.json',
    json_encode($baselineResults, JSON_PRETTY_PRINT)
);

echo "\n🎯 BASELINE MEASUREMENT COMPLETE\n";
echo "Results saved to storage/reports/task2_baseline_*.json\n";

// Summary
$totalQueries = array_sum(array_column($baselineResults, 'query_count'));
echo "📊 SUMMARY:\n";
foreach ($baselineResults as $name => $result) {
    if (isset($result['query_count'])) {
        echo "- {$name}: {$result['query_count']} queries\n";
    }
}
echo "- TOTAL: {$totalQueries} queries across all endpoints\n";