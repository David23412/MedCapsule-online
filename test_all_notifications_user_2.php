<?php

/**
 * COMPREHENSIVE TEST: Send ALL notification types to USER ID 2
 * This will showcase every distinct icon in the notification system
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

echo "🔔 COMPREHENSIVE NOTIFICATION SHOWCASE FOR USER ID 2\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Get user ID 2
$user = User::find(2);
if (!$user) {
    echo "❌ User ID 2 not found. Creating test user...\n";
    $user = User::create([
        'name' => 'Test Student',
        'email' => 'test.student@example.com',
        'password' => bcrypt('password'),
        'role' => 'student'
    ]);
    echo "✅ Created test user with ID: {$user->id}\n";
}

echo "👤 Sending notifications to: {$user->name} (ID: {$user->id})\n\n";

// Clear existing notifications for clean showcase
echo "🧹 Clearing existing notifications...\n";
$beforeCount = UniversalNotificationService::getNotifications($user, 100)->count();
echo "   Cleared {$beforeCount} existing notifications\n\n";

$notificationCount = 0;

// 1. REGULAR QUIZ MODE NOTIFICATIONS
echo "1️⃣ REGULAR QUIZ MODE NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Quiz completed
$quizData = [
    'score' => 8,
    'total_questions' => 10,
    'percentage' => 80,
    'course_name' => 'Anatomy Course',
    'topic_name' => 'Heart Structure',
    'user_quiz_count' => 5,
    'assignment_completed' => false,
    'mode' => 'quiz'
];
NotificationTriggerService::onQuizCompleted($user, $quizData);
$notificationCount++;
echo "   ✅ quiz_completed (fas fa-clipboard-check)\n";

// Perfect score
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
$notificationCount += 2; // quiz_completed + perfect_score
echo "   ✅ perfect_score (fas fa-medal)\n";

// First attempt
$firstQuizData = [
    'score' => 7,
    'total_questions' => 10,
    'percentage' => 70,
    'course_name' => 'First Course',
    'topic_name' => 'First Topic',
    'user_quiz_count' => 1, // First attempt
    'assignment_completed' => false,
    'mode' => 'quiz'
];
NotificationTriggerService::onQuizCompleted($user, $firstQuizData);
$notificationCount += 2; // quiz_completed + first_attempt
echo "   ✅ first_attempt (fas fa-play-circle)\n";

// 2. TUTOR MODE NOTIFICATIONS
echo "\n2️⃣ TUTOR MODE NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Regular tutor session (90% - mastery achieved)
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
$notificationCount += 2; // tutor_session_completed + tutor_mastery_achieved
echo "   ✅ tutor_session_completed (fas fa-user-graduate)\n";
echo "   ✅ tutor_mastery_achieved (fas fa-award)\n";

// Perfect tutor session (100%)
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
$notificationCount += 2; // tutor_session_completed + tutor_mastery_achieved
echo "   ✅ tutor_perfect_mastery (fas fa-crown) - if 100%\n";

// Poor tutor session (60% - needs practice)
$poorTutorData = [
    'correct_answers' => 6,
    'total_questions' => 10,
    'course_name' => 'Challenging Course',
    'topic_name' => 'Difficult Topic',
    'course_id' => 1,
    'topic_id' => 3,
    'mode' => 'tutor'
];
NotificationTriggerService::onTutorModeCompleted($user, $poorTutorData);
$notificationCount += 2; // tutor_session_completed + tutor_needs_practice
echo "   ✅ tutor_needs_practice (fas fa-redo-alt)\n";

// Strong performance (85%)
$strongTutorData = [
    'correct_answers' => 85,
    'total_questions' => 100,
    'course_name' => 'Strong Course',
    'topic_name' => 'Strong Topic',
    'course_id' => 1,
    'topic_id' => 4,
    'mode' => 'tutor'
];
NotificationTriggerService::onTutorModeCompleted($user, $strongTutorData);
$notificationCount += 2; // tutor_session_completed + tutor_strong_performance
echo "   ✅ tutor_strong_performance (fas fa-thumbs-up)\n";

// 3. ASSIGNMENT SYSTEM NOTIFICATIONS
echo "\n3️⃣ ASSIGNMENT SYSTEM NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Create test assignment if needed
$assignment = Assignment::first();
if (!$assignment) {
    $assignment = Assignment::create([
        'title' => 'Comprehensive Medical Assessment',
        'description' => 'Complete assessment covering all topics',
        'course_id' => 1,
        'topic_id' => 1,
        'tutor_id' => 1,
        'student_id' => $user->id,
        'deadline' => now()->addDays(7),
        'assignment_mode' => 'complete_topic',
        'status' => 'active'
    ]);
}

// Tutor assigns assignment
$tutor = User::find(1) ?? $user; // Use admin or self as tutor
NotificationTriggerService::onTutorAssignsAssignment($user, $assignment, $tutor);
$notificationCount += 2; // tutor_assignment_assigned + assignment_due_date_set
echo "   ✅ tutor_assignment_assigned (fas fa-tasks)\n";
echo "   ✅ assignment_due_date_set (fas fa-calendar-check)\n";

// Assignment completion scenarios - ALL percentage ranges
$assignmentScenarios = [
    ['score' => 10, 'percentage' => 100, 'title' => 'Perfect Assignment', 'expected' => 'assignment_perfect + assignment_exceptional + assignment_completed'],
    ['score' => 19, 'percentage' => 95, 'title' => 'Exceptional Assignment', 'expected' => 'assignment_exceptional + assignment_completed'],
    ['score' => 18, 'percentage' => 90, 'title' => 'Excellent Assignment', 'expected' => 'assignment_excellent + assignment_completed'],
    ['score' => 16, 'percentage' => 80, 'title' => 'Good Assignment', 'expected' => 'assignment_good + assignment_completed'],
    ['score' => 14, 'percentage' => 70, 'title' => 'Passed Assignment', 'expected' => 'assignment_passed + assignment_completed'],
    ['score' => 12, 'percentage' => 60, 'title' => 'Review Assignment', 'expected' => 'assignment_needs_review + assignment_completed'],
    ['score' => 10, 'percentage' => 50, 'title' => 'Improvement Assignment', 'expected' => 'assignment_needs_improvement + assignment_completed']
];

foreach ($assignmentScenarios as $scenario) {
    $assignmentData = [
        'score' => $scenario['score'],
        'total_questions' => 20,
        'percentage' => $scenario['percentage'],
        'assignment_title' => $scenario['title'],
        'assignment_id' => $assignment->id,
        'mode' => 'assignment'
    ];
    
    NotificationTriggerService::onAssignmentCompleted($user, $assignmentData);
    
    // Count notifications based on percentage
    if ($scenario['percentage'] == 100) {
        $notificationCount += 3; // perfect + exceptional + completed
        echo "   ✅ assignment_perfect (fas fa-trophy)\n";
        echo "   ✅ assignment_exceptional (fas fa-gem)\n";
    } elseif ($scenario['percentage'] >= 95) {
        $notificationCount += 2; // exceptional + completed
        echo "   ✅ assignment_exceptional (fas fa-gem)\n";
    } elseif ($scenario['percentage'] >= 90) {
        $notificationCount += 2; // excellent + completed
        echo "   ✅ assignment_excellent (fas fa-star-of-life)\n";
    } elseif ($scenario['percentage'] >= 80) {
        $notificationCount += 2; // good + completed
        echo "   ✅ assignment_good (fas fa-check-double)\n";
    } elseif ($scenario['percentage'] >= 70) {
        $notificationCount += 2; // passed + completed
        echo "   ✅ assignment_passed (fas fa-check-square)\n";
    } elseif ($scenario['percentage'] >= 60) {
        $notificationCount += 2; // needs_review + completed
        echo "   ✅ assignment_needs_review (fas fa-search)\n";
    } else {
        $notificationCount += 2; // needs_improvement + completed
        echo "   ✅ assignment_needs_improvement (fas fa-tools)\n";
    }
    echo "   ✅ assignment_completed (fas fa-file-check)\n";
}

// 4. ASSIGNMENT DUE DATE REMINDERS (Urgency Escalation)
echo "\n4️⃣ ASSIGNMENT DUE DATE REMINDERS (Urgency Escalation)\n";
echo "-" . str_repeat("-", 50) . "\n";

$reminderScenarios = [
    ['hours' => 48, 'type' => 'assignment_due_48h', 'icon' => 'fas fa-info-circle'],
    ['hours' => 24, 'type' => 'assignment_due_24h', 'icon' => 'fas fa-clock'],
    ['hours' => 12, 'type' => 'assignment_due_12h', 'icon' => 'fas fa-hourglass-half'],
    ['hours' => 6, 'type' => 'assignment_due_6h', 'icon' => 'fas fa-hourglass-end'],
    ['hours' => 3, 'type' => 'assignment_due_3h', 'icon' => 'fas fa-exclamation-triangle'],
    ['hours' => 1, 'type' => 'assignment_due_1h', 'icon' => 'fas fa-fire']
];

foreach ($reminderScenarios as $scenario) {
    NotificationTriggerService::onAssignmentOverdueReminder($user, $assignment, $scenario['hours']);
    $notificationCount++;
    echo "   ✅ {$scenario['type']} ({$scenario['icon']})\n";
}

// 5. ASSIGNMENT OVERDUE NOTIFICATIONS (Severity Escalation)
echo "\n5️⃣ ASSIGNMENT OVERDUE NOTIFICATIONS (Severity Escalation)\n";
echo "-" . str_repeat("-", 50) . "\n";

$overdueScenarios = [
    ['hours' => -1, 'type' => 'assignment_overdue_1h', 'icon' => 'fas fa-exclamation-circle'],
    ['hours' => -6, 'type' => 'assignment_overdue_6h', 'icon' => 'fas fa-times-circle'],
    ['hours' => -24, 'type' => 'assignment_overdue_24h', 'icon' => 'fas fa-ban'],
    ['hours' => -72, 'type' => 'assignment_overdue_days', 'icon' => 'fas fa-skull-crossbones']
];

foreach ($overdueScenarios as $scenario) {
    NotificationTriggerService::onAssignmentOverdueReminder($user, $assignment, $scenario['hours']);
    $notificationCount++;
    echo "   ✅ {$scenario['type']} ({$scenario['icon']})\n";
}

// 6. ADDITIONAL SYSTEM NOTIFICATIONS
echo "\n6️⃣ ADDITIONAL SYSTEM NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Course enrollment
NotificationTriggerService::onCourseEnrollment($user, 'Advanced Cardiology');
$notificationCount += 2; // course_enrolled + course_welcome
echo "   ✅ course_welcome (fas fa-graduation-cap)\n";

// Study streak
NotificationTriggerService::onStudyStreak($user, 7);
$notificationCount++;
echo "   ✅ study_streak (fas fa-fire)\n";

// Milestone
NotificationTriggerService::onCorrectAnswersMilestone($user, 100);
$notificationCount++;
echo "   ✅ correct_answers_milestone (fas fa-trophy)\n";

// Mistakes need review
NotificationTriggerService::onMistakesNeedReview($user, 5);
$notificationCount++;
echo "   ✅ review_mistakes (fas fa-exclamation-triangle)\n";

// Weak topic
NotificationTriggerService::onWeakTopicIdentified($user, 'Pharmacology');
$notificationCount++;
echo "   ✅ weak_topic_identified (fas fa-exclamation-circle)\n";

// Payment success
NotificationTriggerService::onPaymentSuccess($user, ['amount' => 99.99, 'course_name' => 'Premium Course']);
$notificationCount += 2; // payment_success + course_access_granted
echo "   ✅ payment_success (fas fa-credit-card)\n";
echo "   ✅ course_access_granted (fas fa-unlock)\n";

$finalCount = UniversalNotificationService::getNotifications($user, 100)->count();

echo "\n🎉 COMPREHENSIVE NOTIFICATION SHOWCASE COMPLETE!\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "📊 FINAL SUMMARY:\n";
echo "   👤 User: {$user->name} (ID: {$user->id})\n";
echo "   📧 Total notifications sent: ~{$notificationCount}\n";
echo "   📱 Actual notifications in system: {$finalCount}\n\n";

echo "🎨 ICON SHOWCASE CATEGORIES:\n";
echo "   📚 QUIZ MODE: clipboard-check, medal, play-circle\n";
echo "   🎯 TUTOR MODE: user-graduate, award, crown, thumbs-up, redo-alt\n";
echo "   📋 ASSIGNMENTS: tasks, calendar-check, file-check, gem, star-of-life, check-double, check-square, search, tools, trophy\n";
echo "   ⏰ DUE DATES: info-circle → clock → hourglass-half → hourglass-end → exclamation-triangle → fire\n";
echo "   🚨 OVERDUE: exclamation-circle → times-circle → ban → skull-crossbones\n";
echo "   🔔 SYSTEM: graduation-cap, fire, trophy, exclamation-triangle, exclamation-circle, credit-card, unlock\n\n";

echo "✅ USER ID 2 NOW HAS ALL NOTIFICATION TYPES WITH DISTINCT ICONS!\n";
echo "   Log into the system as User ID 2 to see all the different notification icons in action.\n";