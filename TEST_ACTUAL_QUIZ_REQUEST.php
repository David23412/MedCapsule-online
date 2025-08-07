<?php

/**
 * 🔥 TEST ACTUAL QUIZ REQUEST 🔥
 * Simulate the exact request that's failing with 403
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 TESTING ACTUAL QUIZ REQUEST 🔥\n";
echo "==================================\n\n";

// Get a test user (assuming user ID 1 exists)
$user = \App\Models\User::find(1);
if (!$user) {
    echo "❌ No user found with ID 1\n";
    exit;
}

echo "✅ Test user found: {$user->name} (ID: {$user->id})\n";

// Create the exact request that's failing
$request = \Illuminate\Http\Request::create(
    '/quiz/1/1/grade',
    'POST',
    [
        'answers' => ['1' => 'A', '2' => 'B'], // Sample answers
        'duration_seconds' => 60,
        'mode' => 'quiz',
        '_token' => csrf_token()
    ],
    [], // cookies
    [], // files
    [
        'HTTP_HOST' => 'd781485850d4.ngrok-free.app',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CSRF_TOKEN' => csrf_token(),
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Test Browser)',
        'HTTPS' => 'on'
    ]
);

// Set the authenticated user
$request->setUserResolver(function () use ($user) {
    return $user;
});

echo "\nRequest details:\n";
echo "- URL: {$request->fullUrl()}\n";
echo "- Method: {$request->method()}\n";
echo "- Path: {$request->path()}\n";
echo "- Host: {$request->getHost()}\n";
echo "- CSRF token: " . substr(csrf_token(), 0, 10) . "...\n";
echo "- User: {$user->name}\n";

// Test 1: Check route resolution
echo "\n1. Testing route resolution...\n";
try {
    $route = \Route::getRoutes()->match($request);
    echo "   ✅ Route found: {$route->getName()}\n";
    echo "   ✅ Route URI: {$route->uri()}\n";
    echo "   ✅ Route action: {$route->getActionName()}\n";
    
    $middleware = $route->gatherMiddleware();
    echo "   ✅ Middleware: " . implode(', ', $middleware) . "\n";
} catch (Exception $e) {
    echo "   ❌ Route resolution failed: {$e->getMessage()}\n";
}

// Test 2: Check authentication
echo "\n2. Testing authentication...\n";
auth()->login($user);
echo "   ✅ User logged in: " . (auth()->check() ? 'YES' : 'NO') . "\n";
echo "   ✅ Auth user ID: " . (auth()->id() ?: 'NONE') . "\n";

// Test 3: Check course/topic access
echo "\n3. Testing course/topic access...\n";
try {
    $course = \App\Models\Course::find(1);
    $topic = \App\Models\Topic::find(1);
    
    if ($course && $topic) {
        echo "   ✅ Course found: {$course->name}\n";
        echo "   ✅ Topic found: {$topic->name}\n";
        
        $hasAccess = $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
        echo "   ✅ User has access: " . ($hasAccess ? 'YES' : 'NO') . "\n";
        
        if (!$hasAccess) {
            echo "   🔧 Granting access...\n";
            \DB::table('course_user')->updateOrInsert(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['is_enrolled' => true, 'enrolled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
            echo "   ✅ Access granted\n";
        }
    } else {
        echo "   ❌ Course or topic not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Access check failed: {$e->getMessage()}\n";
}

// Test 4: Simulate the actual request
echo "\n4. Simulating actual request...\n";
try {
    echo "   📡 Making request to application...\n";
    
    $response = $app->handle($request);
    $statusCode = $response->getStatusCode();
    
    echo "   📡 Response status: {$statusCode}\n";
    echo "   📡 Response headers:\n";
    foreach ($response->headers->all() as $key => $values) {
        echo "      {$key}: " . implode(', ', $values) . "\n";
    }
    
    if ($statusCode === 403) {
        echo "   ❌ 403 FORBIDDEN ERROR CONFIRMED\n";
        echo "   📄 Response content (first 500 chars):\n";
        echo "   " . substr($response->getContent(), 0, 500) . "\n";
    } elseif ($statusCode === 200) {
        echo "   ✅ Request successful!\n";
        echo "   📄 Response content (first 200 chars):\n";
        echo "   " . substr($response->getContent(), 0, 200) . "\n";
    } else {
        echo "   ⚠️  Unexpected status: {$statusCode}\n";
        echo "   📄 Response content (first 200 chars):\n";
        echo "   " . substr($response->getContent(), 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Request failed: {$e->getMessage()}\n";
    echo "   📄 Stack trace:\n";
    echo "   " . substr($e->getTraceAsString(), 0, 1000) . "\n";
}

echo "\n🎯 TEST COMPLETE\n";
echo "================\n";
echo "If you see a 403 error above, check the Laravel logs for more details.\n";
echo "The CSRF debugging should show exactly what's happening.\n";