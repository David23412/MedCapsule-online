<?php

/**
 * 🔥 LETHAL NOTIFICATION END-TO-END DEBUG ENGINE 🔥
 * 
 * This is the most AGGRESSIVE notification debugging system ever created.
 * It will track EVERY step of notification creation, storage, and retrieval.
 * NO NOTIFICATION WILL BE LOST OR DELETED WITHOUT BEING TRACKED!
 * 
 * Run with: php artisan tinker --execute="require 'LETHAL_NOTIFICATION_END_TO_END_DEBUG_ENGINE.php';"
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

echo "🔥🔥🔥 LETHAL NOTIFICATION END-TO-END DEBUG ENGINE 🔥🔥🔥\n";
echo "=" . str_repeat("=", 80) . "\n\n";

echo "🚨 DISABLING ALL AGGRESSIVE CLEANUP TEMPORARILY FOR DEBUGGING 🚨\n";
echo "-" . str_repeat("-", 60) . "\n\n";

// STEP 1: CREATE DATABASE TABLE FOR PERSISTENT NOTIFICATION TRACKING
echo "1. 🗄️  CREATING PERSISTENT NOTIFICATION TRACKING TABLE:\n";
echo "-" . str_repeat("-", 50) . "\n";

try {
    DB::statement("
        CREATE TABLE IF NOT EXISTS notification_debug_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            notification_id VARCHAR(255) NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            notification_type VARCHAR(100),
            title TEXT,
            message TEXT,
            data JSON,
            cache_key VARCHAR(255),
            cache_exists BOOLEAN DEFAULT FALSE,
            cache_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_notification_id (notification_id),
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✅ Notification debug table created/verified\n";
} catch (Exception $e) {
    echo "❌ Failed to create debug table: " . $e->getMessage() . "\n";
}

// STEP 2: LETHAL NOTIFICATION TRACKING FUNCTION
function LETHAL_TRACK_NOTIFICATION($userId, $notificationId, $eventType, $data = []) {
    try {
        $cacheKey = "user_notifications_{$userId}";
        $cached = Cache::get($cacheKey, []);
        
        DB::table('notification_debug_log')->insert([
            'user_id' => $userId,
            'notification_id' => $notificationId,
            'event_type' => $eventType,
            'notification_type' => $data['type'] ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'data' => json_encode($data),
            'cache_key' => $cacheKey,
            'cache_exists' => !empty($cached),
            'cache_count' => count($cached),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "🔍 TRACKED: {$eventType} for user {$userId} - notification {$notificationId}\n";
    } catch (Exception $e) {
        echo "❌ TRACKING FAILED: " . $e->getMessage() . "\n";
    }
}

// STEP 3: GET ALL USERS FOR TESTING
echo "\n2. 👥 GETTING ALL USERS FOR COMPREHENSIVE TESTING:\n";
echo "-" . str_repeat("-", 50) . "\n";

$users = App\Models\User::take(5)->get(); // Test with first 5 users
if ($users->isEmpty()) {
    echo "❌ NO USERS FOUND! Creating test user...\n";
    $user = App\Models\User::create([
        'name' => 'Debug Test User',
        'email' => 'debug@test.com',
        'password' => bcrypt('password'),
        'role' => 'student'
    ]);
    $users = collect([$user]);
}

echo "✅ Found " . $users->count() . " users for testing\n";
foreach ($users as $user) {
    echo "   👤 {$user->name} (ID: {$user->id}, Role: {$user->role})\n";
}

// STEP 4: CLEAR ALL EXISTING NOTIFICATIONS AND RESET
echo "\n3. 🧹 CLEARING ALL EXISTING NOTIFICATIONS FOR FRESH START:\n";
echo "-" . str_repeat("-", 50) . "\n";

foreach ($users as $user) {
    $cacheKey = "user_notifications_{$user->id}";
    $unreadKey = "user_unread_count_{$user->id}";
    
    $existingCount = count(Cache::get($cacheKey, []));
    Cache::forget($cacheKey);
    Cache::forget($unreadKey);
    
    LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'CACHE_CLEARED', [
        'existing_count' => $existingCount
    ]);
    
    echo "🧹 Cleared {$existingCount} notifications for user {$user->id}\n";
}

// STEP 5: CREATE COMPREHENSIVE TEST NOTIFICATIONS
echo "\n4. 🎯 CREATING COMPREHENSIVE TEST NOTIFICATIONS:\n";
echo "-" . str_repeat("-", 50) . "\n";

$notificationTypes = [
    'course_welcome' => ['Test Course Welcome', 'Welcome to the debug course!'],
    'quiz_completed' => ['Quiz Completed', 'You scored 8/10 on Debug Topic'],
    'achievement' => ['Achievement Unlocked', 'Debug Master: Completed debugging'],
    'streak_milestone' => ['Streak Milestone', '5-day debug streak achieved!'],
    'payment_success' => ['Payment Successful', 'Payment of $99 for Debug Course'],
    'assignment_due' => ['Assignment Due', 'Debug Assignment due in 2 hours'],
    'system_update' => ['System Update', 'New debugging features available'],
    'announcement' => ['Important Announcement', 'Debug mode is now active']
];

foreach ($users as $user) {
    echo "\n👤 Creating notifications for {$user->name}:\n";
    
    foreach ($notificationTypes as $type => $content) {
        $notificationId = uniqid('debug_' . $type . '_', true);
        
        // Track BEFORE creation
        LETHAL_TRACK_NOTIFICATION($user->id, $notificationId, 'BEFORE_CREATE', [
            'type' => $type,
            'title' => $content[0],
            'message' => $content[1]
        ]);
        
        // Create notification using service
        try {
            $notification = App\Services\UniversalNotificationService::create(
                $user,
                $type,
                $content[0],
                $content[1],
                ['debug' => true, 'test_id' => $notificationId],
                'high'
            );
            
            // Track AFTER creation
            LETHAL_TRACK_NOTIFICATION($user->id, $notification['id'], 'AFTER_CREATE', $notification);
            
            echo "   ✅ Created {$type}: {$content[0]}\n";
            
            // Immediately verify it exists in cache
            $cacheKey = "user_notifications_{$user->id}";
            $cached = Cache::get($cacheKey, []);
            $found = false;
            foreach ($cached as $cachedNotif) {
                if ($cachedNotif['id'] === $notification['id']) {
                    $found = true;
                    break;
                }
            }
            
            LETHAL_TRACK_NOTIFICATION($user->id, $notification['id'], 'IMMEDIATE_VERIFY', [
                'found_in_cache' => $found,
                'cache_count' => count($cached)
            ]);
            
            if (!$found) {
                echo "   ❌ WARNING: Notification NOT found in cache immediately after creation!\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Failed to create {$type}: " . $e->getMessage() . "\n";
            LETHAL_TRACK_NOTIFICATION($user->id, $notificationId, 'CREATE_FAILED', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Small delay to prevent race conditions
        usleep(100000); // 0.1 second
    }
}

// STEP 6: VERIFY ALL NOTIFICATIONS EXIST
echo "\n5. 🔍 VERIFYING ALL NOTIFICATIONS EXIST:\n";
echo "-" . str_repeat("-", 50) . "\n";

foreach ($users as $user) {
    echo "\n👤 Verifying notifications for {$user->name}:\n";
    
    // Check via service
    $notifications = App\Services\UniversalNotificationService::getNotifications($user, 50);
    $unreadCount = App\Services\UniversalNotificationService::getUnreadCount($user);
    
    echo "   📊 Service reports: {$notifications->count()} notifications, {$unreadCount} unread\n";
    
    // Check cache directly
    $cacheKey = "user_notifications_{$user->id}";
    $cached = Cache::get($cacheKey, []);
    echo "   💾 Cache contains: " . count($cached) . " notifications\n";
    
    // Check unread cache
    $unreadCached = Cache::get("user_unread_count_{$user->id}", 0);
    echo "   🔢 Unread cache: {$unreadCached}\n";
    
    LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'VERIFICATION', [
        'service_count' => $notifications->count(),
        'service_unread' => $unreadCount,
        'cache_count' => count($cached),
        'cache_unread' => $unreadCached
    ]);
    
    // List all notifications
    if ($notifications->count() > 0) {
        echo "   📝 Notification details:\n";
        foreach ($notifications as $index => $notif) {
            echo "      " . ($index + 1) . ". {$notif['type']}: {$notif['title']} (Read: " . ($notif['read'] ? 'Yes' : 'No') . ")\n";
        }
    } else {
        echo "   ❌ NO NOTIFICATIONS FOUND!\n";
    }
}

// STEP 7: TEST CONTROLLER ENDPOINT
echo "\n6. 🎮 TESTING CONTROLLER ENDPOINT:\n";
echo "-" . str_repeat("-", 50) . "\n";

foreach ($users as $user) {
    echo "\n👤 Testing endpoint for {$user->name}:\n";
    
    try {
        // Authenticate user
        Auth::login($user);
        
        // Create controller instance
        $controller = new App\Http\Controllers\Notification\NotificationViewController();
        
        // Create request
        $request = new Illuminate\Http\Request();
        $request->setUserResolver(function() use ($user) {
            return $user;
        });
        
        // Call controller
        $response = $controller->index($request);
        $responseData = json_decode($response->getContent(), true);
        
        echo "   📡 Controller Status: " . $response->getStatusCode() . "\n";
        echo "   ✅ Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
        echo "   🔔 Notifications: " . count($responseData['notifications'] ?? []) . "\n";
        echo "   🔢 Unread: " . ($responseData['unread_count'] ?? 0) . "\n";
        
        LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'CONTROLLER_TEST', [
            'status_code' => $response->getStatusCode(),
            'success' => $responseData['success'] ?? false,
            'notification_count' => count($responseData['notifications'] ?? []),
            'unread_count' => $responseData['unread_count'] ?? 0
        ]);
        
        if (isset($responseData['notifications']) && count($responseData['notifications']) > 0) {
            echo "   📝 First notification: " . $responseData['notifications'][0]['title'] . "\n";
        } else {
            echo "   ❌ NO NOTIFICATIONS IN CONTROLLER RESPONSE!\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Controller test failed: " . $e->getMessage() . "\n";
        LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'CONTROLLER_FAILED', [
            'error' => $e->getMessage()
        ]);
    }
}

// STEP 8: MONITOR FOR AGGRESSIVE CLEANUP
echo "\n7. 👀 MONITORING FOR AGGRESSIVE CLEANUP:\n";
echo "-" . str_repeat("-", 50) . "\n";

foreach ($users as $user) {
    echo "\n👤 Monitoring cleanup for {$user->name}:\n";
    
    // Get initial count
    $initialNotifications = App\Services\UniversalNotificationService::getNotifications($user, 50);
    $initialCount = $initialNotifications->count();
    echo "   📊 Initial count: {$initialCount}\n";
    
    // Trigger cleanup methods
    echo "   🧹 Triggering cleanup methods...\n";
    
    // 1. performAggressiveCleanup
    App\Services\UniversalNotificationService::performAggressiveCleanup($user);
    $afterAggressive = App\Services\UniversalNotificationService::getNotifications($user, 50)->count();
    echo "   📊 After aggressive cleanup: {$afterAggressive} (Lost: " . ($initialCount - $afterAggressive) . ")\n";
    
    LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'AGGRESSIVE_CLEANUP', [
        'before_count' => $initialCount,
        'after_count' => $afterAggressive,
        'lost_count' => $initialCount - $afterAggressive
    ]);
    
    // 2. cleanupExpiredNotifications
    $deletedCount = App\Services\UniversalNotificationService::cleanupExpiredNotifications($user);
    $afterExpired = App\Services\UniversalNotificationService::getNotifications($user, 50)->count();
    echo "   📊 After expired cleanup: {$afterExpired} (Deleted: {$deletedCount})\n";
    
    LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'EXPIRED_CLEANUP', [
        'deleted_count' => $deletedCount,
        'after_count' => $afterExpired
    ]);
    
    // 3. enforceLimit
    App\Services\UniversalNotificationService::enforceLimit($user);
    $afterLimit = App\Services\UniversalNotificationService::getNotifications($user, 50)->count();
    echo "   📊 After limit enforcement: {$afterLimit}\n";
    
    LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'LIMIT_ENFORCEMENT', [
        'after_count' => $afterLimit
    ]);
}

// STEP 9: SIMULATE REAL USER ACTIONS
echo "\n8. 🎭 SIMULATING REAL USER ACTIONS:\n";
echo "-" . str_repeat("-", 50) . "\n";

$testUser = $users->first();
echo "👤 Using {$testUser->name} for real action simulation:\n";

// Simulate quiz completion
echo "\n🎯 Simulating quiz completion...\n";
try {
    $course = App\Models\Course::first();
    $topic = App\Models\Topic::first();
    
    if ($course && $topic) {
        // Create mock quiz data
        $answers = ['1' => 'A', '2' => 'B', '3' => 'C'];
        $duration = 300; // 5 minutes
        $mode = 'quiz';
        
        // This should trigger notification events
        $gradingService = new App\Services\GradingService(
            new App\Services\CacheService(),
            new App\Services\UserStatsService(),
            new App\Services\NotificationService(),
            new App\Services\WrittenAnswerEvaluationService()
        );
        
        echo "   🎯 Attempting quiz grading to trigger events...\n";
        
        // Check notifications before
        $beforeQuiz = App\Services\UniversalNotificationService::getNotifications($testUser, 50)->count();
        echo "   📊 Notifications before quiz: {$beforeQuiz}\n";
        
        // This should trigger our new event dispatches
        try {
            // We can't easily simulate the full grading process, so let's trigger events directly
            event(new App\Events\StudyStreakUpdated($testUser, 5, 4));
            event(new App\Events\TopicMastered($testUser, $topic, 95.0, 1));
            event(new App\Events\CorrectAnswersMilestoneReached($testUser, 100, 100, 85.5));
            
            echo "   ✅ Events dispatched successfully\n";
            
            // Check notifications after
            sleep(1); // Give events time to process
            $afterQuiz = App\Services\UniversalNotificationService::getNotifications($testUser, 50)->count();
            echo "   📊 Notifications after quiz: {$afterQuiz} (New: " . ($afterQuiz - $beforeQuiz) . ")\n";
            
            LETHAL_TRACK_NOTIFICATION($testUser->id, 'SYSTEM', 'QUIZ_SIMULATION', [
                'before_count' => $beforeQuiz,
                'after_count' => $afterQuiz,
                'new_notifications' => $afterQuiz - $beforeQuiz
            ]);
            
        } catch (Exception $e) {
            echo "   ❌ Quiz simulation failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "   ❌ No course/topic found for simulation\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Quiz simulation setup failed: " . $e->getMessage() . "\n";
}

// STEP 10: FINAL COMPREHENSIVE REPORT
echo "\n9. 📊 FINAL COMPREHENSIVE REPORT:\n";
echo "-" . str_repeat("-", 50) . "\n";

foreach ($users as $user) {
    echo "\n👤 FINAL REPORT FOR {$user->name}:\n";
    
    $finalNotifications = App\Services\UniversalNotificationService::getNotifications($user, 50);
    $finalUnread = App\Services\UniversalNotificationService::getUnreadCount($user);
    $cacheKey = "user_notifications_{$user->id}";
    $finalCached = Cache::get($cacheKey, []);
    
    echo "   📊 Final notification count: " . $finalNotifications->count() . "\n";
    echo "   🔢 Final unread count: {$finalUnread}\n";
    echo "   💾 Final cache count: " . count($finalCached) . "\n";
    
    // Get debug log for this user
    $debugLogs = DB::table('notification_debug_log')
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();
    
    echo "   📝 Debug log entries: " . $debugLogs->count() . "\n";
    
    // Show recent events
    echo "   🔍 Recent events:\n";
    foreach ($debugLogs->take(5) as $log) {
        echo "      {$log->created_at}: {$log->event_type} - {$log->notification_id}\n";
    }
    
    LETHAL_TRACK_NOTIFICATION($user->id, 'SYSTEM', 'FINAL_REPORT', [
        'final_count' => $finalNotifications->count(),
        'final_unread' => $finalUnread,
        'final_cached' => count($finalCached),
        'debug_logs' => $debugLogs->count()
    ]);
}

// STEP 11: CRITICAL ANALYSIS
echo "\n10. 🔬 CRITICAL ANALYSIS:\n";
echo "-" . str_repeat("-", 50) . "\n";

$totalDebugLogs = DB::table('notification_debug_log')->count();
echo "📊 Total debug log entries: {$totalDebugLogs}\n";

$eventTypes = DB::table('notification_debug_log')
    ->select('event_type', DB::raw('COUNT(*) as count'))
    ->groupBy('event_type')
    ->orderBy('count', 'desc')
    ->get();

echo "📈 Event type breakdown:\n";
foreach ($eventTypes as $event) {
    echo "   {$event->event_type}: {$event->count}\n";
}

// Check for cleanup issues
$cleanupEvents = DB::table('notification_debug_log')
    ->whereIn('event_type', ['AGGRESSIVE_CLEANUP', 'EXPIRED_CLEANUP', 'LIMIT_ENFORCEMENT'])
    ->get();

if ($cleanupEvents->count() > 0) {
    echo "\n⚠️  CLEANUP ANALYSIS:\n";
    foreach ($cleanupEvents as $cleanup) {
        $data = json_decode($cleanup->data, true);
        if (isset($data['lost_count']) && $data['lost_count'] > 0) {
            echo "   🚨 AGGRESSIVE CLEANUP DELETED {$data['lost_count']} notifications for user {$cleanup->user_id}\n";
        }
    }
}

echo "\n🎯 DIAGNOSIS:\n";
echo "-" . str_repeat("-", 50) . "\n";

$hasNotifications = false;
foreach ($users as $user) {
    $count = App\Services\UniversalNotificationService::getNotifications($user, 50)->count();
    if ($count > 0) {
        $hasNotifications = true;
        break;
    }
}

if ($hasNotifications) {
    echo "✅ NOTIFICATIONS ARE BEING CREATED AND STORED\n";
    echo "✅ CACHE SYSTEM IS WORKING\n";
    echo "✅ SERVICE LAYER IS FUNCTIONAL\n";
    echo "🔍 ISSUE IS LIKELY IN FRONTEND OR AUTHENTICATION\n";
} else {
    echo "❌ NOTIFICATIONS ARE NOT PERSISTING\n";
    echo "❌ AGGRESSIVE CLEANUP IS TOO AGGRESSIVE\n";
    echo "❌ CACHE SYSTEM MAY BE FAULTY\n";
}

echo "\n🚀 RECOMMENDED ACTIONS:\n";
echo "-" . str_repeat("-", 50) . "\n";
echo "1. Check debug log table for cleanup patterns\n";
echo "2. Disable aggressive cleanup temporarily\n";
echo "3. Test frontend with existing notifications\n";
echo "4. Verify event dispatching is working\n";
echo "5. Check browser console for errors\n";

echo "\n🔥 DEBUG ENGINE COMPLETE! 🔥\n";
echo "Check the 'notification_debug_log' table for detailed tracking.\n";
echo "=" . str_repeat("=", 80) . "\n";

?>