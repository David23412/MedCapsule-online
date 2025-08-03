<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\Log;

echo "🚀 TESTING BUTTON CLICK FUNCTIONALITY\n";
echo "====================================\n\n";

// Clear the log to see fresh entries
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    // Keep only the last few lines to see new entries
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    file_put_contents($logFile, implode('', $lastLines));
}

echo "✅ Log file cleared for testing\n";
echo "✅ Users available: " . User::count() . "\n";
echo "✅ Courses available: " . Course::count() . "\n\n";

echo "📋 TESTING INSTRUCTIONS:\n";
echo "1. Open browser and go to /manage-access\n";
echo "2. Open browser Developer Tools (F12)\n";
echo "3. Go to Console tab\n";
echo "4. Select a user and course\n";
echo "5. Click 'Grant Access' button\n";
echo "6. Check console for debug messages\n";
echo "7. Check this terminal for log entries\n\n";

echo "🔍 EXPECTED CONSOLE MESSAGES:\n";
echo "- '🚨 GRANT ACCESS BUTTON CLICKED!'\n";
echo "- '🚀 Grant Access - User ID: X, Course ID: Y'\n";
echo "- '🚀 Starting AJAX request...'\n";
echo "- '🚀 AJAX request starting...'\n";
echo "- '✅ AJAX Success: {...}'\n\n";

echo "🔍 EXPECTED LOG ENTRIES:\n";
echo "- '🚀 GRANT ACCESS REQUEST'\n";
echo "- '✅ ENROLLMENT COMPLETED'\n\n";

echo "⏳ Monitoring logs for new entries...\n";
echo "Press Ctrl+C to stop monitoring\n\n";

// Monitor the log file for new entries
$lastSize = filesize($logFile);

while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastSize) {
        // New content added
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastSize);
        $newContent = fread($handle, $currentSize - $lastSize);
        fclose($handle);
        
        echo "📝 NEW LOG ENTRY:\n";
        echo $newContent . "\n";
        
        $lastSize = $currentSize;
    }
    
    sleep(1);
}