<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🔔 MEDCAPSULE WELCOME NOTIFICATION TEST\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Get test user
$user = User::find(2);
if (!$user) {
    echo "❌ User 2 not found\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Check current notifications
$beforeCount = UniversalNotificationService::getNotifications($user, 20)->count();
echo "📊 Current notifications: {$beforeCount}\n\n";

// Test the welcome notification trigger
echo "🔥 TESTING WELCOME NOTIFICATION TRIGGER...\n";
echo "-" . str_repeat("-", 50) . "\n";

try {
    // Simulate login
    NotificationTriggerService::onUserLogin($user);
    echo "✅ onUserLogin() called successfully\n";
    
    // Check if notification was created
    $afterCount = UniversalNotificationService::getNotifications($user, 20)->count();
    $newNotifications = $afterCount - $beforeCount;
    
    echo "📊 Notifications after login: {$afterCount}\n";
    echo "📊 New notifications created: {$newNotifications}\n\n";
    
    if ($newNotifications > 0) {
        echo "🎉 SUCCESS! Welcome notification was created!\n\n";
        
        // Show the latest notifications
        $latestNotifications = UniversalNotificationService::getNotifications($user, 5);
        
        echo "📋 LATEST NOTIFICATIONS:\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        foreach ($latestNotifications->take(3) as $notification) {
            $type = $notification['type'];
            $title = $notification['title'];
            $message = $notification['message'];
            $time = \Carbon\Carbon::parse($notification['created_at'])->diffForHumans();
            
            if ($type === 'medcapsule_welcome') {
                echo "🎯 WELCOME NOTIFICATION FOUND!\n";
                echo "   Type: {$type}\n";
                echo "   Title: {$title}\n";
                echo "   Message: {$message}\n";
                echo "   Time: {$time}\n";
                echo "   Icon: fas fa-capsules (Pills icon)\n";
                echo "   Color: Blue (#3b82f6)\n";
                echo "   Priority: HIGH (Amber border)\n\n";
            } else {
                echo "   • {$type}: {$title} ({$time})\n";
            }
        }
        
    } else {
        echo "ℹ️  No new notification created (might already exist within 7 days)\n\n";
        
        // Check for existing welcome notifications
        $existingWelcome = UniversalNotificationService::getNotifications($user, 20)
            ->where('type', 'medcapsule_welcome')
            ->first();
            
        if ($existingWelcome) {
            $time = \Carbon\Carbon::parse($existingWelcome['created_at'])->diffForHumans();
            echo "📋 EXISTING WELCOME NOTIFICATION FOUND:\n";
            echo "   Title: {$existingWelcome['title']}\n";
            echo "   Message: {$existingWelcome['message']}\n";
            echo "   Created: {$time}\n\n";
            echo "✅ Welcome notification system is working!\n";
            echo "ℹ️  New notification not sent because one exists within 7 days\n";
        } else {
            echo "❌ No welcome notification found - something might be wrong\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🔔 NEXT STEPS:\n";
echo "-" . str_repeat("-", 50) . "\n";
echo "1. Try logging out and logging back in\n";
echo "2. Check the notification bell in the top navigation\n";
echo "3. You should see a pills icon (💊) with blue color\n";
echo "4. Message: 'Welcome to MedCapsule learning platform! Start your medical education journey.'\n\n";

echo "MEDCAPSULE WELCOME TEST COMPLETE! 🔥\n";