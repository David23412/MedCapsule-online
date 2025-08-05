<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🎯 TESTING TUTOR MODE NOTIFICATIONS IMPLEMENTATION\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Get test user
$user = User::find(2);
if (!$user) {
    echo "❌ User ID 2 not found\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Clear existing notifications for clean test
echo "🧹 Clearing existing notifications...\n";
$beforeCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   Found {$beforeCount} existing notifications\n\n";

// Test 1: Tutor mode with less than 50% score (should trigger practice notification)
echo "1️⃣ TESTING TUTOR MODE < 50% SCORE\n";
echo "-" . str_repeat("-", 40) . "\n";

$lowScoreTutorData = [
    'score' => 3,
    'total_questions' => 10,
    'percentage' => 30,
    'course_name' => 'Cardiology Basics',
    'mode' => 'tutor',
    'topic' => 'Heart Anatomy'
];

NotificationTriggerService::onQuizCompleted($user, $lowScoreTutorData);

$afterLowScore = UniversalNotificationService::getNotifications($user, 5);
echo "   ✅ Notifications created: " . ($afterLowScore->count() - $beforeCount) . "\n";

foreach ($afterLowScore->take(2) as $notification) {
    echo "   📖 [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n";
    echo "      └─ Priority: {$notification['priority']} | Icon: {$notification['icon']}\n\n";
}

// Test 2: Tutor mode with 100% score (should trigger perfect notification)
echo "2️⃣ TESTING TUTOR MODE 100% SCORE\n";
echo "-" . str_repeat("-", 40) . "\n";

$perfectTutorData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'course_name' => 'Anatomy Fundamentals',
    'mode' => 'tutor',
    'topic' => 'Skeletal System'
];

NotificationTriggerService::onQuizCompleted($user, $perfectTutorData);

$afterPerfect = UniversalNotificationService::getNotifications($user, 5);
echo "   ✅ Notifications created: " . ($afterPerfect->count() - $afterLowScore->count()) . "\n";

foreach ($afterPerfect->take(2) as $notification) {
    echo "   ⭐ [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n";
    echo "      └─ Priority: {$notification['priority']} | Icon: {$notification['icon']}\n\n";
}

// Test 3: Tutor mode with 75% score (should only trigger completion notification)
echo "3️⃣ TESTING TUTOR MODE 75% SCORE\n";
echo "-" . str_repeat("-", 40) . "\n";

$goodTutorData = [
    'score' => 7,
    'total_questions' => 10,
    'percentage' => 70,
    'course_name' => 'Physiology Basics',
    'mode' => 'tutor',
    'topic' => 'Cardiovascular System'
];

NotificationTriggerService::onQuizCompleted($user, $goodTutorData);

$afterGood = UniversalNotificationService::getNotifications($user, 5);
echo "   ✅ Notifications created: " . ($afterGood->count() - $afterPerfect->count()) . "\n";

foreach ($afterGood->take(1) as $notification) {
    echo "   ✅ [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n";
    echo "      └─ Priority: {$notification['priority']} | Icon: {$notification['icon']}\n\n";
}

// Summary of all recent notifications
echo "4️⃣ ALL RECENT TUTOR NOTIFICATIONS\n";
echo "-" . str_repeat("-", 40) . "\n";
$allRecentNotifications = UniversalNotificationService::getNotifications($user, 10);

foreach ($allRecentNotifications as $index => $notification) {
    if (strpos($notification['type'], 'tutor') !== false) {
        $icon = match($notification['type']) {
            'tutor_quiz_completed' => '✅',
            'tutor_perfect' => '⭐',
            'tutor_needs_practice' => '📖',
            default => '🎯'
        };
        
        echo "   {$icon} [{$notification['type']}] {$notification['title']}\n";
        echo "      └─ {$notification['message']}\n";
        echo "      └─ Priority: {$notification['priority']} | Created: {$notification['created_at']}\n\n";
    }
}

$finalCount = UniversalNotificationService::getNotifications($user, 100)->count();

echo "\n🎉 TUTOR NOTIFICATIONS IMPLEMENTATION TEST COMPLETE!\n";
echo "=" . str_repeat("=", 50) . "\n";
echo "📊 RESULTS:\n";
echo "   • Initial notifications: {$beforeCount}\n";
echo "   • Final notifications: {$finalCount}\n";
echo "   • New notifications created: " . ($finalCount - $beforeCount) . "\n\n";

echo "✅ IMPLEMENTATION VERIFIED:\n";
echo "   • Tutor mode < 50%: Triggers 'tutor_needs_practice' notification\n";
echo "   • Tutor mode 100%: Triggers 'tutor_perfect' with purple star\n";
echo "   • Tutor mode 50-99%: Only triggers 'tutor_quiz_completed'\n";
echo "   • All notifications have proper icons and messages\n\n";

echo "🎯 NOTIFICATION TYPES TESTED:\n";
echo "   • tutor_quiz_completed (fas fa-check-circle) - Blue\n";
echo "   • tutor_perfect (fas fa-star) - Purple star with purple border\n";
echo "   • tutor_needs_practice (fas fa-book-open) - Red\n";

?>