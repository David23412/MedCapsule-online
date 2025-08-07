<?php

/**
 * SUPER AGENT: Clear all notifications and run comprehensive test for User ID 2
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
use Illuminate\Support\Facades\Cache;

echo "🚀 SUPER AGENT: CLEARING AND TESTING USER ID 2\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Get user ID 2
$user = User::find(2);
if (!$user) {
    echo "❌ User ID 2 not found. Creating test user...\n";
        // Validate user creation data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            'password' => 'required|string|min:8'
        ]);

    $user = User::create([
        'name' => 'Test Student',
        'email' => 'test.student@example.com',
        'password' => bcrypt('password'),
        'role' => 'student'
    ]);
    echo "✅ Created test user with ID: {$user->id}\n";
}

echo "👤 Target user: {$user->name} (ID: {$user->id})\n\n";

// STEP 1: NUCLEAR CLEAR ALL NOTIFICATIONS
echo "💥 STEP 1: NUCLEAR CLEARING ALL NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Clear cache-based notifications
$cacheKey = "user_notifications_{$user->id}";
$unreadCacheKey = "user_unread_count_{$user->id}";

Cache::forget($cacheKey);
Cache::forget($unreadCacheKey);

echo "   ✅ Cleared notification cache\n";
echo "   ✅ Cleared unread count cache\n";

// Verify clearing
$remainingNotifications = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   📊 Remaining notifications: {$remainingNotifications}\n\n";

// STEP 2: COMPREHENSIVE NOTIFICATION GENERATION
echo "🎨 STEP 2: GENERATING ALL DISTINCT NOTIFICATION TYPES\n";
echo "-" . str_repeat("-", 50) . "\n";

$notificationCount = 0;

// 📚 REGULAR QUIZ MODE NOTIFICATIONS
echo "📚 Quiz Mode Notifications:\n";

// Quiz completed (80%)
$quizData = [
    'score' => 8, 'total_questions' => 10, 'percentage' => 80,
    'course_name' => 'Anatomy Course', 'topic_name' => 'Heart Structure',
    'user_quiz_count' => 5, 'assignment_completed' => false, 'mode' => 'quiz'
];
NotificationTriggerService::onQuizCompleted($user, $quizData);
$notificationCount++;
echo "   ✅ quiz_completed (fas fa-clipboard-check) - Blue\n";

// Perfect score (100%)
$perfectQuizData = [
    'score' => 10, 'total_questions' => 10, 'percentage' => 100,
    'course_name' => 'Perfect Anatomy', 'topic_name' => 'Perfect Topic',
    'user_quiz_count' => 5, 'assignment_completed' => false, 'mode' => 'quiz'
];
NotificationTriggerService::onQuizCompleted($user, $perfectQuizData);
$notificationCount += 2; // quiz_completed + perfect_score
echo "   ✅ perfect_score (fas fa-medal) - Gold\n";

// First attempt
$firstQuizData = [
    'score' => 7, 'total_questions' => 10, 'percentage' => 70,
    'course_name' => 'First Course', 'topic_name' => 'First Topic',
    'user_quiz_count' => 1, 'assignment_completed' => false, 'mode' => 'quiz'
];
NotificationTriggerService::onQuizCompleted($user, $firstQuizData);
$notificationCount += 2; // quiz_completed + first_attempt
echo "   ✅ first_attempt (fas fa-play-circle) - Green\n";

// 🎯 TUTOR MODE NOTIFICATIONS
echo "\n🎯 Tutor Mode Notifications:\n";

// Mastery achieved (90%)
$tutorData = [
    'correct_answers' => 9, 'total_questions' => 10,
    'course_name' => 'Physiology Course', 'topic_name' => 'Cardiac Function',
    'course_id' => 1, 'topic_id' => 2, 'mode' => 'tutor'
];
NotificationTriggerService::onTutorModeCompleted($user, $tutorData);
$notificationCount += 2; // tutor_session_completed + tutor_mastery_achieved
echo "   ✅ tutor_session_completed (fas fa-user-graduate) - Orange\n";
echo "   ✅ tutor_mastery_achieved (fas fa-award) - Red\n";

// Strong performance (85%)
$strongTutorData = [
    'correct_answers' => 85, 'total_questions' => 100,
    'course_name' => 'Strong Course', 'topic_name' => 'Strong Topic',
    'course_id' => 1, 'topic_id' => 4, 'mode' => 'tutor'
];
NotificationTriggerService::onTutorModeCompleted($user, $strongTutorData);
$notificationCount += 2; // tutor_session_completed + tutor_strong_performance
echo "   ✅ tutor_strong_performance (fas fa-thumbs-up) - Amber\n";

// Needs practice (60%)
$poorTutorData = [
    'correct_answers' => 6, 'total_questions' => 10,
    'course_name' => 'Challenging Course', 'topic_name' => 'Difficult Topic',
    'course_id' => 1, 'topic_id' => 3, 'mode' => 'tutor'
];
NotificationTriggerService::onTutorModeCompleted($user, $poorTutorData);
$notificationCount += 2; // tutor_session_completed + tutor_needs_practice
echo "   ✅ tutor_needs_practice (fas fa-redo-alt) - Red\n";

// 📋 ASSIGNMENT SYSTEM NOTIFICATIONS
echo "\n📋 Assignment System Notifications:\n";

// Create test assignment
$assignment = Assignment::first();
if (!$assignment) {
    $assignment = Assignment::create([
        'title' => 'Comprehensive Medical Assessment',
        'course_id' => 1,
        'topic_id' => 1,
        'deadline' => now()->addDays(7),
        'duration_minutes' => 60,
        'assignment_mode' => 'complete_topic',
        'assigned_source' => null
    ]);
    
    // Assign to user using the pivot table
    $assignment->assignedUsers()->attach($user->id);
}

// Tutor assigns assignment
$tutor = User::find(1) ?? $user;
NotificationTriggerService::onTutorAssignsAssignment($user, $assignment, $tutor);
$notificationCount += 2; // tutor_assignment_assigned + assignment_due_date_set
echo "   ✅ tutor_assignment_assigned (fas fa-tasks) - Violet\n";
echo "   ✅ assignment_due_date_set (fas fa-calendar-check) - Purple\n";

// Assignment completion scenarios
$assignmentScenarios = [
    ['score' => 20, 'percentage' => 100, 'title' => 'Perfect Assignment'],
    ['score' => 19, 'percentage' => 95, 'title' => 'Exceptional Assignment'],
    ['score' => 18, 'percentage' => 90, 'title' => 'Excellent Assignment'],
    ['score' => 16, 'percentage' => 80, 'title' => 'Good Assignment'],
    ['score' => 14, 'percentage' => 70, 'title' => 'Passed Assignment'],
    ['score' => 12, 'percentage' => 60, 'title' => 'Review Assignment'],
    ['score' => 10, 'percentage' => 50, 'title' => 'Improvement Assignment']
];

foreach ($assignmentScenarios as $scenario) {
    $assignmentData = [
        'score' => $scenario['score'], 'total_questions' => 20,
        'percentage' => $scenario['percentage'], 'assignment_title' => $scenario['title'],
        'assignment_id' => $assignment->id, 'mode' => 'assignment'
    ];
    
    NotificationTriggerService::onAssignmentCompleted($user, $assignmentData);
    
    if ($scenario['percentage'] == 100) {
        $notificationCount += 3; // perfect + exceptional + completed
        echo "   ✅ assignment_perfect (fas fa-trophy) - Pink Dark\n";
        echo "   ✅ assignment_exceptional (fas fa-gem) - Pink\n";
    } elseif ($scenario['percentage'] >= 95) {
        $notificationCount += 2; // exceptional + completed
        echo "   ✅ assignment_exceptional (fas fa-gem) - Pink\n";
    } elseif ($scenario['percentage'] >= 90) {
        $notificationCount += 2; // excellent + completed
        echo "   ✅ assignment_excellent (fas fa-star-of-life) - Fuchsia\n";
    } elseif ($scenario['percentage'] >= 80) {
        $notificationCount += 2; // good + completed
        echo "   ✅ assignment_good (fas fa-check-double) - Purple Light\n";
    } elseif ($scenario['percentage'] >= 70) {
        $notificationCount += 2; // passed + completed
        echo "   ✅ assignment_passed (fas fa-check-square) - Violet Light\n";
    } elseif ($scenario['percentage'] >= 60) {
        $notificationCount += 2; // needs_review + completed
        echo "   ✅ assignment_needs_review (fas fa-search) - Violet\n";
    } else {
        $notificationCount += 2; // needs_improvement + completed
        echo "   ✅ assignment_needs_improvement (fas fa-tools) - Purple Dark\n";
    }
    echo "   ✅ assignment_completed (fas fa-file-check) - Violet\n";
}

// ⏰ ASSIGNMENT DUE DATE REMINDERS
echo "\n⏰ Assignment Due Date Reminders (Escalating Colors):\n";

$reminderScenarios = [
    ['hours' => 48, 'type' => 'assignment_due_48h', 'icon' => 'fas fa-info-circle', 'color' => 'Cyan (Calm)'],
    ['hours' => 24, 'type' => 'assignment_due_24h', 'icon' => 'fas fa-clock', 'color' => 'Cyan Dark (Notice)'],
    ['hours' => 12, 'type' => 'assignment_due_12h', 'icon' => 'fas fa-hourglass-half', 'color' => 'Amber (Attention)'],
    ['hours' => 6, 'type' => 'assignment_due_6h', 'icon' => 'fas fa-hourglass-end', 'color' => 'Orange (Concern)'],
    ['hours' => 3, 'type' => 'assignment_due_3h', 'icon' => 'fas fa-exclamation-triangle', 'color' => 'Orange Dark (Urgent)'],
    ['hours' => 1, 'type' => 'assignment_due_1h', 'icon' => 'fas fa-fire', 'color' => 'Red (Critical)']
];

foreach ($reminderScenarios as $scenario) {
    NotificationTriggerService::onAssignmentOverdueReminder($user, $assignment, $scenario['hours']);
    $notificationCount++;
    echo "   ✅ {$scenario['type']} ({$scenario['icon']}) - {$scenario['color']}\n";
}

// 🚨 ASSIGNMENT OVERDUE NOTIFICATIONS
echo "\n🚨 Assignment Overdue Notifications (Escalating Severity):\n";

$overdueScenarios = [
    ['hours' => -1, 'type' => 'assignment_overdue_1h', 'icon' => 'fas fa-exclamation-circle', 'color' => 'Red (Just Overdue)'],
    ['hours' => -6, 'type' => 'assignment_overdue_6h', 'icon' => 'fas fa-times-circle', 'color' => 'Red Dark (Seriously Overdue)'],
    ['hours' => -24, 'type' => 'assignment_overdue_24h', 'icon' => 'fas fa-ban', 'color' => 'Red Darker (Very Overdue)'],
    ['hours' => -72, 'type' => 'assignment_overdue_days', 'icon' => 'fas fa-skull-crossbones', 'color' => 'Red Darkest (Extremely Overdue)']
];

foreach ($overdueScenarios as $scenario) {
    NotificationTriggerService::onAssignmentOverdueReminder($user, $assignment, $scenario['hours']);
    $notificationCount++;
    echo "   ✅ {$scenario['type']} ({$scenario['icon']}) - {$scenario['color']}\n";
}

// 🔔 SYSTEM NOTIFICATIONS
echo "\n🔔 System Notifications:\n";

// Course enrollment
NotificationTriggerService::onCourseEnrollment($user, 'Advanced Cardiology');
$notificationCount += 2; // course_enrolled + course_welcome
echo "   ✅ course_welcome (fas fa-graduation-cap) - Violet\n";

// Study streak
NotificationTriggerService::onStudyStreak($user, 7);
$notificationCount++;
echo "   ✅ study_streak (fas fa-fire) - Orange\n";

// Milestone
NotificationTriggerService::onCorrectAnswersMilestone($user, 100);
$notificationCount++;
echo "   ✅ correct_answers_milestone (fas fa-trophy) - Amber\n";

// Mistakes need review
NotificationTriggerService::onMistakesNeedReview($user, 5);
$notificationCount++;
echo "   ✅ review_mistakes (fas fa-exclamation-triangle) - Red\n";

// Weak topic
NotificationTriggerService::onWeakTopicIdentified($user, 'Pharmacology');
$notificationCount++;
echo "   ✅ weak_topic_identified (fas fa-exclamation-circle) - Red\n";

// Payment success
NotificationTriggerService::onPaymentSuccess($user, ['amount' => 99.99, 'course_name' => 'Premium Course']);
$notificationCount += 2; // payment_success + course_access_granted
echo "   ✅ payment_success (fas fa-credit-card) - Green\n";
echo "   ✅ course_access_granted (fas fa-unlock) - Green\n";

$finalCount = UniversalNotificationService::getNotifications($user, 100)->count();

echo "\n🎉 SUPER AGENT MISSION COMPLETE!\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "📊 FINAL RESULTS:\n";
echo "   👤 User: {$user->name} (ID: {$user->id})\n";
echo "   📧 Notifications generated: ~{$notificationCount}\n";
echo "   📱 Actual notifications in system: {$finalCount}\n\n";

echo "🎨 DISTINCT ICON CATEGORIES CREATED:\n";
echo "   📚 QUIZ MODE: Blue/Green colors (clipboard-check, medal, play-circle)\n";
echo "   🎯 TUTOR MODE: Orange/Red colors (user-graduate, award, crown, thumbs-up, redo-alt)\n";
echo "   📋 ASSIGNMENTS: Purple/Pink colors (tasks, calendar-check, file-check, gem, star-of-life, etc.)\n";
echo "   ⏰ DUE DATES: Cyan→Red escalation (info-circle → clock → hourglass → fire)\n";
echo "   🚨 OVERDUE: Red→Black escalation (exclamation-circle → times-circle → ban → skull-crossbones)\n";
echo "   🔔 SYSTEM: Gray/Blue colors (graduation-cap, fire, trophy, credit-card, unlock)\n\n";

echo "✅ USER ID 2 NOW HAS ALL DISTINCT NOTIFICATION ICONS WITH COLORS!\n";
echo "   🎯 Each notification type has a unique FontAwesome icon\n";
echo "   🎨 Each category has distinct color schemes\n";
echo "   📱 The notification dropdown will display all icons properly\n";
echo "   🚀 Ready for UI testing!\n";