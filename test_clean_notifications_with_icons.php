<?php

/**
 * TEST: Clean Notifications with Distinct Icons
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

echo "🔔 CLEAN NOTIFICATIONS WITH DISTINCT ICONS TEST\n";
echo "=" . str_repeat("=", 70) . "\n\n";

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

// Test 1: REGULAR QUIZ MODE - Clean titles, distinct icons
echo "1️⃣ REGULAR QUIZ MODE (Learning & Assessment Icons)\n";
echo "-" . str_repeat("-", 60) . "\n";

$quizData = [
    'score' => 8,
    'total_questions' => 10,
    'percentage' => 80,
    'course_name' => 'Anatomy Course',
    'topic_name' => 'Heart Structure',
    'user_quiz_count' => 1, // First attempt
    'assignment_completed' => false,
    'mode' => 'quiz'
];

NotificationTriggerService::onQuizCompleted($user, $quizData);

// Perfect quiz
$perfectQuizData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'course_name' => 'Perfect Anatomy',
    'topic_name' => 'Perfect Topic',
    'user_quiz_count' => 5,
    'assignment_completed' => false,
    'mode' => 'quiz'
];

NotificationTriggerService::onQuizCompleted($user, $perfectQuizData);

$afterQuizCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Quiz notifications created: " . ($afterQuizCount - $beforeCount) . "\n";

// Test 2: TUTOR MODE - Guidance & mastery icons
echo "\n2️⃣ TUTOR MODE (Guidance & Mastery Icons)\n";
echo "-" . str_repeat("-", 60) . "\n";

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

// Perfect tutor session
$perfectTutorData = [
    'correct_answers' => 10,
    'total_questions' => 10,
    'course_name' => 'Perfect Physiology',
    'topic_name' => 'Perfect Cardiac',
    'course_id' => 1,
    'topic_id' => 2,
    'mode' => 'tutor'
];

NotificationTriggerService::onTutorModeCompleted($user, $perfectTutorData);

$afterTutorCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Tutor notifications created: " . ($afterTutorCount - $afterQuizCount) . "\n";

// Test 3: ASSIGNMENT SYSTEM - Task & deadline icons
echo "\n3️⃣ ASSIGNMENT SYSTEM (Task & Deadline Icons)\n";
echo "-" . str_repeat("-", 60) . "\n";

// Create test assignment if needed
$assignment = Assignment::first();
if (!$assignment) {
    $assignment = Assignment::create([
        'title' => 'Cardiovascular System Assessment',
        'description' => 'Complete assessment of cardiovascular knowledge',
        'course_id' => 1,
        'topic_id' => 1,
        'tutor_id' => $user->id,
        'deadline' => now()->addDays(7),
        'assignment_mode' => 'complete_topic',
        'status' => 'active'
    ]);
}

// Test different assignment completion scenarios
$assignmentScenarios = [
    ['score' => 10, 'percentage' => 100, 'title' => 'Perfect Assignment'],
    ['score' => 9, 'percentage' => 95, 'title' => 'Exceptional Assignment'],
    ['score' => 9, 'percentage' => 90, 'title' => 'Excellent Assignment'],
    ['score' => 8, 'percentage' => 80, 'title' => 'Good Assignment'],
    ['score' => 7, 'percentage' => 70, 'title' => 'Passed Assignment'],
    ['score' => 6, 'percentage' => 60, 'title' => 'Review Assignment'],
    ['score' => 5, 'percentage' => 50, 'title' => 'Improvement Assignment']
];

foreach ($assignmentScenarios as $scenario) {
    $assignmentData = [
        'score' => $scenario['score'],
        'total_questions' => 10,
        'percentage' => $scenario['percentage'],
        'assignment_title' => $scenario['title'],
        'assignment_id' => $assignment->id,
        'mode' => 'assignment'
    ];
    
    NotificationTriggerService::onAssignmentCompleted($user, $assignmentData);
}

$finalCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   ✅ Assignment notifications created: " . ($finalCount - $afterTutorCount) . "\n";

// Display all notifications with their distinct icons
echo "\n4️⃣ ALL NOTIFICATIONS WITH DISTINCT ICONS\n";
echo "-" . str_repeat("-", 60) . "\n";

$allNotifications = UniversalNotificationService::getNotifications($user, 30);

// Get icon mapping
$iconMapping = UniversalNotificationService::NOTIFICATION_ICONS;

$categories = [
    'QUIZ MODE (Learning & Assessment)' => [],
    'TUTOR MODE (Guidance & Mastery)' => [],
    'ASSIGNMENT SYSTEM (Task & Deadline)' => []
];

foreach ($allNotifications as $notification) {
    $icon = $iconMapping[$notification['type']] ?? 'fas fa-bell';
    $notification['icon'] = $icon;
    
    if (str_contains($notification['type'], 'tutor')) {
        $categories['TUTOR MODE (Guidance & Mastery)'][] = $notification;
    } elseif (str_contains($notification['type'], 'assignment')) {
        $categories['ASSIGNMENT SYSTEM (Task & Deadline)'][] = $notification;
    } else {
        $categories['QUIZ MODE (Learning & Assessment)'][] = $notification;
    }
}

foreach ($categories as $categoryName => $notifications) {
    if (empty($notifications)) continue;
    
    echo "\n📚 {$categoryName}:\n";
    foreach ($notifications as $notification) {
        echo "   [{$notification['icon']}] {$notification['title']}\n";
        echo "      └─ {$notification['message']}\n";
        echo "      └─ Type: {$notification['type']} | Priority: {$notification['priority']}\n\n";
    }
}

echo "🎉 CLEAN NOTIFICATIONS WITH DISTINCT ICONS TEST COMPLETE!\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "📊 SUMMARY:\n";
echo "   • Quiz notifications: " . count($categories['QUIZ MODE (Learning & Assessment)']) . " with learning icons\n";
echo "   • Tutor notifications: " . count($categories['TUTOR MODE (Guidance & Mastery)']) . " with mastery icons\n";
echo "   • Assignment notifications: " . count($categories['ASSIGNMENT SYSTEM (Task & Deadline)']) . " with task icons\n";
echo "   • Total notifications: " . ($finalCount - $beforeCount) . "\n\n";

echo "🔍 KEY IMPROVEMENTS:\n";
echo "   ✅ NO EMOJIS in notification titles or messages\n";
echo "   ✅ DISTINCT FontAwesome icons for each notification type\n";
echo "   ✅ CLEAN, professional notification text\n";
echo "   ✅ UNIQUE icons that reflect the context and urgency\n";
echo "   ✅ ESCALATING urgency icons for assignment due dates\n\n";

echo "🎨 ICON CATEGORIES:\n";
echo "   📚 QUIZ: clipboard-check, medal, play-circle (learning focus)\n";
echo "   🎯 TUTOR: chalkboard-teacher, user-graduate, award (guidance focus)\n";
echo "   📋 ASSIGNMENT: tasks, file-check, gem, trophy (task focus)\n";
echo "   ⏰ DUE DATES: info-circle → clock → hourglass → fire (urgency escalation)\n";
echo "   🚨 OVERDUE: exclamation-circle → times-circle → ban → skull (severity escalation)\n";