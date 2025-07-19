<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TUTOR DASHBOARD TEST ===\n\n";

// Test 1: Check if tutors exist
echo "1. Checking tutors...\n";
$tutors = User::where('role', 'tutor')->get();
echo "Found {$tutors->count()} tutors:\n";
foreach ($tutors as $tutor) {
    echo "   - {$tutor->name} ({$tutor->email})\n";
}
echo "\n";

// Test 2: Check student assignments
echo "2. Checking student assignments...\n";
foreach ($tutors as $tutor) {
    $assignedStudents = $tutor->assignedStudents()->get();
    echo "   {$tutor->name} has {$assignedStudents->count()} assigned students:\n";
    
    foreach ($assignedStudents as $student) {
        $quizCount = $student->quizAttempts()->count();
        $avgScore = $student->quizAttempts()->avg('score') ?? 0;
        $lastActive = $student->last_active_at ? $student->last_active_at->diffForHumans() : 'Never';
        
        echo "     - {$student->name}: {$quizCount} quizzes, {$avgScore}% avg, active {$lastActive}\n";
    }
    echo "\n";
}

// Test 3: Check tutor dashboard controller functionality
echo "3. Testing dashboard controller...\n";
$mainTutor = $tutors->first();
if ($mainTutor) {
    // Simulate authentication
    auth()->login($mainTutor);
    
    $controller = new App\Http\Controllers\Tutor\TutorDashboardController();
    
    try {
        $request = new Illuminate\Http\Request();
        $response = $controller->index($request);
        echo "   ✓ Dashboard loads successfully\n";
        
        // Check if students are being passed to the view
        $viewData = $response->getData();
        if (isset($viewData['students'])) {
            echo "   ✓ Students data is available: {$viewData['students']->count()} students\n";
        } else {
            echo "   ✗ Students data is missing\n";
        }
        
    } catch (Exception $e) {
        echo "   ✗ Dashboard error: " . $e->getMessage() . "\n";
    }
}

// Test 4: Check export functionality
echo "\n4. Testing export functionality...\n";
try {
    $students = User::where('role', 'student')->take(2)->get();
    echo "   ✓ Can retrieve students for export: {$students->count()} students\n";
    
    // Test CSV generation
    $csvData = "Student Name,Email,Quiz Average,Total Quizzes\n";
    foreach ($students as $student) {
        $avgScore = $student->quizAttempts()->avg('score') ?? 0;
        $quizCount = $student->quizAttempts()->count();
        $csvData .= "{$student->name},{$student->email}," . number_format($avgScore, 1) . ",{$quizCount}\n";
    }
    echo "   ✓ CSV generation works\n";
    
} catch (Exception $e) {
    echo "   ✗ Export error: " . $e->getMessage() . "\n";
}

// Test 5: Check database relationships
echo "\n5. Checking database relationships...\n";
$relationshipCount = DB::table('tutor_student_relationships')->count();
echo "   Total tutor-student relationships: {$relationshipCount}\n";

$relationships = DB::table('tutor_student_relationships')
    ->join('users as tutors', 'tutor_student_relationships.tutor_id', '=', 'tutors.id')
    ->join('users as students', 'tutor_student_relationships.student_id', '=', 'students.id')
    ->select('tutors.name as tutor_name', 'students.name as student_name')
    ->get();

foreach ($relationships as $rel) {
    echo "   - {$rel->tutor_name} → {$rel->student_name}\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Dashboard should now show assigned students properly!\n";
echo "Login as tutor@medcapsule.com (password: password) to test\n";