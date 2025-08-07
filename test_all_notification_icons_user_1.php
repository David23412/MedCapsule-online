<?php

/**
 * COMPREHENSIVE NOTIFICATION ICONS TEST FOR USER ID 1
 * This script creates all possible notification types to test all icons
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

echo "🔔 COMPREHENSIVE NOTIFICATION ICONS TEST FOR USER ID 1\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Get user ID 1
$user = User::find(1);
if (!$user) {
    echo "❌ User ID 1 not found in database. Please create user ID 1 first.\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Clear existing notifications for clean test
echo "🧹 Clearing existing notifications...\n";
$cacheKey = "user_notifications_{$user->id}";
\Illuminate\Support\Facades\Cache::put($cacheKey, []);
\Illuminate\Support\Facades\Cache::forget("user_unread_count_{$user->id}");
echo "   ✅ All notifications cleared\n\n";

// 1. ACADEMIC & LEARNING NOTIFICATIONS
echo "1️⃣ ACADEMIC & LEARNING NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

$notifications = [
    ['course_welcome', 'Welcome to Course', 'Welcome to Advanced Anatomy Course!'],
    ['quiz_completed', 'Quiz Completed', 'You scored 85% on Cardiovascular System'],
    ['perfect_score', 'Perfect Score!', 'Amazing! You got 100% on Heart Structure'],
    ['first_attempt', 'First Attempt Success', 'Great job on your first quiz attempt!'],
    ['topic_mastery', 'Topic Mastered', 'You have mastered Cardiac Physiology'],
    ['level_up', 'Level Up!', 'Congratulations! You reached Level 5'],
    ['assignment_due', 'Assignment Due Soon', 'Your assignment is due in 2 hours'],
    ['grade_released', 'Grade Released', 'Your assignment grade is now available'],
];

foreach ($notifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 2. TUTOR MODE SPECIFIC NOTIFICATIONS
echo "\n2️⃣ TUTOR MODE SPECIFIC NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

$tutorNotifications = [
    ['tutor_session_completed', 'Tutor Session Complete', 'You completed a tutor session with 90% accuracy'],
    ['tutor_mastery_achieved', 'Mastery Achieved', 'You achieved mastery in tutor mode!'],
    ['tutor_perfect_mastery', 'Perfect Mastery', 'Perfect mastery achieved in tutor mode!'],
    ['tutor_perfect', 'Perfect Tutor Score', 'Outstanding! 100% in tutor mode'],
    ['tutor_strong_performance', 'Strong Performance', 'Excellent work in tutor mode!'],
    ['tutor_needs_practice', 'More Practice Needed', 'Keep practicing to improve your score'],
];

foreach ($tutorNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 3. ASSIGNMENT SPECIFIC NOTIFICATIONS
echo "\n3️⃣ ASSIGNMENT SPECIFIC NOTIFICATIONS\n";
echo "-" . str_repeat("-", 50) . "\n";

$assignmentNotifications = [
    ['assignment_assigned', 'New Assignment', 'You have been assigned: Cardiovascular Assessment'],
    ['assignment_completed', 'Assignment Completed', 'You completed: Anatomy Final Project'],
    ['assignment_exceptional', 'Exceptional Work', 'Your assignment received exceptional marks!'],
    ['assignment_excellent', 'Excellent Performance', 'Excellent work on your assignment!'],
    ['assignment_good', 'Good Job', 'Good performance on your assignment'],
    ['assignment_passed', 'Assignment Passed', 'You successfully passed the assignment'],
    ['assignment_needs_review', 'Needs Review', 'Your assignment needs further review'],
    ['assignment_needs_improvement', 'Needs Improvement', 'Your assignment needs some improvement'],
    ['assignment_perfect', 'Perfect Assignment', 'Perfect score on your assignment!'],
    ['assignment_due_1h', 'Due in 1 Hour', 'Assignment due in 1 hour!'],
    ['assignment_due_3h', 'Due in 3 Hours', 'Assignment due in 3 hours'],
    ['assignment_overdue', 'Assignment Overdue', 'Your assignment is now overdue'],
    ['assignment_created', 'Assignment Created', 'New assignment has been created for you'],
];

foreach ($assignmentNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 4. ACHIEVEMENTS & MILESTONES
echo "\n4️⃣ ACHIEVEMENTS & MILESTONES\n";
echo "-" . str_repeat("-", 50) . "\n";

$achievementNotifications = [
    ['achievement', 'Achievement Unlocked', 'You unlocked the "Quick Learner" achievement!'],
    ['streak_milestone', 'Streak Milestone', 'You reached a 7-day study streak!'],
    ['study_streak', 'Study Streak', 'Keep up your amazing study streak!'],
    ['correct_answers_milestone', 'Milestone Reached', 'You answered 1000 questions correctly!'],
    ['quiz_mastered', 'Quiz Mastered', 'You have mastered this quiz topic!'],
];

foreach ($achievementNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 5. ENERGY & PROGRESS (LIGHTNING ICONS)
echo "\n5️⃣ ENERGY & PROGRESS (LIGHTNING ICONS)\n";
echo "-" . str_repeat("-", 50) . "\n";

$energyNotifications = [
    ['energy_boost', 'Energy Boost', 'You gained an energy boost from your performance!'],
    ['power_up', 'Power Up!', 'You powered up your learning abilities!'],
    ['momentum', 'Building Momentum', 'You are building great learning momentum!'],
];

foreach ($energyNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 6. COMPREHENSIVE STREAK SYSTEM
echo "\n6️⃣ COMPREHENSIVE STREAK SYSTEM\n";
echo "-" . str_repeat("-", 50) . "\n";

$streakNotifications = [
    ['streak_reset', 'Streak Reset', 'Your streak has been reset - start fresh!'],
    ['streak_day_1', 'Day 1 Streak', 'You started your learning journey!'],
    ['streak_day_2', 'Day 2 Streak', 'Two days in a row - great start!'],
    ['streak_day_3', 'Day 3 Streak', 'Three days strong!'],
    ['streak_week_1', 'Week 1 Complete', 'You completed your first week!'],
    ['streak_day_10', '10 Day Streak', 'Amazing 10-day streak!'],
    ['streak_week_2', 'Week 2 Complete', 'Two weeks of consistent learning!'],
    ['streak_day_15', '15 Day Streak', 'Halfway to a month!'],
    ['streak_day_21', '21 Day Streak', 'You formed a habit!'],
    ['streak_month_1', 'Month 1 Complete', 'One full month of learning!'],
    ['streak_month_3', '3 Month Streak', 'Quarter year achievement!'],
    ['streak_month_6', '6 Month Streak', 'Half year milestone!'],
    ['streak_year_1', '1 Year Streak', 'Full year of dedication!'],
];

foreach ($streakNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 7. PREMIUM COURSE & WARNINGS
echo "\n7️⃣ PREMIUM COURSE & WARNINGS\n";
echo "-" . str_repeat("-", 50) . "\n";

$premiumNotifications = [
    ['course_mastery', 'Course Mastery', 'You achieved mastery in Premium Cardiology!'],
    ['review_mistakes', 'Review Mistakes', 'Time to review your recent mistakes'],
    ['weak_topic_identified', 'Weak Topic Found', 'We identified areas for improvement'],
    ['skipped_questions_detected', 'Skipped Questions', 'You have some skipped questions to review'],
    ['topic_needs_review', 'Topic Review Needed', 'This topic needs more attention'],
    ['review_reminder', 'Review Reminder', 'Don\'t forget to review your materials'],
];

foreach ($premiumNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 8. PAYMENT & ACCESS
echo "\n8️⃣ PAYMENT & ACCESS\n";
echo "-" . str_repeat("-", 50) . "\n";

$paymentNotifications = [
    ['payment_success', 'Payment Successful', 'Your payment was processed successfully'],
    ['payment_failed', 'Payment Failed', 'Your payment could not be processed'],
    ['course_access_granted', 'Access Granted', 'You now have access to Premium Course'],
    ['course_access_denied', 'Access Denied', 'Premium access required for this content'],
];

foreach ($paymentNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 9. SYSTEM & GENERAL
echo "\n9️⃣ SYSTEM & GENERAL\n";
echo "-" . str_repeat("-", 50) . "\n";

$systemNotifications = [
    ['system_update', 'System Update', 'New features have been added to the platform'],
    ['announcement', 'Important Announcement', 'Check out our new study materials!'],
    ['reminder', 'Study Reminder', 'Time for your daily study session'],
    ['info', 'Information', 'Here\'s some helpful information for you'],
    ['success', 'Success', 'Your action was completed successfully'],
    ['warning', 'Warning', 'Please pay attention to this warning'],
    ['error', 'Error Occurred', 'An error occurred - please try again'],
    ['test', 'Test Notification', 'This is a test notification'],
];

foreach ($systemNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 10. SPECIAL ACHIEVEMENTS
echo "\n🔟 SPECIAL ACHIEVEMENTS\n";
echo "-" . str_repeat("-", 50) . "\n";

$specialNotifications = [
    ['speed_demon', 'Speed Demon', 'You completed that quiz incredibly fast!'],
    ['consistency_king', 'Consistency King', 'Your consistent performance is remarkable!'],
    ['knowledge_master', 'Knowledge Master', 'You have mastered this knowledge area!'],
];

foreach ($specialNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 11. SUBJECT SPECIFIC
echo "\n1️⃣1️⃣ SUBJECT SPECIFIC\n";
echo "-" . str_repeat("-", 50) . "\n";

$subjectNotifications = [
    ['anatomy_mastered', 'Anatomy Mastered', 'You have mastered human anatomy!'],
    ['cardiology_completed', 'Cardiology Complete', 'Cardiology course completed successfully!'],
    ['pharmacology_quiz', 'Pharmacology Quiz', 'New pharmacology quiz available'],
    ['physiology_quiz', 'Physiology Quiz', 'Complete your physiology assessment'],
];

foreach ($subjectNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// 12. CUSTOM TYPES
echo "\n1️⃣2️⃣ CUSTOM TYPES\n";
echo "-" . str_repeat("-", 50) . "\n";

$customNotifications = [
    ['milestone_reached', 'Milestone Reached', 'You reached an important milestone!'],
    ['goal_achieved', 'Goal Achieved', 'Congratulations on achieving your goal!'],
    ['breakthrough', 'Breakthrough Moment', 'You had a learning breakthrough!'],
    ['inspiration', 'Daily Inspiration', 'Here\'s some inspiration for your studies'],
];

foreach ($customNotifications as [$type, $title, $message]) {
    UniversalNotificationService::create($user, $type, $title, $message);
    echo "   ✅ Created: {$type} - {$title}\n";
}

// Get final count and display summary
$finalNotifications = UniversalNotificationService::getNotifications($user, 200);
$totalCount = $finalNotifications->count();

echo "\n🎉 COMPREHENSIVE NOTIFICATION ICONS TEST COMPLETE!\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "📊 SUMMARY:\n";
echo "   • Total notifications created: {$totalCount}\n";
echo "   • User ID: {$user->id} ({$user->name})\n";
echo "   • All notification icons populated and ready for testing\n\n";

echo "🔍 NOTIFICATION CATEGORIES CREATED:\n";
echo "   • Academic & Learning (8 types)\n";
echo "   • Tutor Mode Specific (6 types)\n";
echo "   • Assignment Specific (13 types)\n";
echo "   • Achievements & Milestones (5 types)\n";
echo "   • Energy & Progress (3 types)\n";
echo "   • Comprehensive Streak System (13 types)\n";
echo "   • Premium Course & Warnings (6 types)\n";
echo "   • Payment & Access (4 types)\n";
echo "   • System & General (8 types)\n";
echo "   • Special Achievements (3 types)\n";
echo "   • Subject Specific (4 types)\n";
echo "   • Custom Types (4 types)\n\n";

echo "🔔 TESTING INSTRUCTIONS:\n";
echo "   1. Open your browser and navigate to the application\n";
echo "   2. Login as user ID 1 ({$user->name})\n";
echo "   3. Click the notification bell in the top navigation\n";
echo "   4. You should see all {$totalCount} notifications with distinct icons\n";
echo "   5. Each notification type should have its unique FontAwesome icon\n";
echo "   6. Icons should be colorful and visually distinct\n\n";

echo "✨ All notification icons are now populated for user ID 1!\n";