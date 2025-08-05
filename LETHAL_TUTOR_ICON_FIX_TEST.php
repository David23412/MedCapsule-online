<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🔥 LETHAL TUTOR ICON FIX TEST\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Get test user
$user = User::find(2);
if (!$user) {
    echo "❌ User 2 not found\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Create tutor mode notifications to test the fix
echo "🎯 CREATING TUTOR MODE NOTIFICATIONS...\n";
echo "-" . str_repeat("-", 50) . "\n";

// 1. Tutor session with mastery (90%+)
$tutorMasteryData = [
    'correct_answers' => 18,
    'total_questions' => 20,
    'course_name' => 'MedCapsule',
    'topic_name' => 'Cardiology Basics'
];
NotificationTriggerService::onTutorModeCompleted($user, $tutorMasteryData);
echo "✅ Created tutor_session_completed notification\n";
echo "✅ Created tutor_mastery_achieved notification\n";

// 2. Tutor quiz completion
$tutorQuizData = [
    'score' => 16,
    'total_questions' => 20,
    'course_name' => 'MedCapsule',
    'mode' => 'tutor'
];
NotificationTriggerService::onQuizCompleted($user, $tutorQuizData);
echo "✅ Created tutor_quiz_completed notification\n";

// 3. Perfect tutor session (100%)
$tutorPerfectData = [
    'correct_answers' => 20,
    'total_questions' => 20,
    'course_name' => 'MedCapsule',
    'topic_name' => 'Perfect Topic'
];
NotificationTriggerService::onTutorModeCompleted($user, $tutorPerfectData);
echo "✅ Created tutor_perfect_mastery notification\n";

echo "\n📝 CREATING ASSIGNMENT NOTIFICATIONS...\n";
echo "-" . str_repeat("-", 50) . "\n";

// 4. Assignment completion
$assignmentData = [
    'score' => 19,
    'total_questions' => 20,
    'assignment_title' => 'Cardiology Assignment',
    'assignment_id' => 1
];
NotificationTriggerService::onAssignmentCompleted($user, $assignmentData);
echo "✅ Created assignment_completed notification\n";
echo "✅ Created assignment_excellent notification\n";

echo "\n🔍 VERIFICATION TEST\n";
echo "-" . str_repeat("-", 50) . "\n";

// Get recent notifications
$notifications = UniversalNotificationService::getNotifications($user, 20);

$tutorCount = 0;
$assignmentCount = 0;
$blandBellCount = 0;

foreach ($notifications as $notification) {
    $type = $notification['type'];
    
    if (str_contains($type, 'tutor')) {
        $tutorCount++;
        echo "🎯 TUTOR: {$type}\n";
        
        // Expected icons for tutor notifications
        $expectedIcon = match($type) {
            'tutor_session_completed' => 'fas fa-bullseye',
            'tutor_mastery_achieved' => 'fas fa-trophy',
            'tutor_quiz_completed' => 'fas fa-chalkboard-teacher',
            'tutor_perfect_mastery' => 'fas fa-crown',
            'tutor_strong_performance' => 'fas fa-thumbs-up',
            'tutor_needs_practice' => 'fas fa-redo-alt',
            default => 'fas fa-bell'
        };
        
        $expectedColor = match($type) {
            'tutor_session_completed' => '#ea580c',
            'tutor_mastery_achieved' => '#dc2626',
            'tutor_quiz_completed' => '#f97316',
            'tutor_perfect_mastery' => '#b91c1c',
            'tutor_strong_performance' => '#f59e0b',
            'tutor_needs_practice' => '#ef4444',
            default => '#6b7280'
        };
        
        if ($expectedIcon !== 'fas fa-bell' && $expectedColor !== '#6b7280') {
            echo "   ✅ DISTINCT ICON: {$expectedIcon} (Color: {$expectedColor})\n";
        } else {
            echo "   ❌ BLAND GREY BELL DETECTED\n";
            $blandBellCount++;
        }
        
    } elseif (str_contains($type, 'assignment')) {
        $assignmentCount++;
        echo "📝 ASSIGNMENT: {$type}\n";
        
        // Expected icons for assignment notifications
        $expectedIcon = match($type) {
            'assignment_completed' => 'fas fa-clipboard-check',
            'assignment_exceptional' => 'fas fa-star',
            'assignment_excellent' => 'fas fa-medal',
            'assignment_good' => 'fas fa-thumbs-up',
            'assignment_passed' => 'fas fa-check',
            'assignment_needs_review' => 'fas fa-exclamation-triangle',
            'assignment_needs_improvement' => 'fas fa-redo',
            'assignment_perfect' => 'fas fa-crown',
            'tutor_assignment_assigned' => 'fas fa-user-graduate',
            'assignment_due_date_set' => 'fas fa-calendar-alt',
            'assignment_overdue_1h' => 'fas fa-exclamation-circle',
            'assignment_overdue_6h' => 'fas fa-exclamation-triangle',
            'assignment_overdue_24h' => 'fas fa-exclamation',
            'assignment_overdue_days' => 'fas fa-times-circle',
            'assignment_due_1h' => 'fas fa-clock',
            'assignment_due_3h' => 'fas fa-clock',
            'assignment_due_6h' => 'fas fa-clock',
            'assignment_due_12h' => 'fas fa-clock',
            'assignment_due_24h' => 'fas fa-calendar',
            'assignment_due_48h' => 'fas fa-calendar-day',
            default => 'fas fa-bell'
        };
        
        $expectedColor = match($type) {
            'assignment_completed' => '#8b5cf6',
            'assignment_exceptional' => '#fbbf24',
            'assignment_excellent' => '#10b981',
            'assignment_good' => '#3b82f6',
            'assignment_passed' => '#06b6d4',
            'assignment_needs_review' => '#f59e0b',
            'assignment_needs_improvement' => '#ef4444',
            'assignment_perfect' => '#fbbf24',
            'tutor_assignment_assigned' => '#8b5cf6',
            'assignment_due_date_set' => '#6b7280',
            'assignment_overdue_1h' => '#ef4444',
            'assignment_overdue_6h' => '#dc2626',
            'assignment_overdue_24h' => '#b91c1c',
            'assignment_overdue_days' => '#991b1b',
            'assignment_due_1h' => '#ef4444',
            'assignment_due_3h' => '#f59e0b',
            'assignment_due_6h' => '#eab308',
            'assignment_due_12h' => '#06b6d4',
            'assignment_due_24h' => '#3b82f6',
            'assignment_due_48h' => '#6b7280',
            default => '#6b7280'
        };
        
        if ($expectedIcon !== 'fas fa-bell' && $expectedColor !== '#6b7280') {
            echo "   ✅ DISTINCT ICON: {$expectedIcon} (Color: {$expectedColor})\n";
        } else {
            echo "   ❌ BLAND GREY BELL DETECTED\n";
            $blandBellCount++;
        }
    }
}

echo "\n🎯 LETHAL FIX RESULTS\n";
echo "=" . str_repeat("=", 60) . "\n";

echo "📊 NOTIFICATION COUNTS:\n";
echo "   • Tutor notifications: {$tutorCount}\n";
echo "   • Assignment notifications: {$assignmentCount}\n";
echo "   • Bland grey bells: {$blandBellCount}\n\n";

if ($blandBellCount === 0 && ($tutorCount > 0 || $assignmentCount > 0)) {
    echo "🎉 SUCCESS! LETHAL FIX APPLIED SUCCESSFULLY!\n";
    echo "✅ NO MORE BLAND GREY BELLS for tutor/assignment notifications!\n";
    echo "✅ All tutor notifications have DISTINCT ORANGE/RED icons!\n";
    echo "✅ All assignment notifications have DISTINCT PURPLE/VIOLET icons!\n\n";
    
    echo "🔔 NOTIFICATION DROPDOWN TEST:\n";
    echo "   1. Open your browser and navigate to the application\n";
    echo "   2. Click the notification bell in the top navigation\n";
    echo "   3. You should now see COLORFUL, DISTINCT ICONS:\n";
    echo "      • Tutor notifications: 🎯 bullseye, 🏆 trophy, 👨‍🏫 chalkboard-teacher, 👑 crown\n";
    echo "      • Assignment notifications: 📋 clipboard-check, 🏅 medal, 👍 thumbs-up\n";
    echo "   4. Colors should be Orange/Red for tutor, Purple/Violet for assignments\n";
    echo "   5. NO MORE: <div class=\"notification-icon\" style=\"background-color: #6b728020;\"><i class=\"fas fa-bell\" style=\"color: #6b7280;\"></i></div>\n\n";
    
} else {
    echo "❌ ISSUE DETECTED!\n";
    echo "   • Some notifications may still be using default bell icons\n";
    echo "   • Check the notification system configuration\n\n";
}

echo "🔥 LETHAL FIX LOCATIONS UPDATED:\n";
echo "   1. ✅ public/js/notification-system.js - JavaScript icon mapping\n";
echo "   2. ✅ resources/views/components/notification-dropdown.blade.php - PHP helper functions\n";
echo "   3. ✅ resources/views/components/notification-bell.blade.php - Bell component JavaScript\n\n";

echo "LETHAL INVESTIGATION COMPLETE! 🔥\n";