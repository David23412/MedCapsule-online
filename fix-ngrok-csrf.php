<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "=== Fixing ngrok CSRF Issues ===\n\n";

// 1. Clear all caches
echo "1. Clearing all caches...\n";
Artisan::call('config:clear');
Artisan::call('cache:clear');
Artisan::call('route:clear');
Artisan::call('view:clear');
echo "   ✓ Caches cleared\n";

// 2. Clear sessions
echo "2. Clearing sessions...\n";
try {
    DB::table('sessions')->truncate();
    echo "   ✓ Sessions cleared\n";
} catch (Exception $e) {
    echo "   ⚠ Sessions table not found or error: " . $e->getMessage() . "\n";
}

// 3. Regenerate application key if needed
echo "3. Checking application key...\n";
$appKey = env('APP_KEY');
if (empty($appKey)) {
    Artisan::call('key:generate');
    echo "   ✓ Application key generated\n";
} else {
    echo "   ✓ Application key exists\n";
}

// 4. Test database connection
echo "4. Testing database connection...\n";
try {
    DB::connection()->getPdo();
    echo "   ✓ Database connection successful\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// 5. Create sessions table if it doesn't exist
echo "5. Checking sessions table...\n";
try {
    $sessionTableExists = DB::getSchemaBuilder()->hasTable('sessions');
    if (!$sessionTableExists) {
        Artisan::call('session:table');
        Artisan::call('migrate', ['--path' => 'database/migrations', '--force' => true]);
        echo "   ✓ Sessions table created\n";
    } else {
        echo "   ✓ Sessions table exists\n";
    }
} catch (Exception $e) {
    echo "   ⚠ Sessions table check failed: " . $e->getMessage() . "\n";
}

// 6. Test CSRF token generation
echo "6. Testing CSRF token generation...\n";
try {
    $token = csrf_token();
    if (!empty($token)) {
        echo "   ✓ CSRF token generated: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "   ✗ CSRF token generation failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ CSRF token error: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Complete ===\n";
echo "Please restart your Laravel server and try again.\n";
echo "If issues persist, check your ngrok URL in .env file.\n";