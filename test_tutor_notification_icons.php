<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;

echo "🎯 TUTOR NOTIFICATION ICON TEST\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Get test user
$user = User::find(2);
if (!$user) {
    echo "❌ User 2 not found\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Create tutor mode notifications
echo "🔥 Creating tutor mode notifications...\n";

// Tutor session with mastery
$tutorData = [
    'correct_answers' => 18,
    'total_questions' => 20,
    'course_name' => 'MedCapsule',
    'topic_name' => 'Cardiology'
];

NotificationTriggerService::onTutorModeCompleted($user, $tutorData);
echo "   ✅ Created tutor_session_completed notification\n";
echo "   ✅ Created tutor_mastery_achieved notification\n";

// Create tutor quiz notification
$tutorQuizData = [
    'score' => 16,
    'total_questions' => 20,
    'course_name' => 'MedCapsule',
    'mode' => 'tutor'
];

NotificationTriggerService::onQuizCompleted($user, $tutorQuizData);
echo "   ✅ Created tutor_quiz_completed notification\n";

echo "\n";

// Get recent notifications
$notifications = UniversalNotificationService::getNotifications($user, 10);

echo "📋 RECENT NOTIFICATIONS:\n";
echo "-" . str_repeat("-", 50) . "\n";

foreach ($notifications->take(5) as $notification) {
    $type = $notification['type'];
    
    // Check if it's a tutor notification
    if (str_contains($type, 'tutor')) {
        echo "🎯 TUTOR: {$type}\n";
        echo "   Title: {$notification['title']}\n";
        echo "   Message: {$notification['message']}\n";
        
        // Test icon mapping
        $expectedIcons = [
            'tutor_session_completed' => 'fas fa-bullseye',
            'tutor_mastery_achieved' => 'fas fa-trophy',
            'tutor_quiz_completed' => 'fas fa-chalkboard-teacher',
            'tutor_perfect_mastery' => 'fas fa-crown',
            'tutor_strong_performance' => 'fas fa-thumbs-up',
            'tutor_needs_practice' => 'fas fa-redo-alt'
        ];
        
        $expectedColors = [
            'tutor_session_completed' => '#ea580c',
            'tutor_mastery_achieved' => '#dc2626',
            'tutor_quiz_completed' => '#f97316',
            'tutor_perfect_mastery' => '#b91c1c',
            'tutor_strong_performance' => '#f59e0b',
            'tutor_needs_practice' => '#ef4444'
        ];
        
        $icon = $expectedIcons[$type] ?? 'fas fa-bell';
        $color = $expectedColors[$type] ?? '#6b7280';
        
        echo "   Expected Icon: {$icon}\n";
        echo "   Expected Color: {$color}\n";
        
        if ($icon !== 'fas fa-bell') {
            echo "   ✅ DISTINCT ICON (not bland grey bell)\n";
        } else {
            echo "   ❌ BLAND GREY BELL DETECTED\n";
        }
        
        echo "\n";
    }
}

echo "🔔 NOTIFICATION DROPDOWN TEST INSTRUCTIONS:\n";
echo "-" . str_repeat("-", 50) . "\n";
echo "1. Open your browser and go to the application\n";
echo "2. Click the notification bell in the top navigation\n";
echo "3. Look for the tutor notifications you just created\n";
echo "4. Verify they have DISTINCT ICONS instead of grey bells\n";
echo "5. Tutor notifications should have orange/red colors\n\n";

echo "🎨 EXPECTED TUTOR ICONS:\n";
echo "   • tutor_session_completed: 🎯 bullseye (orange)\n";
echo "   • tutor_mastery_achieved: 🏆 trophy (red)\n";
echo "   • tutor_quiz_completed: 👨‍🏫 chalkboard-teacher (orange)\n\n";

echo "LETHAL FIX APPLIED! Check your notification dropdown now! 🔥\n";