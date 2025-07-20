<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Course;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Assignment View Debug ===\n\n";

// Simulate the assignment controller create method
$admin = User::where('role', 'admin')->first();
echo "Testing with admin: {$admin->name}\n";

if ($admin->isAdmin()) {
    $courses = Course::orderBy('name')->get();
    $students = User::where('role', User::ROLE_STUDENT)->orderBy('name')->get();
    
    echo "Courses found: {$courses->count()}\n";
    echo "Students found: {$students->count()}\n";
    
    echo "\nCourses data:\n";
    foreach ($courses as $course) {
        echo "  - ID: {$course->id}, Name: {$course->name}, Year: {$course->year}\n";
    }
    
    echo "\nStudents data:\n";
    foreach ($students as $student) {
        echo "  - ID: {$student->id}, Name: {$student->name}, Role: {$student->role}\n";
    }
} else {
    echo "User is not admin!\n";
}

echo "\n=== Debug Complete ===\n";