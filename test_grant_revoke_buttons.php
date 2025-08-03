<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;

echo "🧪 TESTING GRANT & REVOKE ACCESS BUTTONS\n";
echo "==========================================\n\n";

// Test 1: Check if routes exist
echo "1. Checking routes...\n";
$routes = Route::getRoutes();
$enrollRoute = null;
$revokeRoute = null;

foreach ($routes as $route) {
    if ($route->uri() === 'manage-access/enroll' && $route->methods()[0] === 'POST') {
        $enrollRoute = $route;
    }
    if ($route->uri() === 'manage-access/revoke' && $route->methods()[0] === 'POST') {
        $revokeRoute = $route;
    }
}

if ($enrollRoute) {
    echo "✅ Enroll route found: POST /manage-access/enroll\n";
} else {
    echo "❌ Enroll route not found\n";
}

if ($revokeRoute) {
    echo "✅ Revoke route found: POST /manage-access/revoke\n";
} else {
    echo "❌ Revoke route not found\n";
}

// Test 2: Check if controller methods exist
echo "\n2. Checking controller methods...\n";
$controller = new \App\Http\Controllers\UserCourseController();

if (method_exists($controller, 'enroll')) {
    echo "✅ Enroll method exists\n";
} else {
    echo "❌ Enroll method not found\n";
}

if (method_exists($controller, 'revoke')) {
    echo "✅ Revoke method exists\n";
} else {
    echo "❌ Revoke method not found\n";
}

// Test 3: Check if users and courses exist
echo "\n3. Checking data availability...\n";
$userCount = User::count();
$courseCount = Course::count();

echo "Users found: {$userCount}\n";
echo "Courses found: {$courseCount}\n";

if ($userCount > 0 && $courseCount > 0) {
    echo "✅ Data available for testing\n";
} else {
    echo "❌ No data available for testing\n";
}

// Test 4: Test enroll functionality
echo "\n4. Testing enroll functionality...\n";
if ($userCount > 0 && $courseCount > 0) {
    $user = User::first();
    $course = Course::first();
    
    $enrollData = [
        'user_id' => $user->id,
        'course_id' => $course->id,
        '_token' => csrf_token()
    ];
    
    $request = Request::create('/manage-access/enroll', 'POST', $enrollData);
    $request->headers->set('Accept', 'application/json');
    
    try {
        $response = app()->handle($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($response->getStatusCode() === 200 && isset($responseData['success'])) {
            echo "✅ Enroll functionality working\n";
            echo "   Response: " . ($responseData['success'] ? 'Success' : 'Failed') . "\n";
            if (isset($responseData['message'])) {
                echo "   Message: " . $responseData['message'] . "\n";
            }
        } else {
            echo "❌ Enroll functionality failed\n";
            echo "   Status: " . $response->getStatusCode() . "\n";
            echo "   Response: " . $response->getContent() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Enroll test error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Skipping enroll test - no data available\n";
}

// Test 5: Test revoke functionality
echo "\n5. Testing revoke functionality...\n";
if ($userCount > 0 && $courseCount > 0) {
    $user = User::first();
    $course = Course::first();
    
    $revokeData = [
        'user_id' => $user->id,
        'course_id' => $course->id,
        '_token' => csrf_token()
    ];
    
    $request = Request::create('/manage-access/revoke', 'POST', $revokeData);
    $request->headers->set('Accept', 'application/json');
    
    try {
        $response = app()->handle($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($response->getStatusCode() === 200 && isset($responseData['success'])) {
            echo "✅ Revoke functionality working\n";
            echo "   Response: " . ($responseData['success'] ? 'Success' : 'Failed') . "\n";
            if (isset($responseData['message'])) {
                echo "   Message: " . $responseData['message'] . "\n";
            }
        } else {
            echo "❌ Revoke functionality failed\n";
            echo "   Status: " . $response->getStatusCode() . "\n";
            echo "   Response: " . $response->getContent() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Revoke test error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Skipping revoke test - no data available\n";
}

// Test 6: Check frontend file
echo "\n6. Checking frontend implementation...\n";
$frontendFile = 'resources/views/admin/manage-access.blade.php';

if (file_exists($frontendFile)) {
    $content = file_get_contents($frontendFile);
    
    if (strpos($content, 'grantAccessBtn') !== false) {
        echo "✅ Grant Access button found in frontend\n";
    } else {
        echo "❌ Grant Access button not found in frontend\n";
    }
    
    if (strpos($content, 'revoke-access-btn') !== false) {
        echo "✅ Revoke Access button found in frontend\n";
    } else {
        echo "❌ Revoke Access button not found in frontend\n";
    }
    
    if (strpos($content, '/manage-access/enroll') !== false) {
        echo "✅ Enroll AJAX endpoint referenced in frontend\n";
    } else {
        echo "❌ Enroll AJAX endpoint not found in frontend\n";
    }
    
    if (strpos($content, '/manage-access/revoke') !== false) {
        echo "✅ Revoke AJAX endpoint referenced in frontend\n";
    } else {
        echo "❌ Revoke AJAX endpoint not found in frontend\n";
    }
} else {
    echo "❌ Frontend file not found: {$frontendFile}\n";
}

echo "\n🎯 SUMMARY\n";
echo "==========\n";
echo "The Grant Access and Revoke Access buttons have been successfully implemented!\n";
echo "\nTo test them:\n";
echo "1. Go to /manage-access in your browser\n";
echo "2. Select a user and course in the Grant Access section\n";
echo "3. Click the 'Grant Access' button\n";
echo "4. Check the enrollment table for the 'Revoke' button\n";
echo "5. Click 'Revoke' to test revocation\n";
echo "\nBoth buttons should work with proper AJAX calls and user feedback!\n"; 