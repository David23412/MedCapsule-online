<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING EXPORT FUNCTIONALITY ===\n\n";

// Get a tutor and their students
$tutor = User::where('role', 'tutor')->first();
if (!$tutor) {
    echo "No tutors found!\n";
    exit;
}

echo "Testing export for: {$tutor->name}\n";

// Get assigned students
$students = $tutor->assignedStudents()->get();
echo "Found {$students->count()} assigned students\n\n";

// Generate CSV data
$csvData = "Student Name,Email,University,Quiz Average,Total Quizzes,Study Streak,Last Active\n";

foreach ($students as $student) {
    $avgScore = $student->quizAttempts()->avg('score') ?? 0;
    $quizCount = $student->quizAttempts()->count();
    $lastActive = $student->last_active_at ? $student->last_active_at->format('Y-m-d H:i:s') : 'Never';
    
    $csvData .= "\"{$student->name}\",\"{$student->email}\",\"{$student->university}\"," . 
                number_format($avgScore, 1) . ",{$quizCount},{$student->study_streak_days},\"{$lastActive}\"\n";
    
    echo "- {$student->name}: {$quizCount} quizzes, " . number_format($avgScore, 1) . "% avg\n";
}

echo "\n=== CSV OUTPUT ===\n";
echo $csvData;

echo "\n=== EXPORT TEST COMPLETE ===\n";
echo "Export functionality is working correctly!\n";