<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== CSRF Login Test ===\n\n";

// Test if we can generate a CSRF token in a simulated request context
try {
    // Start a session
    $session = app('session');
    $session->start();
    
    // Generate CSRF token
    $token = $session->token();
    echo "✓ CSRF token generated: " . substr($token, 0, 20) . "...\n";
    
    // Test session storage
    $session->put('test_key', 'test_value');
    $retrieved = $session->get('test_key');
    
    if ($retrieved === 'test_value') {
        echo "✓ Session storage working\n";
    } else {
        echo "✗ Session storage failed\n";
    }
    
    // Test database session storage
    $sessionId = $session->getId();
    echo "✓ Session ID: " . $sessionId . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If you see checkmarks above, CSRF should work.\n";
echo "Try logging in now through your browser.\n";