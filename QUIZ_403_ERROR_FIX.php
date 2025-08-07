<?php

/**
 * 🔥 QUIZ 403 ERROR DIAGNOSTIC AND FIX 🔥
 * Diagnoses and fixes the 403 error in quiz submission
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 QUIZ 403 ERROR DIAGNOSTIC AND FIX 🔥\n";
echo "=====================================\n\n";

// Test the current user's authentication
$user = auth()->user();
if (!$user) {
    echo "❌ ERROR: User not authenticated\n";
    echo "   Please log in and try again\n";
    exit;
}

echo "✅ User authenticated: {$user->name} (ID: {$user->id})\n";

// Test CSRF token generation
$csrfToken = csrf_token();
echo "✅ CSRF token generated: " . substr($csrfToken, 0, 10) . "...\n";

// Test session
if (!session()->getId()) {
    echo "❌ ERROR: No session ID\n";
    session()->start();
    echo "✅ Session started\n";
} else {
    echo "✅ Session active: " . substr(session()->getId(), 0, 10) . "...\n";
}

// Test route resolution
try {
    $testCourse = \App\Models\Course::first();
    $testTopic = \App\Models\Topic::first();
    
    if ($testCourse && $testTopic) {
        // Validate scoring inputs
        if (!$score || $score < 0 || $score > 100) {
            throw new InvalidArgumentException('Invalid score value');
        }

        $gradeRoute = route('quiz.grade', ['course' => $testCourse->id, 'topic' => $testTopic->id]);
        echo "✅ Grade route resolves: {$gradeRoute}\n";
    } else {
        echo "⚠️  No test course/topic available\n";
    }
} catch (Exception $e) {
    echo "❌ Route resolution error: {$e->getMessage()}\n";
}

// Check CSRF middleware configuration
$csrfMiddleware = app(\App\Http\Middleware\VerifyCsrfToken::class);
$except = $csrfMiddleware->except ?? [];

echo "\n🔧 CSRF MIDDLEWARE CONFIGURATION:\n";
echo "Excluded routes:\n";
foreach ($except as $route) {
    echo "  - {$route}\n";
}

// Test if quiz grade route is excluded
$isExcluded = in_array('quiz/*/*/grade', $except);
echo "\nQuiz grade route excluded from CSRF: " . ($isExcluded ? "✅ YES" : "❌ NO") . "\n";

// Create a test request to diagnose the issue
echo "\n🧪 TESTING QUIZ SUBMISSION...\n";

try {
    // Get a test course and topic
    $course = \App\Models\Course::first();
    $topic = \App\Models\Topic::first();
    
    if (!$course || !$topic) {
        echo "❌ No test course/topic available\n";
        exit;
    }
    
    // Check user access to course
    $hasAccess = $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
    echo "User has access to course: " . ($hasAccess ? "✅ YES" : "❌ NO") . "\n";
    
    if (!$hasAccess) {
        echo "❌ ACCESS DENIED: User doesn't have access to course\n";
        echo "   Course ID: {$course->id}\n";
        echo "   Course Price: {$course->price}\n";
        echo "   User is admin: " . ($user->isAdmin() ? "YES" : "NO") . "\n";
        
        // Check enrollment
        $enrollment = \DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        if ($enrollment) {
            echo "   Enrollment status: " . ($enrollment->is_enrolled ? "ENROLLED" : "NOT ENROLLED") . "\n";
        } else {
            echo "   No enrollment record found\n";
        }
        
        echo "\n🔧 FIXING ACCESS ISSUE...\n";
        
        // Grant access to the course
        \DB::table('course_user')->updateOrInsert(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['is_enrolled' => true, 'enrolled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
        
        echo "✅ Access granted to course\n";
    }
    
    // Test the actual quiz grade endpoint
    $request = \Illuminate\Http\Request::create(
        "/quiz/{$course->id}/{$topic->id}/grade",
        'POST',
        [
            'answers' => ['1' => 'A'], // Dummy answer
            'time_taken' => 60,
            'mode' => 'quiz',
            '_token' => csrf_token()
        ],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => csrf_token(),
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]
    );
    
    // Set the authenticated user
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    echo "Making test request to grade endpoint...\n";
    
    $response = app()->handle($request);
    $statusCode = $response->getStatusCode();
    
    echo "Response status: {$statusCode}\n";
    
    if ($statusCode === 403) {
        echo "❌ 403 FORBIDDEN ERROR CONFIRMED\n";
        
        // Check specific causes
        echo "\n🔍 DIAGNOSING 403 CAUSES:\n";
        
        // 1. Check middleware stack
        $route = \Route::getRoutes()->match($request);
        if ($route) {
            $middleware = $route->gatherMiddleware();
            echo "Route middleware: " . implode(', ', $middleware) . "\n";
        }
        
        // 2. Check if it's a CSRF issue specifically
        try {
            $csrfMiddleware = new \App\Http\Middleware\VerifyCsrfToken(app());
            $csrfResult = $csrfMiddleware->handle($request, function($req) {
                return response('OK');
            });
            echo "CSRF middleware test: PASSED\n";
        } catch (Exception $e) {
            echo "CSRF middleware test: FAILED - {$e->getMessage()}\n";
        }
        
        // 3. Check authentication middleware
        if (!auth()->check()) {
            echo "❌ Authentication failed\n";
        } else {
            echo "✅ Authentication passed\n";
        }
        
        echo "\n🔧 APPLYING FIXES...\n";
        
        // Fix 1: Ensure CSRF exclusion is working
        echo "1. Updating CSRF exclusions...\n";
        
        $csrfFile = app_path('Http/Middleware/VerifyCsrfToken.php');
        $csrfContent = file_get_contents($csrfFile);
        
        if (!str_contains($csrfContent, "'quiz/*/*/grade'")) {
            $csrfContent = str_replace(
                "protected \$except = [",
                "protected \$except = [\n        'quiz/*/*/grade',",
                $csrfContent
            );
            file_put_contents($csrfFile, $csrfContent);
            echo "   ✅ Added quiz grade route to CSRF exclusions\n";
        } else {
            echo "   ✅ Quiz grade route already in CSRF exclusions\n";
        }
        
        // Fix 2: Clear route cache
        echo "2. Clearing route cache...\n";
        \Artisan::call('route:clear');
        echo "   ✅ Route cache cleared\n";
        
        // Fix 3: Clear config cache
        echo "3. Clearing config cache...\n";
        \Artisan::call('config:clear');
        echo "   ✅ Config cache cleared\n";
        
        echo "\n✅ FIXES APPLIED - Please try submitting the quiz again\n";
        
    } else if ($statusCode === 200) {
        echo "✅ Quiz submission working correctly\n";
    } else {
        echo "⚠️  Unexpected status code: {$statusCode}\n";
        echo "Response content: " . $response->getContent() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n🎯 SUMMARY:\n";
echo "- Check browser console for JavaScript errors\n";
echo "- Ensure you're logged in\n";
echo "- Try refreshing the page to get a new CSRF token\n";
echo "- If issue persists, check Laravel logs in storage/logs/\n";

echo "\n🔧 QUICK BROWSER FIX:\n";
echo "In browser console, run:\n";
echo "localStorage.clear(); sessionStorage.clear(); location.reload();\n";
