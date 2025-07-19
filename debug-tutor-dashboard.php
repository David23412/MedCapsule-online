<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUGGING TUTOR DASHBOARD ISSUE ===\n\n";

// Check current user (simulate login)
$tutor = User::where('email', 'tutor@medcapsule.com')->first();
if (!$tutor) {
    echo "❌ Tutor not found!\n";
    exit;
}

echo "✅ Found tutor: {$tutor->name} (ID: {$tutor->id})\n";
echo "   Role: {$tutor->role}\n";
echo "   Is Admin: " . ($tutor->isAdmin() ? 'Yes' : 'No') . "\n";
echo "   Is Tutor: " . ($tutor->isTutor() ? 'Yes' : 'No') . "\n\n";

// Check User constants
echo "User Role Constants:\n";
echo "   ROLE_STUDENT: " . User::ROLE_STUDENT . "\n";
echo "   ROLE_TUTOR: " . User::ROLE_TUTOR . "\n";
echo "   ROLE_ADMIN: " . User::ROLE_ADMIN . "\n\n";

// Check all students
$allStudents = User::where('role', User::ROLE_STUDENT)->get();
echo "📊 Total students in database: {$allStudents->count()}\n";
foreach ($allStudents as $student) {
    echo "   - {$student->name} (ID: {$student->id}, Role: {$student->role})\n";
}
echo "\n";

// Check tutor-student relationships
$relationships = DB::table('tutor_student_relationships')->get();
echo "🔗 Total relationships: {$relationships->count()}\n";
foreach ($relationships as $rel) {
    $tutorName = User::find($rel->tutor_id)->name ?? 'Unknown';
    $studentName = User::find($rel->student_id)->name ?? 'Unknown';
    echo "   - Tutor {$rel->tutor_id} ({$tutorName}) → Student {$rel->student_id} ({$studentName})\n";
}
echo "\n";

// Test the assignedStudents relationship
echo "🎯 Testing assignedStudents relationship for tutor {$tutor->id}:\n";
$assignedStudents = $tutor->assignedStudents()->get();
echo "   Found {$assignedStudents->count()} assigned students:\n";
foreach ($assignedStudents as $student) {
    echo "   - {$student->name} (ID: {$student->id})\n";
}
echo "\n";

// Test the query used in the controller
echo "🔍 Testing controller query logic:\n";
$query = User::where('role', User::ROLE_STUDENT);

if (!$tutor->isAdmin()) {
    echo "   Applying tutor filter (non-admin)...\n";
    $query->whereHas('assignedTutors', function ($q) use ($tutor) {
        $q->where('tutor_id', $tutor->id);
    });
} else {
    echo "   Admin user - no filter applied\n";
}

$students = $query->get();
echo "   Query result: {$students->count()} students\n";
foreach ($students as $student) {
    echo "   - {$student->name}\n";
}

echo "\n=== DEBUG COMPLETE ===\n";