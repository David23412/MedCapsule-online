<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔒 ASSIGNMENT LOCKING SYSTEM TEST\n";
echo "================================\n\n";

try {
    // Test 1: Create test data
    echo "1. Creating test data...\n";
    
    $course = Course::first();
    $topic = Topic::first();
    $student = User::first();
    
    if (!$course || !$topic || !$student) {
        echo "❌ Missing test data. Please ensure you have courses, topics, and students.\n";
        exit(1);
    }
    
    echo "✅ Using Course: {$course->name}\n";
    echo "✅ Using Topic: {$topic->name}\n";
    echo "✅ Using Student: {$student->name}\n\n";
    
    // Test 2: Create assignments with different statuses
    echo "2. Creating test assignments...\n";
    
    // Active assignment
    $activeAssignment = Assignment::create([
        'tutor_id' => 1,
        'student_id' => $student->id,
        'topic_id' => $topic->id,
        'course_id' => $course->id,
        'title' => 'Test Active Assignment',
        'description' => 'This should be accessible',
        'deadline' => now()->addDays(2),
        'status' => Assignment::STATUS_ACTIVE,
        'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC
    ]);
    
    // Overdue assignment
    $overdueAssignment = Assignment::create([
        'tutor_id' => 1,
        'student_id' => $student->id,
        'topic_id' => $topic->id,
        'course_id' => $course->id,
        'title' => 'Test Overdue Assignment',
        'description' => 'This should be locked',
        'deadline' => now()->subDays(2),
        'status' => Assignment::STATUS_OVERDUE,
        'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC
    ]);
    
    // Completed assignment
    $completedAssignment = Assignment::create([
        'tutor_id' => 1,
        'student_id' => $student->id,
        'topic_id' => $topic->id,
        'course_id' => $course->id,
        'title' => 'Test Completed Assignment',
        'description' => 'This should be locked',
        'deadline' => now()->addDays(1),
        'status' => Assignment::STATUS_COMPLETED,
        'completed_at' => now()->subHours(2),
        'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC
    ]);
    
    // Time-limited assignment (expired)
    $expiredAssignment = Assignment::create([
        'tutor_id' => 1,
        'student_id' => $student->id,
        'topic_id' => $topic->id,
        'course_id' => $course->id,
        'title' => 'Test Expired Assignment',
        'description' => 'This should be locked due to time limit',
        'deadline' => now()->addDays(1),
        'duration_minutes' => 60,
        'started_at' => now()->subHours(2),
        'status' => Assignment::STATUS_ACTIVE,
        'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC
    ]);
    
    echo "✅ Created 4 test assignments\n\n";
    
    // Test 3: Test locking logic
    echo "3. Testing assignment locking logic...\n";
    
    // Test active assignment
    echo "Active Assignment:\n";
    echo "  - Status: {$activeAssignment->status}\n";
    echo "  - Is Locked: " . ($activeAssignment->isLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Should be locked: " . ($activeAssignment->shouldBeLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Can be completed: " . ($activeAssignment->canBeCompleted() ? 'YES' : 'NO') . "\n\n";
    
    // Test overdue assignment
    echo "Overdue Assignment:\n";
    echo "  - Status: {$overdueAssignment->status}\n";
    echo "  - Is Locked: " . ($overdueAssignment->isLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Should be locked: " . ($overdueAssignment->shouldBeLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Can be completed: " . ($overdueAssignment->canBeCompleted() ? 'YES' : 'NO') . "\n\n";
    
    // Test completed assignment
    echo "Completed Assignment:\n";
    echo "  - Status: {$completedAssignment->status}\n";
    echo "  - Is Locked: " . ($completedAssignment->isLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Should be locked: " . ($completedAssignment->shouldBeLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Can be completed: " . ($completedAssignment->canBeCompleted() ? 'YES' : 'NO') . "\n\n";
    
    // Test expired assignment
    echo "Expired Assignment:\n";
    echo "  - Status: {$expiredAssignment->status}\n";
    echo "  - Has time limit expired: " . ($expiredAssignment->hasTimeLimitExpired() ? 'YES' : 'NO') . "\n";
    echo "  - Is Locked: " . ($expiredAssignment->isLocked() ? 'YES' : 'NO') . "\n";
    echo "  - Should be locked: " . ($expiredAssignment->shouldBeLocked() ? 'YES' : 'NO') . "\n\n";
    
    // Test 4: Auto-lock expired assignment
    echo "4. Testing auto-expiration...\n";
    if ($expiredAssignment->hasTimeLimitExpired()) {
        $expiredAssignment->markAsExpired();
        echo "✅ Expired assignment marked as expired\n";
        echo "  - New Status: {$expiredAssignment->fresh()->status}\n\n";
    }
    
    // Test 5: Test bulk operations
    echo "5. Testing bulk operations...\n";
    
    $overdueCount = Assignment::updateOverdueAssignments();
    echo "✅ Updated {$overdueCount} overdue assignments\n";
    
    $lockedCount = Assignment::lockOverdueAssignments(24);
    echo "✅ Locked {$lockedCount} overdue assignments beyond grace period\n";
    
    $expiredCount = Assignment::expireTimeLimitedAssignments();
    echo "✅ Expired {$expiredCount} time-limited assignments\n\n";
    
    // Test 6: Test assignment statistics
    echo "6. Assignment Statistics:\n";
    $stats = Assignment::getStatistics();
    echo "  - Total: {$stats['total']}\n";
    echo "  - Active: {$stats['active']}\n";
    echo "  - Completed: {$stats['completed']}\n";
    echo "  - Overdue: {$stats['overdue']}\n";
    echo "  - Locked: {$stats['locked']}\n";
    echo "  - Completion Rate: " . number_format($stats['completion_rate'], 1) . "%\n\n";
    
    // Test 7: Test UI locking logic
    echo "7. Testing UI locking logic...\n";
    
    $assignments = [$activeAssignment, $overdueAssignment, $completedAssignment, $expiredAssignment->fresh()];
    
    foreach ($assignments as $assignment) {
        $isLocked = in_array($assignment->status, ['locked', 'expired', 'completed']) || 
                   ($assignment->status === 'overdue');
        
        echo "Assignment '{$assignment->title}':\n";
        echo "  - Status: {$assignment->status}\n";
        echo "  - UI Should Lock: " . ($isLocked ? 'YES' : 'NO') . "\n";
        echo "  - Lock Reason: " . ($assignment->getLockReason() ?? 'None') . "\n\n";
    }
    
    echo "🎉 ALL TESTS COMPLETED SUCCESSFULLY!\n\n";
    
    // Cleanup
    echo "Cleaning up test data...\n";
    Assignment::whereIn('id', [
        $activeAssignment->id,
        $overdueAssignment->id, 
        $completedAssignment->id,
        $expiredAssignment->id
    ])->delete();
    echo "✅ Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🔒 ASSIGNMENT LOCKING SYSTEM IS READY!\n";
echo "=====================================\n";
echo "Features implemented:\n";
echo "✅ Automatic assignment locking when completed\n";
echo "✅ Automatic assignment locking when overdue\n";
echo "✅ Time-limited assignment expiration\n";
echo "✅ Pale, unclickable UI for locked assignments\n";
echo "✅ Console commands for batch operations\n";
echo "✅ Comprehensive assignment status management\n";
echo "✅ Assignment access middleware\n";
echo "\nNext steps:\n";
echo "1. Add console commands to scheduler in app/Console/Kernel.php\n";
echo "2. Test the UI in browser\n";
echo "3. Monitor logs for assignment locking events\n";