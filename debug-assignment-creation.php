<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Assignment Creation Debug ===\n\n";

// Check if we have any users with tutor role
echo "1. Checking Users:\n";
$tutors = User::where('role', 'tutor')->get();
$admins = User::where('role', 'admin')->get();
$students = User::where('role', 'student')->get();

echo "   - Tutors: " . $tutors->count() . "\n";
echo "   - Admins: " . $admins->count() . "\n";
echo "   - Students: " . $students->count() . "\n\n";

if ($tutors->count() > 0) {
    $tutor = $tutors->first();
    echo "2. Testing with tutor: {$tutor->name} (ID: {$tutor->id})\n";
    
    // Check assigned students
    $assignedStudents = $tutor->assignedStudents;
    echo "   - Assigned students: " . $assignedStudents->count() . "\n";
    
    if ($assignedStudents->count() > 0) {
        echo "   - Student names: " . $assignedStudents->pluck('name')->implode(', ') . "\n";
        
        // Check courses for these students
        $studentIds = $assignedStudents->pluck('id');
        $courses = Course::whereHas('users', function($query) use ($studentIds) {
            $query->whereIn('users.id', $studentIds);
        })->get();
        
        echo "   - Courses with assigned students: " . $courses->count() . "\n";
        if ($courses->count() > 0) {
            echo "   - Course names: " . $courses->pluck('name')->implode(', ') . "\n";
            
            // Check topics for first course
            $firstCourse = $courses->first();
            $topics = Topic::where('course_id', $firstCourse->id)->get();
            echo "   - Topics in '{$firstCourse->name}': " . $topics->count() . "\n";
            if ($topics->count() > 0) {
                echo "   - Topic names: " . $topics->pluck('name')->take(5)->implode(', ') . (($topics->count() > 5) ? '...' : '') . "\n";
            }
        }
    }
} else if ($admins->count() > 0) {
    $admin = $admins->first();
    echo "2. Testing with admin: {$admin->name} (ID: {$admin->id})\n";
    
    // Check all courses and students for admin
    $allCourses = Course::count();
    $allStudents = User::where('role', 'student')->count();
    
    echo "   - Total courses: {$allCourses}\n";
    echo "   - Total students: {$allStudents}\n";
    
    if ($allCourses > 0) {
        $firstCourse = Course::first();
        $topics = Topic::where('course_id', $firstCourse->id)->get();
        echo "   - Topics in '{$firstCourse->name}': " . $topics->count() . "\n";
    }
}

// Check tutor-student relationships table
echo "\n3. Checking tutor_student_relationships table:\n";
try {
    $relationships = DB::table('tutor_student_relationships')->count();
    echo "   - Total relationships: {$relationships}\n";
    
    if ($relationships > 0) {
        $sampleRelationships = DB::table('tutor_student_relationships')
            ->join('users as tutors', 'tutor_student_relationships.tutor_id', '=', 'tutors.id')
            ->join('users as students', 'tutor_student_relationships.student_id', '=', 'students.id')
            ->select('tutors.name as tutor_name', 'students.name as student_name')
            ->take(5)
            ->get();
        
        echo "   - Sample relationships:\n";
        foreach ($sampleRelationships as $rel) {
            echo "     * {$rel->tutor_name} -> {$rel->student_name}\n";
        }
    }
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

// Check course enrollments
echo "\n4. Checking course enrollments:\n";
try {
    $enrollments = DB::table('course_user')->count();
    echo "   - Total enrollments: {$enrollments}\n";
    
    if ($enrollments > 0) {
        $sampleEnrollments = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->select('courses.name as course_name', 'users.name as user_name')
            ->take(5)
            ->get();
        
        echo "   - Sample enrollments:\n";
        foreach ($sampleEnrollments as $enroll) {
            echo "     * {$enroll->user_name} -> {$enroll->course_name}\n";
        }
    }
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";