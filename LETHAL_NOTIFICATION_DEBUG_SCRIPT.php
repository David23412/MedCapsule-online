<?php

/**
 * LETHAL NOTIFICATION DEBUG SCRIPT
 * 
 * This script debugs the notification loading issue
 */

echo "🔥 LETHAL NOTIFICATION DEBUG SCRIPT\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Test 1: Check if notifications exist
echo "1. 🔍 CHECKING NOTIFICATION EXISTENCE:\n";
echo "-" . str_repeat("-", 40) . "\n";

$user = App\Models\User::first();
if (!$user) {
    echo "❌ No users found in database!\n";
    exit(1);
}

echo "✅ User found: {$user->name} (ID: {$user->id})\n";

// Check cached notifications
$cacheKey = "user_notifications_{$user->id}";
$cached = Cache::get($cacheKey, []);
echo "📦 Cached notifications: " . count($cached) . "\n";

// Get notifications via service
$notifications = App\Services\UniversalNotificationService::getNotifications($user, 10);
echo "🔔 Service notifications: " . $notifications->count() . "\n";

$unreadCount = App\Services\UniversalNotificationService::getUnreadCount($user);
echo "🔢 Unread count: {$unreadCount}\n";

if ($notifications->count() === 0) {
    echo "\n⚠️  NO NOTIFICATIONS FOUND - Creating test notifications...\n";
    
    // Create test notifications
    App\Services\UniversalNotificationService::createCourseWelcomeNotification($user, 'Debug Course');
    App\Services\UniversalNotificationService::createQuizCompletionNotification($user, 'Debug Topic', 9, 10, 90.0);
    App\Services\UniversalNotificationService::createAchievementNotification($user, 'Debug Achievement', 'Test achievement');
    
    echo "✅ Test notifications created!\n";
    
    // Re-check
    $notifications = App\Services\UniversalNotificationService::getNotifications($user, 10);
    echo "🔔 Notifications after creation: " . $notifications->count() . "\n";
}

// Test 2: Check notification controller directly
echo "\n2. 🎯 TESTING NOTIFICATION CONTROLLER:\n";
echo "-" . str_repeat("-", 40) . "\n";

try {
    // Simulate authenticated request
    Auth::login($user);
    
    $controller = new App\Http\Controllers\Notification\NotificationViewController();
    $request = new Illuminate\Http\Request();
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $response = $controller->index($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "✅ Controller response status: " . $response->getStatusCode() . "\n";
    echo "📊 Response success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
    echo "🔔 Response notifications: " . count($responseData['notifications'] ?? []) . "\n";
    echo "🔢 Response unread count: " . ($responseData['unread_count'] ?? 0) . "\n";
    
    if (isset($responseData['notifications']) && count($responseData['notifications']) > 0) {
        echo "📝 First notification: " . $responseData['notifications'][0]['title'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . "\n";
}

// Test 3: Check routes
echo "\n3. 🛣️  CHECKING ROUTES:\n";
echo "-" . str_repeat("-", 40) . "\n";

$routes = Route::getRoutes();
$notificationRoutes = [];

foreach ($routes as $route) {
    if (strpos($route->uri(), 'notification') !== false) {
        $notificationRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName()
        ];
    }
}

if (empty($notificationRoutes)) {
    echo "❌ No notification routes found!\n";
} else {
    echo "✅ Notification routes found:\n";
    foreach ($notificationRoutes as $route) {
        echo "   {$route['method']} /{$route['uri']} -> {$route['action']}\n";
    }
}

// Test 4: Check middleware
echo "\n4. 🛡️  CHECKING MIDDLEWARE:\n";
echo "-" . str_repeat("-", 40) . "\n";

$route = Route::getRoutes()->getByName('notifications.index');
if ($route) {
    $middleware = $route->gatherMiddleware();
    echo "✅ Route middleware: " . implode(', ', $middleware) . "\n";
} else {
    echo "❌ notifications.index route not found!\n";
}

// Test 5: Check authentication
echo "\n5. 🔐 CHECKING AUTHENTICATION:\n";
echo "-" . str_repeat("-", 40) . "\n";

echo "✅ Auth user: " . (Auth::check() ? Auth::user()->name : 'Not authenticated') . "\n";
echo "✅ Session driver: " . config('session.driver') . "\n";
echo "✅ Auth guard: " . config('auth.defaults.guard') . "\n";

// Test 6: Manual API test
echo "\n6. 🌐 MANUAL API TEST:\n";
echo "-" . str_repeat("-", 40) . "\n";

try {
    // Create a proper request with authentication
    $request = Request::create('/notifications', 'GET');
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    // Set up session for the request
    $session = app('session.store');
    $session->put('_token', csrf_token());
    $request->setLaravelSession($session);
    
    Auth::login($user);
    
    $response = app()->handle($request);
    echo "✅ Manual API status: " . $response->getStatusCode() . "\n";
    
    $content = $response->getContent();
    if ($response->getStatusCode() === 200) {
        $data = json_decode($content, true);
        echo "📊 API success: " . ($data['success'] ?? 'unknown') . "\n";
        echo "🔔 API notifications: " . count($data['notifications'] ?? []) . "\n";
    } else {
        echo "❌ API error: " . $content . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Manual API test failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 DIAGNOSIS:\n";
echo "-" . str_repeat("-", 40) . "\n";

if ($notifications->count() > 0) {
    echo "✅ Notifications exist in cache\n";
    echo "✅ UniversalNotificationService is working\n";
    echo "🔍 Issue is likely in frontend JavaScript or authentication\n";
    echo "💡 Check browser console for JavaScript errors\n";
    echo "💡 Check if CSRF token is being sent correctly\n";
    echo "💡 Check if user is properly authenticated in browser\n";
} else {
    echo "❌ No notifications found\n";
    echo "🔍 Issue is in notification creation or storage\n";
    echo "💡 Events may not be firing to create notifications\n";
    echo "💡 Check if quiz completion creates notifications\n";
}

echo "\n🚀 RECOMMENDED ACTIONS:\n";
echo "-" . str_repeat("-", 40) . "\n";
echo "1. Complete a quiz to trigger notification events\n";
echo "2. Check browser console for JavaScript errors\n";
echo "3. Verify CSRF token in browser developer tools\n";
echo "4. Test notification endpoint directly in browser\n";
echo "5. Check Laravel logs for any errors\n";

echo "\n" . str_repeat("=", 60) . "\n";

?>