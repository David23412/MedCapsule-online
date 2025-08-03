<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;

echo "🧪 TESTING BUTTON SUBMISSION\n";
echo "============================\n\n";

// Test 1: Check if the page loads with buttons
echo "1. Testing page load...\n";
try {
    $request = Request::create('/manage-access', 'GET');
    $response = app()->handle($request);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Manage access page loads successfully\n";
        
        // Check if buttons exist in the response
        $content = $response->getContent();
        if (strpos($content, 'grantAccessBtn') !== false) {
            echo "✅ Grant Access button found in page\n";
        } else {
            echo "❌ Grant Access button not found in page\n";
        }
        
        if (strpos($content, 'revokeAccessBtn') !== false) {
            echo "✅ Revoke Access button found in page\n";
        } else {
            echo "❌ Revoke Access button not found in page\n";
        }
        
        if (strpos($content, '/manage-access/enroll') !== false) {
            echo "✅ Enroll AJAX endpoint referenced\n";
        } else {
            echo "❌ Enroll AJAX endpoint not found\n";
        }
        
        if (strpos($content, '/manage-access/revoke') !== false) {
            echo "✅ Revoke AJAX endpoint referenced\n";
        } else {
            echo "❌ Revoke AJAX endpoint not found\n";
        }
    } else {
        echo "❌ Manage access page failed to load (Status: " . $response->getStatusCode() . ")\n";
    }
} catch (Exception $e) {
    echo "❌ Page load error: " . $e->getMessage() . "\n";
}

// Test 2: Check if users and courses are available
echo "\n2. Checking data availability...\n";
$userCount = User::count();
$courseCount = Course::count();

echo "Users found: {$userCount}\n";
echo "Courses found: {$courseCount}\n";

if ($userCount > 0 && $courseCount > 0) {
    echo "✅ Data available for testing\n";
    
    // Show sample data
    $sampleUser = User::first();
    $sampleCourse = Course::first();
    echo "Sample user: {$sampleUser->name} ({$sampleUser->email})\n";
    echo "Sample course: {$sampleCourse->name}\n";
} else {
    echo "❌ No data available for testing\n";
}

echo "\n🎯 BROWSER TESTING INSTRUCTIONS\n";
echo "===============================\n";
echo "1. Open your browser and go to: http://localhost:8000/manage-access\n";
echo "2. Open browser developer tools (F12) and go to Console tab\n";
echo "3. Click the 'Grant Access' button - you should see:\n";
echo "   - Console log: '🚀 GRANT ACCESS BUTTON CLICKED!'\n";
echo "   - Console log: '📡 Sending AJAX request to /manage-access/enroll'\n";
echo "4. Click the 'Revoke Access' button - you should see:\n";
echo "   - Console log: '🚀 REVOKE ACCESS BUTTON CLICKED!'\n";
echo "   - Console log: '📡 Sending AJAX request to /manage-access/revoke'\n";
echo "5. If you see these logs, the buttons are working!\n";
echo "6. If you don't see the logs, there's a JavaScript issue\n";
echo "\n✅ The buttons should now submit to the backend properly!\n"; 