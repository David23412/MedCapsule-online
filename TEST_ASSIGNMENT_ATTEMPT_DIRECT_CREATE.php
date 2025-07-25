<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING DIRECT ASSIGNMENTATTEMPT CREATION\n";
echo "============================================\n\n";

echo "🔍 TESTING AssignmentAttempt::create() DIRECTLY:\n";
echo "================================================\n";

$assignment = \App\Models\Assignment::find(2);
$userId = 2;
$percentageGrade = 85.0;

if (!$assignment) {
    echo "❌ Assignment not found!\n";
    exit;
}

echo "Assignment found: {$assignment->title} (ID: {$assignment->id})\n";
echo "User ID: $userId\n";
echo "Percentage Grade: $percentageGrade%\n\n";

// Test data
$testData = [
    'assignment_id' => $assignment->id,
    'student_id' => $userId,
    'topic_id' => $assignment->topic_id,
    'course_id' => $assignment->course_id,
    'total_questions' => 4,
    'correct_answers' => 3,
    'incorrect_answers' => 1,
    'score_percentage' => $percentageGrade,
    'time_taken_seconds' => 120,
    'question_responses' => [
        '1' => 'A',
        '2' => 'B',
        '3' => 'C',
        '4' => 'A'
    ],
    'assignment_mode' => 'complete_topic',
    'assigned_source' => null,
    'status' => \App\Models\AssignmentAttempt::STATUS_COMPLETED,
    'started_at' => now()->subSeconds(120),
    'completed_at' => now(),
    'is_late_submission' => false,
    'notes' => null
];

echo "🧪 TEST 1: Direct AssignmentAttempt::create()\n";
echo "---------------------------------------------\n";

try {
    echo "Creating AssignmentAttempt with data:\n";
    foreach ($testData as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . json_encode($value) . "\n";
        } else {
            echo "  $key: " . ($value ?: 'NULL') . "\n";
        }
    }
    echo "\n";
    
    $attempt = \App\Models\AssignmentAttempt::create($testData);
    
    if ($attempt) {
        echo "✅ SUCCESS: AssignmentAttempt created!\n";
        echo "  ID: {$attempt->id}\n";
        echo "  Assignment ID: {$attempt->assignment_id}\n";
        echo "  Student ID: {$attempt->student_id}\n";
        echo "  Score: {$attempt->score_percentage}%\n";
        echo "  Status: {$attempt->status}\n";
        echo "  Created: {$attempt->created_at}\n";
    } else {
        echo "❌ FAILURE: AssignmentAttempt::create() returned null\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🔍 CHECKING DATABASE:\n";
echo "=====================\n";

$attempts = \App\Models\AssignmentAttempt::where('assignment_id', $assignment->id)->get();
echo "AssignmentAttempt records for Assignment {$assignment->id}: " . $attempts->count() . "\n";

if ($attempts->count() > 0) {
    foreach ($attempts as $attempt) {
        echo "  ID: {$attempt->id} | Score: {$attempt->score_percentage}% | Status: {$attempt->status}\n";
    }
} else {
    echo "  No records found in database\n";
}

echo "\n🧪 TEST 2: Check database table structure\n";
echo "=========================================\n";

try {
    $columns = \DB::select("DESCRIBE assignment_attempts");
    echo "assignment_attempts table columns:\n";
    foreach ($columns as $column) {
        echo "  {$column->Field} ({$column->Type}) - " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "\n";
}

echo "\n✅ DIRECT ASSIGNMENTATTEMPT CREATION TEST COMPLETE\n";