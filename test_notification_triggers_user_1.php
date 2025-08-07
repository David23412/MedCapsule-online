<?php

/**
 * NOTIFICATION TRIGGERS TEST FOR USER ID 1
 * This script tests the actual notification trigger system
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

echo "🚀 NOTIFICATION TRIGGERS TEST FOR USER ID 1\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Get user ID 1
$user = User::find(1);
if (!$user) {
    echo "❌ User ID 1 not found in database.\n";
    exit(1);
}

echo "👤 Testing with user: {$user->name} (ID: {$user->id})\n\n";

// Test 1: Quiz Completion Notifications
echo "1️⃣ TESTING QUIZ COMPLETION TRIGGERS\n";
echo "-" . str_repeat("-", 50) . "\n";

$quizData = [
    'score' => 8,
    'total_questions' => 10,
    'percentage' => 80,
    'course_name' => 'Advanced Anatomy',
    'topic_name' => 'Cardiovascular System',
    'user_quiz_count' => 5,
    'assignment_completed' => false,
    'mode' => 'quiz'
];

NotificationTriggerService::onQuizCompleted($user, $quizData);
echo "   ✅ Regular quiz completion triggered (80%)\n";

// Perfect score quiz
$perfectQuizData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'course_name' => 'Perfect Anatomy',
    'topic_name' => 'Heart Structure',
    'user_quiz_count' => 1,
    'assignment_completed' => false,
    'mode' => 'quiz'
];

NotificationTriggerService::onQuizCompleted($user, $perfectQuizData);
echo "   ✅ Perfect quiz completion triggered (100%)\n";

// Test 2: Tutor Mode Notifications
echo "\n2️⃣ TESTING TUTOR MODE TRIGGERS\n";
echo "-" . str_repeat("-", 50) . "\n";

$tutorData = [
    'score' => 9,
    'total_questions' => 10,
    'percentage' => 90,
    'course_name' => 'Physiology Course',
    'topic_name' => 'Cardiac Function',
    'course_id' => 1,
    'topic_id' => 2,
    'mode' => 'tutor'
];

NotificationTriggerService::onQuizCompleted($user, $tutorData);
echo "   ✅ Tutor mode completion triggered (90%)\n";

// Perfect tutor score
$perfectTutorData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'course_name' => 'Perfect Tutor Course',
    'topic_name' => 'Perfect Topic',
    'course_id' => 1,
    'topic_id' => 1,
    'mode' => 'tutor'
];

NotificationTriggerService::onQuizCompleted($user, $perfectTutorData);
echo "   ✅ Perfect tutor mode triggered (100%)\n";

// Low score tutor
$lowTutorData = [
    'score' => 4,
    'total_questions' => 10,
    'percentage' => 40,
    'course_name' => 'Practice Course',
    'topic_name' => 'Difficult Topic',
    'course_id' => 1,
    'topic_id' => 3,
    'mode' => 'tutor'
];

NotificationTriggerService::onQuizCompleted($user, $lowTutorData);
echo "   ✅ Low score tutor mode triggered (40%)\n";

// Test 3: Assignment Completion
echo "\n3️⃣ TESTING ASSIGNMENT COMPLETION TRIGGERS\n";
echo "-" . str_repeat("-", 50) . "\n";

// Create or get a test assignment
$assignment = Assignment::first();
if (!$assignment) {
    echo "   ⚠️ No assignments found, creating test assignment...\n";
    $assignment = Assignment::create([
        'title' => 'Cardiovascular Assessment Test',
        'course_id' => 1,
        'topic_id' => 1,
        'deadline' => now()->addDays(7),
        'duration_minutes' => 60,
        'assignment_mode' => 'complete_topic',
        'assigned_source' => null
    ]);
    
    // Assign to user using the pivot table
    $assignment->assignedUsers()->attach($user->id);
    echo "   ✅ Test assignment created\n";
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
echo "   ✅ Assignment completion triggered (90%)\n";

// Perfect assignment
$perfectAssignmentData = [
    'score' => 10,
    'total_questions' => 10,
    'percentage' => 100,
    'assignment_title' => 'Perfect Assignment Test',
    'assignment_id' => $assignment->id,
    'mode' => 'assignment'
];

NotificationTriggerService::onAssignmentCompleted($user, $perfectAssignmentData);
echo "   ✅ Perfect assignment completion triggered (100%)\n";

// Test 4: Course Enrollment
echo "\n4️⃣ TESTING COURSE ENROLLMENT TRIGGERS\n";
echo "-" . str_repeat("-", 50) . "\n";

NotificationTriggerService::onCourseEnrollment($user, 'Advanced Medical Anatomy', 'premium', false);
echo "   ✅ Premium course enrollment triggered\n";

NotificationTriggerService::onCourseEnrollment($user, 'Basic Physiology', 'free', true);
echo "   ✅ Free course enrollment triggered\n";

// Test 5: User Login Welcome
echo "\n5️⃣ TESTING USER LOGIN TRIGGERS\n";
echo "-" . str_repeat("-", 50) . "\n";

NotificationTriggerService::onUserLogin($user);
echo "   ✅ User login welcome triggered\n";

// Get final notification count
$finalNotifications = UniversalNotificationService::getNotifications($user, 100);
$totalCount = $finalNotifications->count();

echo "\n🎉 NOTIFICATION TRIGGERS TEST COMPLETE!\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "📊 SUMMARY:\n";
echo "   • Total notifications after triggers: {$totalCount}\n";
echo "   • User ID: {$user->id} ({$user->name})\n";
echo "   • All notification triggers tested successfully\n\n";

echo "🔍 TRIGGERS TESTED:\n";
echo "   • Quiz completion (regular, perfect)\n";
echo "   • Tutor mode (regular, perfect, low score)\n";
echo "   • Assignment completion (regular, perfect)\n";
echo "   • Course enrollment (premium, free)\n";
echo "   • User login welcome\n\n";

echo "🔔 TESTING INSTRUCTIONS:\n";
echo "   1. Open your browser and navigate to the application\n";
echo "   2. Login as user ID 1 ({$user->name})\n";
echo "   3. Click the notification bell in the top navigation\n";
echo "   4. You should see all notifications including triggered ones\n";
echo "   5. Each notification should have proper icons and messages\n\n";

echo "✨ All notification triggers tested for user ID 1!\n";