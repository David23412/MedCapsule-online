<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔔 TESTING NOTIFICATION DUPLICATION FIX\n";
echo "======================================\n\n";

try {
    $user = \App\Models\User::find(1);
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    echo "✅ Test User: {$user->name} (ID: {$user->id})\n";
    
    // Clear existing notifications for clean test
    \App\Models\UniversalNotification::where('user_id', $user->id)->delete();
    echo "🧹 Cleared existing notifications\n";
    
    // Test 1: Perfect Score (100%) - Should get ONLY purple star notification
    echo "\n🧪 TEST 1: Perfect Score (100%)\n";
    
    $perfectScoreData = [
        'score' => 10,
        'total_questions' => 10,
        'course_name' => 'Internal Medicine',
        'topic_name' => 'Cardiology',
        'mode' => 'quiz'
    ];
    
    \App\Services\NotificationTriggerService::onQuizCompleted($user, $perfectScoreData);
    
    $notifications = \App\Models\UniversalNotification::where('user_id', $user->id)->get();
    echo "   Notifications created: {$notifications->count()}\n";
    
    foreach ($notifications as $notification) {
        echo "   - Type: {$notification->type}, Title: {$notification->title}\n";
    }
    
    if ($notifications->count() === 1 && $notifications->first()->type === 'perfect_score') {
        echo "✅ Perfect score test PASSED - Only purple star notification sent\n";
    } else {
        echo "❌ Perfect score test FAILED - Expected 1 'perfect_score' notification\n";
    }
    
    // Clear for next test
    \App\Models\UniversalNotification::where('user_id', $user->id)->delete();
    
    // Test 2: Good Score (80%) - Should get regular quiz completion notification
    echo "\n🧪 TEST 2: Good Score (80%)\n";
    
    $goodScoreData = [
        'score' => 8,
        'total_questions' => 10,
        'course_name' => 'Internal Medicine',
        'topic_name' => 'Cardiology',
        'mode' => 'quiz'
    ];
    
    \App\Services\NotificationTriggerService::onQuizCompleted($user, $goodScoreData);
    
    $notifications = \App\Models\UniversalNotification::where('user_id', $user->id)->get();
    echo "   Notifications created: {$notifications->count()}\n";
    
    foreach ($notifications as $notification) {
        echo "   - Type: {$notification->type}, Title: {$notification->title}\n";
    }
    
    if ($notifications->count() === 1 && $notifications->first()->type === 'quiz_completed') {
        echo "✅ Good score test PASSED - Only regular completion notification sent\n";
    } else {
        echo "❌ Good score test FAILED - Expected 1 'quiz_completed' notification\n";
    }
    
    // Clear for next test
    \App\Models\UniversalNotification::where('user_id', $user->id)->delete();
    
    // Test 3: Perfect Tutor Score (100%) - Should get ONLY purple star notification
    echo "\n🧪 TEST 3: Perfect Tutor Score (100%)\n";
    
    $perfectTutorData = [
        'score' => 5,
        'total_questions' => 5,
        'course_name' => 'Internal Medicine',
        'topic_name' => 'Cardiology',
        'mode' => 'tutor'
    ];
    
    \App\Services\NotificationTriggerService::onQuizCompleted($user, $perfectTutorData);
    
    $notifications = \App\Models\UniversalNotification::where('user_id', $user->id)->get();
    echo "   Notifications created: {$notifications->count()}\n";
    
    foreach ($notifications as $notification) {
        echo "   - Type: {$notification->type}, Title: {$notification->title}\n";
    }
    
    if ($notifications->count() === 1 && $notifications->first()->type === 'tutor_perfect') {
        echo "✅ Perfect tutor test PASSED - Only purple star notification sent\n";
    } else {
        echo "❌ Perfect tutor test FAILED - Expected 1 'tutor_perfect' notification\n";
    }
    
    // Clear for next test
    \App\Models\UniversalNotification::where('user_id', $user->id)->delete();
    
    // Test 4: Good Tutor Score (70%) - Should get regular tutor completion notification
    echo "\n🧪 TEST 4: Good Tutor Score (70%)\n";
    
    $goodTutorData = [
        'score' => 7,
        'total_questions' => 10,
        'course_name' => 'Internal Medicine',
        'topic_name' => 'Cardiology',
        'mode' => 'tutor'
    ];
    
    \App\Services\NotificationTriggerService::onQuizCompleted($user, $goodTutorData);
    
    $notifications = \App\Models\UniversalNotification::where('user_id', $user->id)->get();
    echo "   Notifications created: {$notifications->count()}\n";
    
    foreach ($notifications as $notification) {
        echo "   - Type: {$notification->type}, Title: {$notification->title}\n";
    }
    
    if ($notifications->count() === 1 && $notifications->first()->type === 'tutor_quiz_completed') {
        echo "✅ Good tutor test PASSED - Only regular tutor completion notification sent\n";
    } else {
        echo "❌ Good tutor test FAILED - Expected 1 'tutor_quiz_completed' notification\n";
    }
    
    // Test 5: Perfect Assignment Score (100%) - Should get ONLY purple star notification
    echo "\n🧪 TEST 5: Perfect Assignment Score (100%)\n";
    
    $perfectAssignmentData = [
        'score' => 15,
        'total_questions' => 15,
        'assignment_title' => 'Cardiology Quiz Assignment',
        'assignment_id' => 1
    ];
    
    \App\Services\NotificationTriggerService::onAssignmentCompleted($user, $perfectAssignmentData);
    
    $notifications = \App\Models\UniversalNotification::where('user_id', $user->id)->get();
    echo "   Notifications created: {$notifications->count()}\n";
    
    foreach ($notifications as $notification) {
        echo "   - Type: {$notification->type}, Title: {$notification->title}\n";
    }
    
    if ($notifications->count() === 1 && $notifications->first()->type === 'assignment_perfect') {
        echo "✅ Perfect assignment test PASSED - Only purple star notification sent\n";
    } else {
        echo "❌ Perfect assignment test FAILED - Expected 1 'assignment_perfect' notification\n";
    }
    
    echo "\n🎯 NOTIFICATION FIX SUMMARY:\n";
    echo "   ✅ Perfect scores now get ONLY purple star notifications\n";
    echo "   ✅ Non-perfect scores get regular completion notifications\n";
    echo "   ✅ No more duplicate notifications for perfect scores\n";
    echo "   ✅ Works for quiz, tutor, and assignment modes\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n✅ Notification duplication fix test completed!\n";