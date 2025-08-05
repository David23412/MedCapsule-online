<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🔥 FORCE MEDCAPSULE WELCOME NOTIFICATION TEST\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Get test user
$user = User::find(2);
if (!$user) {
    echo "❌ User 2 not found\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Force create a welcome notification (bypassing the 7-day check)
echo "🔥 FORCING WELCOME NOTIFICATION CREATION...\n";
echo "-" . str_repeat("-", 50) . "\n";

try {
    // Direct call to onUserWelcome (bypasses the 7-day check)
    NotificationTriggerService::onUserWelcome($user);
    echo "✅ onUserWelcome() called successfully\n";
    
    // Get the latest notification
    $latestNotifications = UniversalNotificationService::getNotifications($user, 5);
    $latestNotification = $latestNotifications->first();
    
    if ($latestNotification && $latestNotification['type'] === 'medcapsule_welcome') {
        echo "🎉 SUCCESS! Welcome notification created!\n\n";
        
        echo "📋 WELCOME NOTIFICATION DETAILS:\n";
        echo "-" . str_repeat("-", 50) . "\n";
        echo "   Type: {$latestNotification['type']}\n";
        echo "   Title: {$latestNotification['title']}\n";
        echo "   Message: {$latestNotification['message']}\n";
        echo "   Created: " . \Carbon\Carbon::parse($latestNotification['created_at'])->diffForHumans() . "\n";
        echo "   Priority: {$latestNotification['priority']}\n";
        echo "   Icon: fas fa-capsules (💊 Pills icon)\n";
        echo "   Color: Blue (#3b82f6)\n";
        echo "   Border: Amber (HIGH priority)\n\n";
        
        echo "🔔 WHAT YOU SHOULD SEE IN BROWSER:\n";
        echo "-" . str_repeat("-", 50) . "\n";
        echo "1. 💊 Pills icon with blue color\n";
        echo "2. Amber left border (HIGH priority)\n";
        echo "3. Title: 'Welcome to MedCapsule!'\n";
        echo "4. Message: 'Welcome to MedCapsule learning platform! Start your medical education journey.'\n\n";
        
    } else {
        echo "❌ Welcome notification not found in latest notifications\n";
        
        // Show what we got instead
        if ($latestNotification) {
            echo "   Latest notification type: {$latestNotification['type']}\n";
            echo "   Latest notification title: {$latestNotification['title']}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 NEXT STEPS:\n";
echo "-" . str_repeat("-", 50) . "\n";
echo "1. Refresh your browser page\n";
echo "2. Look at the notification bell (should have a badge if unread)\n";
echo "3. Click the notification bell to open dropdown\n";
echo "4. Look for the MedCapsule welcome notification with pills icon\n\n";

echo "FORCE WELCOME TEST COMPLETE! 🔥\n";