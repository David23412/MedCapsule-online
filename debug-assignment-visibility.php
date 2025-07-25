<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Assignment;
use App\Models\Topic;
use App\Models\Course;
use App\Models\User;

echo "🔍 ASSIGNMENT VISIBILITY INVESTIGATION\n";
echo "=====================================\n\n";

// Get all assignments
$assignments = Assignment::with(['topic', 'student'])->get();
echo "📊 TOTAL ASSIGNMENTS IN DATABASE: " . $assignments->count() . "\n\n";

foreach ($assignments as $assignment) {
    echo "Assignment ID: {$assignment->id}\n";
    echo "Topic ID: {$assignment->topic_id}\n";
    echo "Student ID: {$assignment->student_id}\n";
    echo "Title: {$assignment->title}\n";
    echo "Status: {$assignment->status}\n";
    echo "Deadline: {$assignment->deadline}\n";
    echo "Created: {$assignment->created_at}\n";
    echo "---\n";
}

echo "\n🔍 SIMULATING CONTROLLER LOGIC\n";
echo "==============================\n";

// Simulate what the controller does
$courseId = 1; // Assuming course ID 1
$userId = 2;   // Student user ID (Tuna)

echo "Course ID: {$courseId}\n";
echo "User ID: {$userId}\n\n";

// Get assignments like the controller does (NEW FIXED VERSION)
$controllerAssignments = Assignment::where('student_id', $userId)
    ->where('course_id', $courseId)
    ->with(['topic', 'completedAttempts' => function($query) use ($userId) {
        $query->where('user_id', $userId)
              ->orderBy('completed_at', 'desc');
    }])
    ->get(); // FIXED: No keyBy('topic_id')

// FIXED: Update overdue assignment statuses
foreach ($controllerAssignments as $assignment) {
    if ($assignment->status === 'active' && \Carbon\Carbon::parse($assignment->deadline)->isPast()) {
        $assignment->update(['status' => 'overdue']);
        $assignment->refresh(); // Refresh to get updated status
    }
}

echo "📊 ASSIGNMENTS FOUND BY CONTROLLER: " . $controllerAssignments->count() . "\n\n";

foreach ($controllerAssignments as $assignment) {
    echo "Assignment ID: {$assignment->id}\n";
    echo "Topic ID: {$assignment->topic_id}\n";
    echo "Title: {$assignment->title}\n";
    echo "Status: {$assignment->status}\n";
    echo "---\n";
}

echo "\n🚨 TESTING keyBy('topic_id') ISSUE\n";
echo "==================================\n";

// Test the keyBy issue
$keyedAssignments = $controllerAssignments->keyBy('topic_id');
echo "📊 ASSIGNMENTS AFTER keyBy('topic_id'): " . $keyedAssignments->count() . "\n\n";

foreach ($keyedAssignments as $topicId => $assignment) {
    echo "Topic ID Key: {$topicId}\n";
    echo "Assignment ID: {$assignment->id}\n";
    echo "Assignment Title: {$assignment->title}\n";
    echo "---\n";
}

echo "\n🔧 TESTING WITHOUT keyBy (CORRECT APPROACH)\n";
echo "==========================================\n";

// Test without keyBy
$nonKeyedAssignments = $controllerAssignments;
echo "📊 ASSIGNMENTS WITHOUT keyBy: " . $nonKeyedAssignments->count() . "\n\n";

foreach ($nonKeyedAssignments as $assignment) {
    echo "Assignment ID: {$assignment->id}\n";
    echo "Topic ID: {$assignment->topic_id}\n";
    echo "Assignment Title: {$assignment->title}\n";
    echo "---\n";
}

echo "\n🎯 DIAGNOSIS COMPLETE\n";
echo "====================\n";

if ($controllerAssignments->count() > $keyedAssignments->count()) {
    echo "❌ PROBLEM CONFIRMED: keyBy('topic_id') is causing assignment loss!\n";
    echo "   Original count: {$controllerAssignments->count()}\n";
    echo "   After keyBy count: {$keyedAssignments->count()}\n";
    echo "   Lost assignments: " . ($controllerAssignments->count() - $keyedAssignments->count()) . "\n";
} else {
    echo "✅ No keyBy issue detected (all assignments have unique topic_ids)\n";
}

echo "\n🔍 CHECKING FOR OVERDUE ASSIGNMENTS\n";
echo "===================================\n";

$now = now();
foreach ($controllerAssignments as $assignment) {
    $deadline = \Carbon\Carbon::parse($assignment->deadline);
    $isOverdue = $deadline->isPast();
    
    echo "Assignment: {$assignment->title}\n";
    echo "Deadline: {$assignment->deadline}\n";
    echo "Current time: {$now}\n";
    echo "Is overdue: " . ($isOverdue ? 'YES' : 'NO') . "\n";
    echo "Status: {$assignment->status}\n";
    echo "---\n";
}

echo "\n✅ INVESTIGATION COMPLETE\n";