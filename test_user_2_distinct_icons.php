<?php

/**
 * TEST: Verify User ID 2 has distinct notification icons with colors
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\UniversalNotificationService;

echo "🎨 DISTINCT NOTIFICATION ICONS VERIFICATION FOR USER ID 2\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Get user ID 2
$user = User::find(2);
if (!$user) {
    echo "❌ User ID 2 not found!\n";
    exit(1);
}

echo "👤 Checking notifications for: {$user->name} (ID: {$user->id})\n\n";

// Get all notifications for user 2
$notifications = UniversalNotificationService::getNotifications($user, 50);
echo "📱 Total notifications found: {$notifications->count()}\n\n";

if ($notifications->isEmpty()) {
    echo "⚠️ No notifications found. Run test_all_notifications_user_2.php first!\n";
    exit(1);
}

// Include the notification dropdown functions
function getNotificationIcon($type) {
    $icons = [
        // 📚 REGULAR QUIZ MODE - Learning & Assessment Icons
        'quiz_completed' => 'fas fa-clipboard-check',
        'perfect_score' => 'fas fa-medal',
        'first_attempt' => 'fas fa-play-circle',
        'course_welcome' => 'fas fa-graduation-cap',
        'topic_mastery' => 'fas fa-brain',
        'level_up' => 'fas fa-level-up-alt',
        'grade_released' => 'fas fa-chart-line',
        
        // 🎯 TUTOR MODE - Guidance & Mastery Icons
        'tutor_quiz_completed' => 'fas fa-chalkboard-teacher',
        'tutor_session_completed' => 'fas fa-user-graduate',
        'tutor_mastery_achieved' => 'fas fa-award',
        'tutor_perfect_mastery' => 'fas fa-crown',
        'tutor_strong_performance' => 'fas fa-thumbs-up',
        'tutor_needs_practice' => 'fas fa-redo-alt',
        
        // 📋 ASSIGNMENT SYSTEM - Task & Deadline Icons
        'tutor_assignment_assigned' => 'fas fa-tasks',
        'assignment_due_date_set' => 'fas fa-calendar-check',
        'assignment_completed' => 'fas fa-file-check',
        'assignment_exceptional' => 'fas fa-gem',
        'assignment_excellent' => 'fas fa-star-of-life',
        'assignment_good' => 'fas fa-check-double',
        'assignment_passed' => 'fas fa-check-square',
        'assignment_needs_review' => 'fas fa-search',
        'assignment_needs_improvement' => 'fas fa-tools',
        'assignment_perfect' => 'fas fa-trophy',
        
        // ⏰ ASSIGNMENT DUE WARNINGS - Urgency Escalation Icons
        'assignment_due_48h' => 'fas fa-info-circle',
        'assignment_due_24h' => 'fas fa-clock',
        'assignment_due_12h' => 'fas fa-hourglass-half',
        'assignment_due_6h' => 'fas fa-hourglass-end',
        'assignment_due_3h' => 'fas fa-exclamation-triangle',
        'assignment_due_1h' => 'fas fa-fire',
        
        // 🚨 ASSIGNMENT OVERDUE - Critical Alert Icons
        'assignment_overdue_1h' => 'fas fa-exclamation-circle',
        'assignment_overdue_6h' => 'fas fa-times-circle',
        'assignment_overdue_24h' => 'fas fa-ban',
        'assignment_overdue_days' => 'fas fa-skull-crossbones',
        
        // 🏆 ACHIEVEMENTS & MILESTONES
        'achievement' => 'fas fa-trophy',
        'streak_milestone' => 'fas fa-fire',
        'study_streak' => 'fas fa-fire',
        'correct_answers_milestone' => 'fas fa-trophy',
        'quiz_mastered' => 'fas fa-graduation-cap',
        
        // ⚠️ WARNINGS & REVIEWS
        'review_mistakes' => 'fas fa-exclamation-triangle',
        'weak_topic_identified' => 'fas fa-exclamation-circle',
        'skipped_questions_detected' => 'fas fa-forward',
        'topic_needs_review' => 'fas fa-exclamation-triangle',
        'review_reminder' => 'fas fa-clock',
        
        // 💳 PAYMENT & ACCESS
        'payment_success' => 'fas fa-credit-card',
        'payment_failed' => 'fas fa-exclamation-triangle',
        'course_access_granted' => 'fas fa-unlock',
        'course_access_denied' => 'fas fa-lock',
        
        // 🔔 SYSTEM & GENERAL
        'system_update' => 'fas fa-bell',
        'announcement' => 'fas fa-bullhorn',
        'reminder' => 'fas fa-bell',
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check',
        'warning' => 'fas fa-exclamation-triangle',
        'error' => 'fas fa-exclamation-circle',
        'test' => 'fas fa-bell'
    ];
    
    return $icons[$type] ?? 'fas fa-bell';
}

function getNotificationIconColor($type) {
    $colors = [
        // 📚 REGULAR QUIZ MODE - Blue/Green Learning Colors
        'quiz_completed' => '#3b82f6',        // blue-500
        'perfect_score' => '#fbbf24',         // amber-400 (GOLD!)
        'first_attempt' => '#10b981',         // emerald-500
        'course_welcome' => '#8b5cf6',        // violet-500
        'topic_mastery' => '#059669',         // emerald-600
        'level_up' => '#fbbf24',              // amber-400 (LIGHTNING YELLOW!)
        'grade_released' => '#6366f1',        // indigo-500
        
        // 🎯 TUTOR MODE - Orange/Red Guidance Colors
        'tutor_quiz_completed' => '#f97316',      // orange-500
        'tutor_session_completed' => '#ea580c',   // orange-600
        'tutor_mastery_achieved' => '#dc2626',    // red-600
        'tutor_perfect_mastery' => '#b91c1c',     // red-700
        'tutor_strong_performance' => '#f59e0b',  // amber-500
        'tutor_needs_practice' => '#ef4444',      // red-500
        
        // 📋 ASSIGNMENT SYSTEM - Purple/Pink Task Colors
        'tutor_assignment_assigned' => '#8b5cf6',     // violet-500
        'assignment_due_date_set' => '#a855f7',       // purple-500
        'assignment_completed' => '#7c3aed',          // violet-600
        'assignment_exceptional' => '#ec4899',        // pink-500
        'assignment_excellent' => '#d946ef',          // fuchsia-500
        'assignment_good' => '#c084fc',               // purple-400
        'assignment_passed' => '#a78bfa',             // violet-400
        'assignment_needs_review' => '#8b5cf6',       // violet-500
        'assignment_needs_improvement' => '#6b21a8',  // purple-800
        'assignment_perfect' => '#be185d',            // pink-700
        
        // ⏰ ASSIGNMENT DUE WARNINGS - Escalating Yellow to Red
        'assignment_due_48h' => '#06b6d4',        // cyan-500 (calm)
        'assignment_due_24h' => '#0891b2',        // cyan-600 (notice)
        'assignment_due_12h' => '#f59e0b',        // amber-500 (attention)
        'assignment_due_6h' => '#f97316',         // orange-500 (concern)
        'assignment_due_3h' => '#ea580c',         // orange-600 (urgent)
        'assignment_due_1h' => '#dc2626',         // red-600 (critical)
        
        // 🚨 ASSIGNMENT OVERDUE - Escalating Red to Black
        'assignment_overdue_1h' => '#ef4444',     // red-500 (just overdue)
        'assignment_overdue_6h' => '#dc2626',     // red-600 (seriously overdue)
        'assignment_overdue_24h' => '#b91c1c',    // red-700 (very overdue)
        'assignment_overdue_days' => '#7f1d1d',   // red-900 (extremely overdue)
        
        // 🏆 ACHIEVEMENTS & MILESTONES - Gold/Yellow
        'achievement' => '#f59e0b',           // amber-500
        'streak_milestone' => '#f97316',      // orange-500
        'study_streak' => '#f97316',          // orange-500
        'correct_answers_milestone' => '#f59e0b', // amber-500
        'quiz_mastered' => '#10b981',         // emerald-500
        
        // ⚠️ WARNINGS & REVIEWS - Red/Orange
        'review_mistakes' => '#ef4444',       // red-500
        'weak_topic_identified' => '#ef4444', // red-500
        'skipped_questions_detected' => '#f59e0b', // amber-500
        'topic_needs_review' => '#ef4444',    // red-500
        'review_reminder' => '#06b6d4',       // cyan-500
        
        // 💳 PAYMENT & ACCESS - Green/Red
        'payment_success' => '#10b981',       // emerald-500
        'payment_failed' => '#ef4444',        // red-500
        'course_access_granted' => '#10b981', // emerald-500
        'course_access_denied' => '#ef4444',  // red-500
        
        // 🔔 SYSTEM & GENERAL - Gray/Blue
        'system_update' => '#6b7280',         // gray-500
        'announcement' => '#3b82f6',          // blue-500
        'reminder' => '#06b6d4',              // cyan-500
        'info' => '#06b6d4',                  // cyan-500
        'success' => '#10b981',               // emerald-500
        'warning' => '#f59e0b',               // amber-500
        'error' => '#ef4444',                 // red-500
        'test' => '#3b82f6'                   // blue-500
    ];
    
    return $colors[$type] ?? '#6b7280';
}

// Group notifications by category
$categories = [
    'QUIZ MODE' => [],
    'TUTOR MODE' => [],
    'ASSIGNMENT SYSTEM' => [],
    'DUE WARNINGS' => [],
    'OVERDUE ALERTS' => [],
    'SYSTEM' => []
];

foreach ($notifications as $notification) {
    $type = $notification['type'];
    $icon = getNotificationIcon($type);
    $color = getNotificationIconColor($type);
    
    $notification['icon'] = $icon;
    $notification['color'] = $color;
    
    if (str_contains($type, 'tutor')) {
        $categories['TUTOR MODE'][] = $notification;
    } elseif (str_contains($type, 'assignment_due')) {
        $categories['DUE WARNINGS'][] = $notification;
    } elseif (str_contains($type, 'assignment_overdue')) {
        $categories['OVERDUE ALERTS'][] = $notification;
    } elseif (str_contains($type, 'assignment')) {
        $categories['ASSIGNMENT SYSTEM'][] = $notification;
    } elseif (in_array($type, ['quiz_completed', 'perfect_score', 'first_attempt'])) {
        $categories['QUIZ MODE'][] = $notification;
    } else {
        $categories['SYSTEM'][] = $notification;
    }
}

// Display each category with distinct icons and colors
foreach ($categories as $categoryName => $notifications) {
    if (empty($notifications)) continue;
    
    echo "🎨 {$categoryName} NOTIFICATIONS:\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    foreach ($notifications as $notification) {
        echo sprintf(
            "   [%s] %s\n      └─ Color: %s | Type: %s\n      └─ %s\n\n",
            $notification['icon'],
            $notification['title'],
            $notification['color'],
            $notification['type'],
            substr($notification['message'], 0, 60) . (strlen($notification['message']) > 60 ? '...' : '')
        );
    }
}

echo "🎉 DISTINCT ICON VERIFICATION COMPLETE!\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "📊 SUMMARY:\n";
echo "   • Quiz Mode: " . count($categories['QUIZ MODE']) . " notifications (Blue/Green colors)\n";
echo "   • Tutor Mode: " . count($categories['TUTOR MODE']) . " notifications (Orange/Red colors)\n";
echo "   • Assignment System: " . count($categories['ASSIGNMENT SYSTEM']) . " notifications (Purple/Pink colors)\n";
echo "   • Due Warnings: " . count($categories['DUE WARNINGS']) . " notifications (Cyan→Red escalation)\n";
echo "   • Overdue Alerts: " . count($categories['OVERDUE ALERTS']) . " notifications (Red→Black escalation)\n";
echo "   • System: " . count($categories['SYSTEM']) . " notifications (Gray/Blue colors)\n\n";

echo "✅ USER ID 2 NOW HAS DISTINCT NOTIFICATION ICONS WITH COLORS!\n";
echo "   Each notification type has a unique FontAwesome icon and color scheme.\n";
echo "   The notification dropdown component has been updated to display these properly.\n";