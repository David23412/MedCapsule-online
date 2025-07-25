<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚨 EMERGENCY CONTAMINATION INVESTIGATION\n";
echo "========================================\n\n";

echo "PROBLEM: Fixed JavaScript but contamination STILL happening!\n";
echo "CONCLUSION: There are MULTIPLE contamination sources!\n\n";

echo "🔍 POTENTIAL CONTAMINATION SOURCES:\n";
echo "===================================\n";
echo "1. ❌ JavaScript event handler (FIXED but still contaminating)\n";
echo "2. ❓ Backend assignment completion logic\n";
echo "3. ❓ Database triggers or cascading updates\n";
echo "4. ❓ Assignment status update logic\n";
echo "5. ❓ Progress calculation in blade templates\n";
echo "6. ❓ CSS visual contamination (making it LOOK like all are updated)\n\n";

echo "🎯 CRITICAL QUESTIONS TO INVESTIGATE:\n";
echo "=====================================\n";
echo "1. Are assignment statuses being updated in the DATABASE?\n";
echo "2. Is the assignment completion logic updating multiple records?\n";
echo "3. Is there CSS making it APPEAR like all cards are updated?\n";
echo "4. Is the regular topic card getting assignment progress data?\n\n";

echo "🔍 INVESTIGATION PLAN:\n";
echo "======================\n";
echo "1. Check database - are ALL assignment statuses actually 'completed'?\n";
echo "2. Find assignment completion backend logic\n";
echo "3. Check if assignment progress is bleeding into regular topic\n";
echo "4. Look for any other JavaScript or backend contamination\n\n";

echo "✅ STARTING INVESTIGATION...\n";

// Check current database state
echo "\n📊 CURRENT DATABASE STATE:\n";
echo "==========================\n";

$assignments = \App\Models\Assignment::all();
foreach ($assignments as $assignment) {
    echo "Assignment ID: {$assignment->id}\n";
    echo "Title: {$assignment->title}\n";
    echo "Status: {$assignment->status}\n";
    echo "Topic ID: {$assignment->topic_id}\n";
    echo "---\n";
}

echo "\n🔍 CHECKING ASSIGNMENT ATTEMPTS:\n";
echo "================================\n";

$attempts = \App\Models\AssignmentAttempt::with(['assignment'])->get();
echo "Total Assignment Attempts: " . $attempts->count() . "\n\n";

foreach ($attempts as $attempt) {
    echo "Attempt ID: {$attempt->id}\n";
    echo "Assignment: {$attempt->assignment->title}\n";
    echo "Status: {$attempt->status}\n";
    echo "Score: {$attempt->score_percentage}%\n";
    echo "Completed: {$attempt->completed_at}\n";
    echo "---\n";
}

echo "\n🔍 CHECKING TOPIC PROGRESS:\n";
echo "===========================\n";

$topicProgress = \App\Models\UserProgress::where('topic_id', 1)->get();
echo "Topic Progress Records: " . $topicProgress->count() . "\n\n";

foreach ($topicProgress as $progress) {
    echo "User ID: {$progress->user_id}\n";
    echo "Topic ID: {$progress->topic_id}\n";
    echo "Percentage: {$progress->percentage_grade}%\n";
    echo "Updated: {$progress->updated_at}\n";
    echo "---\n";
}

echo "\n🚨 CONTAMINATION ANALYSIS:\n";
echo "==========================\n";

if ($assignments->where('status', 'completed')->count() > 1) {
    echo "❌ BACKEND CONTAMINATION CONFIRMED!\n";
    echo "   Multiple assignments marked as 'completed' in database\n";
    echo "   This is NOT just a frontend/JavaScript issue!\n\n";
} else {
    echo "✅ Database status looks correct\n";
    echo "   Contamination might be visual/frontend only\n\n";
}

if ($topicProgress->count() > 0) {
    echo "🔍 REGULAR TOPIC CONTAMINATION:\n";
    echo "   Regular topic has progress data\n";
    echo "   This might be getting assignment progress\n\n";
}

echo "🎯 NEXT STEPS:\n";
echo "==============\n";
echo "1. Find assignment completion backend logic\n";
echo "2. Check if it's updating multiple assignments\n";
echo "3. Check if assignment completion updates regular topic progress\n";
echo "4. Look for any cascading update logic\n\n";

echo "🚨 EMERGENCY INVESTIGATION COMPLETE\n";