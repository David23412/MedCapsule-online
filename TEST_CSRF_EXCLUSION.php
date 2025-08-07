<?php

/**
 * 🔥 TEST CSRF EXCLUSION 🔥
 * Test if the quiz route is properly excluded from CSRF verification
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 TESTING CSRF EXCLUSION 🔥\n";
echo "=============================\n\n";

// Create a test request
$request = \Illuminate\Http\Request::create('/quiz/1/1/grade', 'POST', [
    'answers' => ['1' => 'A'],
    'time_taken' => 60,
    '_token' => 'test-token'
]);

// Set ngrok host to match your setup
$request->headers->set('Host', 'd781485850d4.ngrok-free.app');
$request->headers->set('X-CSRF-TOKEN', 'test-token');

echo "Test request details:\n";
echo "- Path: {$request->path()}\n";
echo "- Method: {$request->method()}\n";
echo "- Host: {$request->getHost()}\n";
echo "- Has CSRF header: " . ($request->hasHeader('X-CSRF-TOKEN') ? 'YES' : 'NO') . "\n";
echo "- Has CSRF input: " . ($request->has('_token') ? 'YES' : 'NO') . "\n";

// Test CSRF middleware
$csrfMiddleware = app(\App\Http\Middleware\VerifyCsrfToken::class);

// Use reflection to access the protected inExceptArray method
$reflection = new ReflectionClass($csrfMiddleware);
$inExceptArrayMethod = $reflection->getMethod('inExceptArray');
$inExceptArrayMethod->setAccessible(true);

$isExcluded = $inExceptArrayMethod->invoke($csrfMiddleware, $request);

echo "\nCSRF Exclusion Test:\n";
echo "- Route should be excluded: " . ($isExcluded ? 'YES ✅' : 'NO ❌') . "\n";

if (!$isExcluded) {
    echo "\n❌ PROBLEM: Route is not being excluded!\n";
    echo "Checking exclusion patterns:\n";
    
    $exceptProperty = $reflection->getProperty('except');
    $exceptProperty->setAccessible(true);
    $except = $exceptProperty->getValue($csrfMiddleware);
    
    foreach ($except as $pattern) {
        if (str_contains($pattern, 'quiz')) {
            echo "- Pattern: '$pattern'\n";
            
            // Test if pattern matches
            $matches = fnmatch($pattern, $request->path());
            echo "  Matches '{$request->path()}': " . ($matches ? 'YES' : 'NO') . "\n";
        }
    }
} else {
    echo "✅ Route is properly excluded from CSRF verification\n";
}

echo "\n🔧 If route is not excluded, the patterns might not be matching correctly.\n";
echo "Current request path: '{$request->path()}'\n";
echo "Expected to match: 'quiz/*/*/grade' or 'quiz/*/grade'\n";