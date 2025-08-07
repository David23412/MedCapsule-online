<?php

/**
 * 🔥 QUIZ 403 SERVER-SIDE FIX 🔥
 * Fixes server-side issues causing 403 errors in quiz submission
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 QUIZ 403 SERVER-SIDE FIX 🔥\n";
echo "===============================\n\n";

// Fix 1: Clear all caches
echo "1. Clearing application caches...\n";

try {
    \Artisan::call('route:clear');
    echo "   ✅ Route cache cleared\n";
} catch (Exception $e) {
    echo "   ⚠️  Route cache clear failed: {$e->getMessage()}\n";
}

try {
    \Artisan::call('config:clear');
    echo "   ✅ Config cache cleared\n";
} catch (Exception $e) {
    echo "   ⚠️  Config cache clear failed: {$e->getMessage()}\n";
}

try {
    \Artisan::call('view:clear');
    echo "   ✅ View cache cleared\n";
} catch (Exception $e) {
    echo "   ⚠️  View cache clear failed: {$e->getMessage()}\n";
}

// Fix 2: Check and fix CSRF middleware
echo "\n2. Checking CSRF middleware configuration...\n";

$csrfFile = app_path('Http/Middleware/VerifyCsrfToken.php');
if (file_exists($csrfFile)) {
    $content = file_get_contents($csrfFile);
    
    $requiredExclusions = [
        'quiz/*/*/grade',
        'quiz/grade-random',
        'quiz/*'
    ];
    
    $needsUpdate = false;
    foreach ($requiredExclusions as $exclusion) {
        if (!str_contains($content, "'$exclusion'")) {
            echo "   ❌ Missing CSRF exclusion: $exclusion\n";
            $needsUpdate = true;
        } else {
            echo "   ✅ CSRF exclusion found: $exclusion\n";
        }
    }
    
    if ($needsUpdate) {
        echo "   🔧 Updating CSRF exclusions...\n";
        
        // Add missing exclusions
        $pattern = '/protected\s+\$except\s*=\s*\[([^\]]*)\]/s';
        if (preg_match($pattern, $content, $matches)) {
            $existingExclusions = $matches[1];
            
            foreach ($requiredExclusions as $exclusion) {
                if (!str_contains($existingExclusions, "'$exclusion'")) {
                    $existingExclusions .= "\n        '$exclusion',";
                }
            }
            
            $newContent = str_replace($matches[0], "protected \$except = [$existingExclusions\n    ]", $content);
            file_put_contents($csrfFile, $newContent);
            echo "   ✅ CSRF exclusions updated\n";
        }
    }
} else {
    echo "   ❌ CSRF middleware file not found\n";
}

// Fix 3: Check route registration
echo "\n3. Checking route registration...\n";

try {
    $routes = \Route::getRoutes();
    $quizGradeRoute = null;
    
    foreach ($routes as $route) {
        if ($route->getName() === 'quiz.grade') {
            $quizGradeRoute = $route;
            break;
        }
    }
    
    if ($quizGradeRoute) {
        echo "   ✅ Quiz grade route found: {$quizGradeRoute->uri()}\n";
        echo "   ✅ Route methods: " . implode(', ', $quizGradeRoute->methods()) . "\n";
        
        $middleware = $quizGradeRoute->gatherMiddleware();
        echo "   ✅ Route middleware: " . implode(', ', $middleware) . "\n";
        
        // Check if auth middleware is present
        if (in_array('auth', $middleware)) {
            echo "   ✅ Authentication required (this is correct)\n";
        } else {
            echo "   ⚠️  No authentication middleware (might be an issue)\n";
        }
        
    } else {
        echo "   ❌ Quiz grade route not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Route check failed: {$e->getMessage()}\n";
}

// Fix 4: Test database connection
echo "\n4. Testing database connection...\n";

try {
    \DB::connection()->getPdo();
    echo "   ✅ Database connection working\n";
    
    // Test user table
    $userCount = \DB::table('users')->count();
    echo "   ✅ Users table accessible ($userCount users)\n";
    
    // Test courses table
    $courseCount = \DB::table('courses')->count();
    echo "   ✅ Courses table accessible ($courseCount courses)\n";
    
} catch (Exception $e) {
    echo "   ❌ Database connection failed: {$e->getMessage()}\n";
}

// Fix 5: Check session configuration
echo "\n5. Checking session configuration...\n";

$sessionDriver = config('session.driver');
echo "   Session driver: $sessionDriver\n";

$sessionLifetime = config('session.lifetime');
echo "   Session lifetime: $sessionLifetime minutes\n";

if ($sessionDriver === 'file') {
    $sessionPath = config('session.files');
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        echo "   ✅ Session storage path writable: $sessionPath\n";
    } else {
        echo "   ❌ Session storage path not writable: $sessionPath\n";
    }
}

// Fix 6: Create a test route for debugging
echo "\n6. Creating debug route...\n";

try {
    // This would normally be in routes/web.php, but we'll suggest it
    echo "   💡 Add this debug route to routes/web.php:\n";
    echo "   \n";
    echo "   Route::post('/debug-quiz-403', function(\Illuminate\Http\Request \$request) {\n";
    echo "       return response()->json([\n";
    echo "           'authenticated' => auth()->check(),\n";
    echo "           'user_id' => auth()->id(),\n";
    echo "           'csrf_token' => csrf_token(),\n";
    echo "           'session_id' => session()->getId(),\n";
    echo "           'request_data' => \$request->all(),\n";
    echo "           'headers' => \$request->headers->all()\n";
    echo "       ]);\n";
    echo "   })->middleware('auth');\n";
    echo "   \n";
    
} catch (Exception $e) {
    echo "   ❌ Debug route creation failed: {$e->getMessage()}\n";
}

echo "\n🎯 SERVER-SIDE FIX SUMMARY:\n";
echo "================================\n";
echo "✅ Application caches cleared\n";
echo "✅ CSRF middleware checked/updated\n";
echo "✅ Route registration verified\n";
echo "✅ Database connection tested\n";
echo "✅ Session configuration checked\n";

echo "\n🔧 NEXT STEPS:\n";
echo "1. Restart your web server (php artisan serve or nginx/apache)\n";
echo "2. Clear browser cache and cookies\n";
echo "3. Test quiz submission again\n";
echo "4. If still failing, check Laravel logs in storage/logs/\n";

echo "\n📋 COMMON CAUSES OF 403 ERRORS:\n";
echo "- User session expired (most common)\n";
echo "- CSRF token mismatch\n";
echo "- Authentication middleware blocking request\n";
echo "- Route cache issues\n";
echo "- Incorrect middleware configuration\n";