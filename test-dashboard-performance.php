<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DASHBOARD PERFORMANCE TEST ===\n\n";

// Enable query logging
DB::enableQueryLog();

// Test 1: Dashboard loading performance
echo "1. Testing dashboard loading performance...\n";
$startTime = microtime(true);

$tutor = User::where('role', 'tutor')->first();
if (!$tutor) {
    echo "No tutors found!\n";
    exit;
}

// Simulate authentication
auth()->login($tutor);

$controller = new App\Http\Controllers\Tutor\TutorDashboardController();

try {
    $request = new Illuminate\Http\Request();
    $response = $controller->index($request);
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    echo "   ✓ Dashboard loaded successfully\n";
    echo "   ⏱️  Execution time: " . number_format($executionTime, 2) . " ms\n";
    
    // Check query count
    $queries = DB::getQueryLog();
    echo "   📊 Database queries executed: " . count($queries) . "\n";
    
    // Show the most expensive queries
    $expensiveQueries = array_filter($queries, function($query) {
        return $query['time'] > 10; // Queries taking more than 10ms
    });
    
    if (!empty($expensiveQueries)) {
        echo "   ⚠️  Expensive queries found:\n";
        foreach ($expensiveQueries as $query) {
            echo "      - " . number_format($query['time'], 2) . "ms: " . substr($query['query'], 0, 100) . "...\n";
        }
    } else {
        echo "   ✅ No expensive queries detected\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Dashboard error: " . $e->getMessage() . "\n";
}

// Clear query log
DB::flushQueryLog();

// Test 2: Student data retrieval efficiency
echo "\n2. Testing student data retrieval...\n";
$startTime = microtime(true);

$students = $tutor->assignedStudents()->with([
    'quizAttempts' => function ($query) {
        $query->latest()->take(10)->with('topic:id,name');
    }
])->withCount('quizAttempts')->get();

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;

echo "   ✓ Retrieved {$students->count()} students\n";
echo "   ⏱️  Execution time: " . number_format($executionTime, 2) . " ms\n";

$queries = DB::getQueryLog();
echo "   📊 Database queries executed: " . count($queries) . "\n";

// Test 3: Memory usage
echo "\n3. Testing memory usage...\n";
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

echo "   💾 Current memory usage: " . number_format($memoryUsage / 1024 / 1024, 2) . " MB\n";
echo "   📈 Peak memory usage: " . number_format($peakMemory / 1024 / 1024, 2) . " MB\n";

// Test 4: Cache efficiency
echo "\n4. Testing cache efficiency...\n";
$cacheKey = "dashboard_students_{$tutor->id}";

// First call (should hit database)
$startTime = microtime(true);
\Illuminate\Support\Facades\Cache::forget($cacheKey);
$students1 = $controller->index($request);
$firstCallTime = (microtime(true) - $startTime) * 1000;

// Second call (should hit cache)
$startTime = microtime(true);
$students2 = $controller->index($request);
$secondCallTime = (microtime(true) - $startTime) * 1000;

echo "   🔄 First call (no cache): " . number_format($firstCallTime, 2) . " ms\n";
echo "   ⚡ Second call (with cache): " . number_format($secondCallTime, 2) . " ms\n";
echo "   📊 Cache efficiency: " . number_format((($firstCallTime - $secondCallTime) / $firstCallTime) * 100, 1) . "% faster\n";

// Test 5: Export performance
echo "\n5. Testing export performance...\n";
$startTime = microtime(true);

try {
    $request = new Illuminate\Http\Request();
    $response = $controller->exportAllReports($request);
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;
    
    echo "   ✓ Export completed successfully\n";
    echo "   ⏱️  Execution time: " . number_format($executionTime, 2) . " ms\n";
    
} catch (Exception $e) {
    echo "   ✗ Export error: " . $e->getMessage() . "\n";
}

echo "\n=== PERFORMANCE TEST COMPLETE ===\n";

// Performance recommendations
echo "\n📋 PERFORMANCE SUMMARY:\n";
if ($executionTime < 100) {
    echo "   ✅ Excellent performance (< 100ms)\n";
} elseif ($executionTime < 500) {
    echo "   ✅ Good performance (< 500ms)\n";
} elseif ($executionTime < 1000) {
    echo "   ⚠️  Acceptable performance (< 1s)\n";
} else {
    echo "   ❌ Poor performance (> 1s) - needs optimization\n";
}

if (count($queries) < 10) {
    echo "   ✅ Efficient database usage (< 10 queries)\n";
} elseif (count($queries) < 20) {
    echo "   ⚠️  Moderate database usage (< 20 queries)\n";
} else {
    echo "   ❌ High database usage (> 20 queries) - consider optimization\n";
}

if ($peakMemory < 50 * 1024 * 1024) { // 50MB
    echo "   ✅ Low memory usage (< 50MB)\n";
} elseif ($peakMemory < 100 * 1024 * 1024) { // 100MB
    echo "   ⚠️  Moderate memory usage (< 100MB)\n";
} else {
    echo "   ❌ High memory usage (> 100MB) - consider optimization\n";
}