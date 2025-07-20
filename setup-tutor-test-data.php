<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Setting up Tutor Test Data ===\n\n";

// Check if we already have a tutor
$existingTutor = User::where('role', 'tutor')->first();
if ($existingTutor) {
    echo "Tutor already exists: {$existingTutor->name}\n";
} else {
    // Create a tutor user
    $tutor = User::create([
        'name' => 'Test Tutor',
        'username' => 'tutor1',
        'email' => 'tutor@test.com',
        'password' => bcrypt('password'),
        'role' => 'tutor',
        'university' => 'Test University',
        'email_verified_at' => now(),
    ]);
    echo "Created tutor: {$tutor->name} (ID: {$tutor->id})\n";
}

// Get the tutor (either existing or newly created)
$tutor = User::where('role', 'tutor')->first();

// Get all students
$students = User::where('role', 'student')->get();
echo "Found {$students->count()} students\n";

if ($students->count() > 0) {
    // Assign all students to the tutor
    foreach ($students as $student) {
        // Check if relationship already exists
        $exists = DB::table('tutor_student_relationships')
            ->where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)
            ->exists();
        
        if (!$exists) {
            DB::table('tutor_student_relationships')->insert([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "Assigned student {$student->name} to tutor {$tutor->name}\n";
        } else {
            echo "Student {$student->name} already assigned to tutor {$tutor->name}\n";
        }
    }
}

// Make sure students are enrolled in courses
$courses = Course::all();
echo "\nFound {$courses->count()} courses\n";

foreach ($students as $student) {
    foreach ($courses as $course) {
        // Check if student is already enrolled
        $enrolled = DB::table('course_user')
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();
        
        if (!$enrolled) {
            DB::table('course_user')->insert([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "Enrolled {$student->name} in {$course->name}\n";
        }
    }
}

echo "\n=== Test Data Setup Complete ===\n";
echo "You can now log in as:\n";
echo "Username: tutor1\n";
echo "Password: password\n";