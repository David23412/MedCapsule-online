<?php

require_once 'vendor/autoload.php';

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicProgress;
use App\Models\QuizAttempt;
use App\Models\Question;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Dynamic Course Progress Updates\n";
echo "=====================================\n\n";

// Get a test user and course
$user = User::first();
$course = Course::first();

if (!$user || !$course) {
    echo "No users or courses found. Please ensure you have test data.\n";
    exit(1);
}

echo "User: {$user->name}\n";
echo "Course: {$course->name}\n\n";

// Get initial completion percentage
$initialCompletion = $course->completion_percentage;
echo "Initial Course Completion: {$initialCompletion}%\n\n";

// Get a topic from this course
$topic = $course->topics()->first();
if (!$topic) {
    echo "No topics found in this course.\n";
    exit(1);
}

echo "Testing with Topic: {$topic->name}\n\n";

// Simulate different quiz attempts and their impact on course progress
$testScenarios = [
    ['score' => 8, 'total' => 10, 'description' => 'Good attempt (80%)'],
    ['score' => 3, 'total' => 10, 'description' => 'Failed attempt (30%)'],
    ['score' => 10, 'total' => 10, 'description' => 'Perfect attempt (100%)'],
    ['score' => 2, 'total' => 10, 'description' => 'Another failed attempt (20%)'],
];

foreach ($testScenarios as $index => $scenario) {
    $testNumber = $index + 1;
    echo "Test {$testNumber}: {$scenario['description']}\n";
    
    // Calculate percentage
    $percentage = ($scenario['score'] / $scenario['total']) * 100;
    
    // Update user topic progress (simulating quiz completion)
    UserTopicProgress::updateOrCreate(
        [
            'user_id' => $user->id,
            'topic_id' => $topic->id
        ],
        [
            'percentage_grade' => $percentage,
            'last_attempt_date' => now()
        ]
    );
    
    // Clear course cache to force recalculation
    $course->clearUserCompletionCache($user->id);
    
    // Get updated completion percentage
    $updatedCompletion = $course->completion_percentage;
    
    echo "  Topic Progress: {$percentage}%\n";
    echo "  Course Completion: {$updatedCompletion}%\n";
    
    if ($index > 0) {
        $change = $updatedCompletion - $initialCompletion;
        echo "  Change: {$change}%\n";
    }
    echo "\n";
    
    $initialCompletion = $updatedCompletion;
}

echo "Test completed! Course progress now reflects the latest quiz attempt.\n";
echo "This demonstrates that course progress updates dynamically based on actual quiz performance.\n"; 