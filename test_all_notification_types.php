<?php

/**
 * COMPREHENSIVE TEST: Quiz vs Tutor vs Assignment Notifications
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Assignment;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🔔 COMPREHENSIVE NOTIFICATION SYSTEM TEST\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Get a test user
$user = User::first();
if (!$user) {
    echo "❌ No users found in database. Please create a user first.\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Clear existing notifications
echo "🧹 Clearing existing notifications...\n";
$beforeCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   Found {$beforeCount} existing notifications\n\n";

// Test 1: REGULAR QUIZ MODE
echo "1️⃣ REGULAR QUIZ MODE NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

$quizData = [
    'score' => 8,
    'total_questions' => 10,
    'percentage' => 80,
    'course_name' => 'Anatomy Course',
    'topic_name' => 'Heart Structure',
    'user_quiz_count' => 3,
    'assignment_completed' => false,
    'mode' => 'quiz'
];

NotificationTriggerService::onQuizCompleted($user, $quizData);

$afterQuizCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Quiz notifications created: " . ($afterQuizCount - $beforeCount) . "\n";

// Test 2: TUTOR MODE
echo "\n2️⃣ TUTOR MODE NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

$tutorData = [
    'correct_answers' => 9,
    'total_questions' => 10,
    'course_name' => 'Physiology Course',
    'topic_name' => 'Cardiac Function',
    'course_id' => 1,
    'topic_id' => 2,
    'mode' => 'tutor'
];

NotificationTriggerService::onTutorModeCompleted($user, $tutorData);

$afterTutorCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Tutor notifications created: " . ($afterTutorCount - $afterQuizCount) . "\n";

// Test 3: ASSIGNMENT COMPLETION
echo "\n3️⃣ ASSIGNMENT COMPLETION NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Create a test assignment if none exists
$assignment = Assignment::first();
if (!$assignment) {
    echo "   ⚠️ No assignments found, creating test assignment...\n";
    $assignment = Assignment::create([
        'title' => 'Test Assignment - Cardiovascular System',
        'description' => 'Complete assessment of cardiovascular knowledge',
        'course_id' => 1,
        'topic_id' => 1,
        'tutor_id' => $user->id,
        'deadline' => now()->addDays(7),
        'assignment_mode' => 'complete_topic',
        'status' => 'active'
    ]);
}

$assignmentData = [
    'score' => 9,
    'total_questions' => 10,
    'percentage' => 90,
    'assignment_title' => $assignment->title,
    'assignment_id' => $assignment->id,
    'mode' => 'assignment'
];

NotificationTriggerService::onAssignmentCompleted($user, $assignmentData);

$afterAssignmentCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Assignment notifications created: " . ($afterAssignmentCount - $afterTutorCount) . "\n";

// Test 4: PERFECT SCORES IN ALL MODES
echo "\n4️⃣ PERFECT SCORE SCENARIOS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Perfect Quiz
$perfectQuizData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'course_name' => 'Perfect Quiz Course',
    'topic_name' => 'Perfect Topic',
    'user_quiz_count' => 1,
    'assignment_completed' => false,
    'mode' => 'quiz'
];
NotificationTriggerService::onQuizCompleted($user, $perfectQuizData);

// Perfect Tutor
$perfectTutorData = [
    'correct_answers' => 10,
    'total_questions' => 10,
    'course_name' => 'Perfect Tutor Course',
    'topic_name' => 'Perfect Tutor Topic',
    'course_id' => 1,
    'topic_id' => 1,
    'mode' => 'tutor'
];
NotificationTriggerService::onTutorModeCompleted($user, $perfectTutorData);

// Perfect Assignment
$perfectAssignmentData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'assignment_title' => 'Perfect Assignment Test',
    'assignment_id' => $assignment->id,
    'mode' => 'assignment'
];
NotificationTriggerService::onAssignmentCompleted($user, $perfectAssignmentData);

$finalCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Perfect score notifications created: " . ($finalCount - $afterAssignmentCount) . "\n";

// Display all notifications with categorization
echo "\n5️⃣ ALL NOTIFICATIONS BY CATEGORY\n";
echo "-" . str_repeat("-", 50) . "\n";

$allNotifications = UniversalNotificationService::getNotifications($user, 20);

$categories = [
    'quiz' => [],
    'tutor' => [],
    'assignment' => []
];

foreach ($allNotifications as $notification) {
    if (str_contains($notification['type'], 'tutor')) {
        $categories['tutor'][] = $notification;
    } elseif (str_contains($notification['type'], 'assignment')) {
        $categories['assignment'][] = $notification;
    } else {
        $categories['quiz'][] = $notification;
    }
}

// Display Quiz Notifications
echo "\n📝 QUIZ MODE NOTIFICATIONS:\n";
foreach ($categories['quiz'] as $notification) {
    $icon = match($notification['type']) {
        'quiz_completed' => '📝',
        'perfect_score' => '🌟',
        'first_attempt' => '🚀',
        default => '📋'
    };
    echo "   {$icon} [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n\n";
}

// Display Tutor Notifications
echo "🎯 TUTOR MODE NOTIFICATIONS:\n";
foreach ($categories['tutor'] as $notification) {
    $icon = match($notification['type']) {
        'tutor_session_completed' => '🎯',
        'tutor_mastery_achieved' => '🏆',
        'tutor_perfect_mastery' => '👑',
        'tutor_strong_performance' => '💪',
        default => '🎯'
    };
    echo "   {$icon} [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n\n";
}

// Display Assignment Notifications
echo "📋 ASSIGNMENT NOTIFICATIONS:\n";
foreach ($categories['assignment'] as $notification) {
    $icon = match($notification['type']) {
        'assignment_completed' => '📋',
        'assignment_perfect' => '🎯',
        'assignment_passed' => '✅',
        'assignment_needs_improvement' => '📚',
        default => '📋'
    };
    echo "   {$icon} [{$notification['type']}] {$notification['title']}\n";
    echo "      └─ {$notification['message']}\n\n";
}

echo "🎉 COMPREHENSIVE NOTIFICATION TEST COMPLETE!\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "📊 SUMMARY:\n";
echo "   • Quiz notifications: " . count($categories['quiz']) . " (📝, 🌟, 🚀)\n";
echo "   • Tutor notifications: " . count($categories['tutor']) . " (🎯, 🏆, 👑, 💪)\n";
echo "   • Assignment notifications: " . count($categories['assignment']) . " (📋, 🎯, ✅, 📚)\n";
echo "   • Total notifications: " . ($finalCount - $beforeCount) . "\n\n";

echo "🔍 KEY DIFFERENCES:\n";
echo "   • QUIZ: Shows course/topic details, focuses on learning progress\n";
echo "   • TUTOR: Shows mastery/accuracy, focuses on understanding\n";
echo "   • ASSIGNMENT: Shows assignment title ONLY, no topic details\n";
echo "   • Each mode has distinct icons, messages, and notification types\n";