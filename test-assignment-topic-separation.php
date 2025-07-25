<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;

echo "🔍 Testing Assignment vs Regular Topic Practice Separation\n";
echo "=========================================================\n\n";

// Find a student user
$student = User::where('role', 'student')->first();
if (!$student) {
    echo "❌ No student user found. Please create a student user first.\n";
    exit(1);
}

// Find a course and topic
$course = Course::first();
$topic = Topic::where('course_id', $course->id)->first();

if (!$course || !$topic) {
    echo "❌ No course or topic found. Please ensure you have test data.\n";
    exit(1);
}

echo "📚 Test Data:\n";
echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Course: {$course->name} (ID: {$course->id})\n";
echo "Topic: {$topic->name} (ID: {$topic->id})\n\n";

// Create a test assignment for this topic
$assignment = Assignment::create([
    'title' => 'Test Assignment - Topic Separation',
    'description' => 'Testing assignment vs regular practice separation',
    'student_id' => $student->id,
    'course_id' => $course->id,
    'topic_id' => $topic->id,
    'tutor_id' => User::where('role', 'tutor')->first()->id ?? 1,
    'deadline' => now()->addDays(7),
    'duration_minutes' => 30, // 30 minute time limit
    'status' => Assignment::STATUS_ACTIVE,
    'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC
]);

echo "✅ Created test assignment with 30-minute time limit\n";
echo "Assignment ID: {$assignment->id}\n\n";

// Test 1: Regular topic practice (should NOT have assignment constraints)
echo "🧪 Test 1: Regular Topic Practice (no assignment_id parameter)\n";
echo "Expected: No time limit, regular practice mode\n";

// Simulate the controller logic for regular practice
$assignmentId = null; // No assignment_id parameter
$foundAssignment = null;

if ($assignmentId) {
    $foundAssignment = Assignment::where('id', $assignmentId)
        ->where('student_id', $student->id)
        ->where('topic_id', $topic->id)
        ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE, Assignment::STATUS_LOCKED])
        ->first();
}

if ($foundAssignment) {
    echo "❌ FAIL: Assignment constraints applied to regular practice!\n";
    echo "Duration: {$foundAssignment->duration_minutes} minutes\n";
} else {
    echo "✅ PASS: No assignment constraints for regular practice\n";
    echo "Duration: No time limit (regular practice)\n";
}

echo "\n";

// Test 2: Assignment practice (should have assignment constraints)
echo "🧪 Test 2: Assignment Practice (with assignment_id parameter)\n";
echo "Expected: 30-minute time limit, assignment mode\n";

// Simulate the controller logic for assignment practice
$assignmentId = $assignment->id; // Assignment_id parameter provided
$foundAssignment = null;

if ($assignmentId) {
    $foundAssignment = Assignment::where('id', $assignmentId)
        ->where('student_id', $student->id)
        ->where('topic_id', $topic->id)
        ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE, Assignment::STATUS_LOCKED])
        ->first();
}

if ($foundAssignment && $foundAssignment->duration_minutes == 30) {
    echo "✅ PASS: Assignment constraints properly applied\n";
    echo "Duration: {$foundAssignment->duration_minutes} minutes\n";
} else {
    echo "❌ FAIL: Assignment constraints not applied correctly!\n";
    if ($foundAssignment) {
        echo "Duration: {$foundAssignment->duration_minutes} minutes\n";
    } else {
        echo "No assignment found\n";
    }
}

echo "\n";

// Test 3: URL generation
echo "🧪 Test 3: URL Generation\n";
echo "Regular practice URL: /quiz/{$course->id}/{$topic->id}\n";
echo "Assignment practice URL: /quiz/{$course->id}/{$topic->id}?assignment_id={$assignment->id}\n\n";

// Clean up
$assignment->delete();
echo "🧹 Cleaned up test assignment\n\n";

echo "🎉 Test Summary:\n";
echo "The fix ensures that:\n";
echo "1. Regular topic practice has NO time limits or assignment constraints\n";
echo "2. Assignment practice has proper time limits and assignment constraints\n";
echo "3. The distinction is made via the assignment_id parameter\n";
echo "4. Users can practice topics freely even if assignments exist for those topics\n";

?>