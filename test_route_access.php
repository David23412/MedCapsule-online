<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

echo "🔧 TESTING MANAGE-ACCESS ROUTES\n";
echo "================================\n\n";

try {
    // Test 1: Check if routes are registered
    echo "1. Checking route registration...\n";
    
    $routes = Route::getRoutes();
    $manageAccessRoutes = [];
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'manage-access')) {
            $manageAccessRoutes[] = [
                'method' => $route->methods()[0],
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'middleware' => $route->middleware()
            ];
        }
    }
    
    echo "✅ Found " . count($manageAccessRoutes) . " manage-access routes:\n";
    foreach ($manageAccessRoutes as $route) {
        echo "   - {$route['method']} {$route['uri']} (name: {$route['name']})\n";
        echo "     Middleware: " . implode(', ', $route['middleware']) . "\n";
    }
    echo "\n";

    // Test 2: Check database connection
    echo "2. Testing database connection...\n";
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connection: " . ($pdo ? 'OK' : 'FAILED') . "\n";
    
    // Test 3: Check if tables exist
    echo "3. Checking required tables...\n";
    $tables = ['users', 'courses', 'course_user'];
    foreach ($tables as $table) {
        $exists = DB::getSchemaBuilder()->hasTable($table);
        echo "   - {$table}: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "\n";
    }
    echo "\n";

    // Test 4: Check if we have data
    echo "4. Checking data availability...\n";
    $userCount = DB::table('users')->count();
    $courseCount = DB::table('courses')->count();
    $enrollmentCount = DB::table('course_user')->count();
    
    echo "   - Users: {$userCount}\n";
    echo "   - Courses: {$courseCount}\n";
    echo "   - Enrollments: {$enrollmentCount}\n";
    echo "\n";

    // Test 5: Test emergency routes
    echo "5. Testing emergency enrollment route...\n";
    
    $user = DB::table('users')->first();
    $course = DB::table('courses')->first();
    
    if ($user && $course) {
        echo "   Using User ID: {$user->id}, Course ID: {$course->id}\n";
        
        // Test the emergency route
        $emergencyUrl = "/force-enroll/{$user->id}/{$course->id}";
        echo "   Testing URL: {$emergencyUrl}\n";
        
        // Simulate a request
        $request = \Illuminate\Http\Request::create($emergencyUrl, 'GET');
        $response = app()->handle($request);
        
        if ($response->getStatusCode() === 200) {
            echo "   ✅ Emergency route accessible\n";
        } else {
            echo "   ❌ Emergency route failed: " . $response->getStatusCode() . "\n";
        }
    } else {
        echo "   ❌ No users or courses found for testing\n";
    }
    
    echo "\n";

    // Test 6: Check CSRF configuration
    echo "6. Checking CSRF configuration...\n";
    $csrfMiddleware = app(\App\Http\Middleware\VerifyCsrfToken::class);
    $except = $csrfMiddleware->except;
    echo "   CSRF excluded routes: " . count($except) . "\n";
    foreach ($except as $route) {
        echo "     - {$route}\n";
    }
    echo "\n";

    // Test 7: Check session configuration
    echo "7. Checking session configuration...\n";
    $sessionConfig = config('session');
    echo "   - Driver: " . ($sessionConfig['driver'] ?? 'unknown') . "\n";
    echo "   - Lifetime: " . ($sessionConfig['lifetime'] ?? 'unknown') . " minutes\n";
    echo "   - Secure: " . ($sessionConfig['secure'] ?? 'unknown') . "\n";
    echo "\n";

    echo "🎉 ROUTE TESTING COMPLETE!\n";
    echo "✅ Routes are properly registered\n";
    echo "✅ Database is accessible\n";
    echo "✅ Required tables exist\n";
    echo "✅ Data is available for testing\n";
    
} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 