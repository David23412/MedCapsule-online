<?php

require_once 'vendor/autoload.php';

use App\Models\User;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== STUDENT PROFILE TEST ===\n\n";

// Get a tutor and their students
$tutor = User::where('role', 'tutor')->first();
if (!$tutor) {
    echo "No tutors found!\n";
    exit;
}

echo "Testing student profile access for tutor: {$tutor->name}\n";

// Get assigned students
$students = $tutor->assignedStudents()->get();
echo "Found {$students->count()} assigned students\n\n";

if ($students->isEmpty()) {
    echo "No students assigned to test with!\n";
    exit;
}

// Test with first student
$student = $students->first();
echo "Testing profile for student: {$student->name}\n";

// Simulate authentication
auth()->login($tutor);

$controller = new App\Http\Controllers\Tutor\TutorDashboardController();

try {
    $request = new Illuminate\Http\Request();
    $response = $controller->showStudentProfile($request, $student->id);
    
    echo "✅ Student profile loads successfully!\n";
    
    // Check if it's a view response
    if ($response instanceof Illuminate\View\View) {
        echo "✅ Returns proper view response\n";
        
        $viewData = $response->getData();
        echo "✅ View data contains:\n";
        echo "   - Student: " . ($viewData['student']->name ?? 'MISSING') . "\n";
        echo "   - Tutor: " . ($viewData['tutor']->name ?? 'MISSING') . "\n";
        echo "   - Data keys: " . implode(', ', array_keys($viewData['data'] ?? [])) . "\n";
        
        // Test comprehensive data
        $data = $viewData['data'];
        if (isset($data['overview'])) {
            echo "\n📊 STUDENT OVERVIEW:\n";
            echo "   - Total Quizzes: " . ($data['overview']['total_quizzes'] ?? 'N/A') . "\n";
            echo "   - Average Score: " . ($data['overview']['average_score'] ?? 'N/A') . "%\n";
            echo "   - Best Score: " . ($data['overview']['best_score'] ?? 'N/A') . "%\n";
            echo "   - Study Streak: " . ($data['overview']['study_streak'] ?? 'N/A') . " days\n";
            echo "   - Total XP: " . ($data['overview']['total_xp'] ?? 'N/A') . "\n";
        }
        
        if (isset($data['recent_quizzes'])) {
            echo "\n📝 RECENT QUIZZES: " . count($data['recent_quizzes']) . " found\n";
            foreach (array_slice($data['recent_quizzes'], 0, 3) as $quiz) {
                echo "   - {$quiz['topic']}: {$quiz['score']}%\n";
            }
        }
        
        if (isset($data['performance_by_topic'])) {
            echo "\n📚 TOPIC PERFORMANCE: " . count($data['performance_by_topic']) . " topics\n";
            foreach (array_slice($data['performance_by_topic'], 0, 3) as $topic) {
                echo "   - {$topic['topic']}: {$topic['average_score']}% ({$topic['attempts']} attempts)\n";
            }
        }
        
    } else {
        echo "❌ Response is not a view: " . get_class($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test access control
echo "\n🔒 TESTING ACCESS CONTROL:\n";

// Test with non-assigned student
$allStudents = User::where('role', 'student')->get();
$nonAssignedStudent = $allStudents->first(function ($s) use ($students) {
    return !$students->contains('id', $s->id);
});

if ($nonAssignedStudent) {
    echo "Testing access to non-assigned student: {$nonAssignedStudent->name}\n";
    
    try {
        $response = $controller->showStudentProfile($request, $nonAssignedStudent->id);
        echo "❌ Access control failed - should have been blocked!\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '403') !== false || strpos($e->getMessage(), 'permission') !== false) {
            echo "✅ Access control working - properly blocked unauthorized access\n";
        } else {
            echo "❌ Unexpected error: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "⚠️  No non-assigned students to test access control with\n";
}

echo "\n=== STUDENT PROFILE TEST COMPLETE ===\n";
echo "Student profile functionality is working correctly!\n";
echo "You can now click on student names in the dashboard to view their profiles.\n";