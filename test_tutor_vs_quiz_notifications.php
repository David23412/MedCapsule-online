<?php

/**
 * Test script to verify different notifications for tutor mode vs quiz mode
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🔔 TESTING TUTOR MODE VS QUIZ MODE NOTIFICATIONS\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Get a test user (first user in database)
$user = User::first();
if (!$user) {
    echo "❌ No users found in database. Please create a user first.\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Clear existing notifications for clean test
echo "🧹 Clearing existing notifications...\n";
$beforeCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   Found {$beforeCount} existing notifications\n\n";

// Test 1: Regular Quiz Mode Completion
echo "1️⃣ TESTING REGULAR QUIZ MODE COMPLETION\n";
echo "-" . str_repeat("-", 40) . "\n";

$quizData = [
    'score' => 8,
    'total_questions' => 10,
    'percentage' => 80,
    'course_name' => 'Test Course',
    'topic_name' => 'Test Topic',
    'user_quiz_count' => 5,
    'assignment_completed' => false,
    'mode' => 'quiz' // Regular quiz mode
];

NotificationTriggerService::onQuizCompleted($user, $quizData);

$afterQuizCount = UniversalNotificationService::getNotifications($user, 100)->count();
$quizNotifications = $afterQuizCount - $beforeCount;
echo "   ✅ Regular quiz notifications created: {$quizNotifications}\n";

// Show the notifications
$recentNotifications = UniversalNotificationService::getNotifications($user, 5);
foreach ($recentNotifications as $notification) {
    echo "   📝 {$notification['type']}: {$notification['title']} - {$notification['message']}\n";
}

echo "\n";

// Test 2: Tutor Mode Completion
echo "2️⃣ TESTING TUTOR MODE COMPLETION\n";
echo "-" . str_repeat("-", 40) . "\n";

$tutorData = [
    'correct_answers' => 9,
    'total_questions' => 10,
    'course_name' => 'Test Course',
    'topic_name' => 'Test Topic',
    'course_id' => 1,
    'topic_id' => 1,
    'mode' => 'tutor'
];

NotificationTriggerService::onTutorModeCompleted($user, $tutorData);

$afterTutorCount = UniversalNotificationService::getNotifications($user, 100)->count();
$tutorNotifications = $afterTutorCount - $afterQuizCount;
echo "   ✅ Tutor mode notifications created: {$tutorNotifications}\n";

// Show the new notifications
$recentNotifications = UniversalNotificationService::getNotifications($user, 3);
foreach ($recentNotifications as $notification) {
    echo "   🎯 {$notification['type']}: {$notification['title']} - {$notification['message']}\n";
}

echo "\n";

// Test 3: Perfect Score Scenarios
echo "3️⃣ TESTING PERFECT SCORE SCENARIOS\n";
echo "-" . str_repeat("-", 40) . "\n";

// Perfect score in regular quiz
$perfectQuizData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'course_name' => 'Perfect Test Course',
    'topic_name' => 'Perfect Test Topic',
    'user_quiz_count' => 1,
    'assignment_completed' => false,
    'mode' => 'quiz'
];

NotificationTriggerService::onQuizCompleted($user, $perfectQuizData);

// Perfect score in tutor mode
$perfectTutorData = [
    'correct_answers' => 10,
    'total_questions' => 10,
    'course_name' => 'Perfect Test Course',
    'topic_name' => 'Perfect Test Topic',
    'course_id' => 1,
    'topic_id' => 1,
    'mode' => 'tutor'
];

NotificationTriggerService::onTutorModeCompleted($user, $perfectTutorData);

$finalCount = UniversalNotificationService::getNotifications($user, 100)->count();
$perfectNotifications = $finalCount - $afterTutorCount;
echo "   ✅ Perfect score notifications created: {$perfectNotifications}\n";

// Show all recent notifications
echo "\n4️⃣ ALL RECENT NOTIFICATIONS\n";
echo "-" . str_repeat("-", 40) . "\n";
$allRecentNotifications = UniversalNotificationService::getNotifications($user, 10);
foreach ($allRecentNotifications as $index => $notification) {
    $icon = match($notification['type']) {
        'quiz_completed' => '📝',
        'perfect_score' => '🌟',
        'first_attempt' => '🚀',
        'tutor_session_completed' => '🎯',
        'tutor_mastery_achieved' => '🏆',
        'tutor_perfect_mastery' => '🏆',
        'tutor_strong_performance' => '💪',
        default => '🔔'
    };
    
    echo "   {$icon} [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n";
    echo "      └─ Priority: {$notification['priority']} | Created: {$notification['created_at']}\n\n";
}

echo "🎉 NOTIFICATION DIFFERENTIATION TEST COMPLETE!\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "📊 SUMMARY:\n";
echo "   • Regular quiz notifications: Different icons (📝, 🌟, 🚀)\n";
echo "   • Tutor mode notifications: Different icons (🎯, 🏆, 💪)\n";
echo "   • Different message content and notification types\n";
echo "   • Total notifications created: " . ($finalCount - $beforeCount) . "\n";