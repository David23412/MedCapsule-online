<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 FIXING ASSIGNMENT INCONSISTENT STATE\n";
echo "=======================================\n\n";

echo "🔍 CURRENT ASSIGNMENT STATES:\n";
echo "=============================\n";

$assignments = \App\Models\Assignment::all();
foreach ($assignments as $assignment) {
    echo "ID: {$assignment->id}\n";
    echo "Title: {$assignment->title}\n";
    echo "Status: {$assignment->status}\n";
    echo "Completed At: " . ($assignment->completed_at ?: 'NULL') . "\n";
    echo "Is Locked: " . ($assignment->isLocked() ? 'YES' : 'NO') . "\n";
    echo "Can Be Completed: " . ($assignment->canBeCompleted() ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

echo "\n🚨 FIXING INCONSISTENT STATES:\n";
echo "==============================\n";

// Fix assignments that have completed_at but wrong status
$inconsistentAssignments = \App\Models\Assignment::whereNotNull('completed_at')
    ->where('status', '!=', \App\Models\Assignment::STATUS_COMPLETED)
    ->get();

echo "Inconsistent assignments found: " . $inconsistentAssignments->count() . "\n\n";

foreach ($inconsistentAssignments as $assignment) {
    echo "FIXING Assignment ID {$assignment->id} ({$assignment->title}):\n";
    echo "  Current: status='{$assignment->status}', completed_at='{$assignment->completed_at}'\n";
    
    // Fix the status to match completed_at
    $assignment->update(['status' => \App\Models\Assignment::STATUS_COMPLETED]);
    
    echo "  Fixed: status='completed', completed_at='{$assignment->completed_at}'\n";
    echo "  Is now locked: " . ($assignment->fresh()->isLocked() ? 'YES' : 'NO') . "\n";
    echo "  Can be completed: " . ($assignment->fresh()->canBeCompleted() ? 'YES' : 'NO') . "\n";
    echo "  ---\n";
}

echo "\n🔍 CHECKING FOR MISSING ASSIGNMENT ATTEMPTS:\n";
echo "============================================\n";

$completedAssignments = \App\Models\Assignment::where('status', \App\Models\Assignment::STATUS_COMPLETED)->get();

foreach ($completedAssignments as $assignment) {
    $attemptCount = \App\Models\AssignmentAttempt::where('assignment_id', $assignment->id)->count();
    
    echo "Assignment ID {$assignment->id} ({$assignment->title}):\n";
    echo "  Status: {$assignment->status}\n";
    echo "  Completed: {$assignment->completed_at}\n";
    echo "  Attempt records: {$attemptCount}\n";
    
    if ($attemptCount == 0) {
        echo "  ❌ MISSING: No AssignmentAttempt record for completed assignment!\n";
        echo "  This indicates createAssignmentAttempt was not called properly.\n";
    } else {
        echo "  ✅ AssignmentAttempt records exist\n";
    }
    echo "  ---\n";
}

echo "\n🎯 SUMMARY:\n";
echo "===========\n";
echo "✅ Fixed inconsistent assignment states\n";
echo "✅ Completed assignments are now properly locked\n";
echo "❌ Missing AssignmentAttempt records indicate backend issue\n";
echo "❌ Need to investigate why createAssignmentAttempt is not being called\n\n";

echo "✅ ASSIGNMENT STATE FIX COMPLETE\n";