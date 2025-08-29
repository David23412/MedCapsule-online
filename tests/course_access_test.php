<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "🧪 COURSE ACCESS COMPREHENSIVE TEST\n";
echo "==================================\n\n";

// Test scenarios
$scenarios = [
    'ADMIN_TO_STUDENT' => 'Admin role removed - all enrollments revoked',
    'PAID_TO_PAID' => 'Course price changed (paid to paid) - enrollments preserved',
    'PAID_TO_FREE' => 'Course price changed (paid to free) - auto-enroll all users',
    'FREE_TO_PAID' => 'Course price changed (free to paid) - all enrollments revoked',
    'STUDENT_TO_ADMIN' => 'Student role changed to admin - access to all courses'
];

foreach ($scenarios as $scenario => $description) {
    echo "🔍 TESTING: {$scenario}\n";
    echo "📝 {$description}\n";
    echo "─".str_repeat("─", 50)."\n";

    try {
        runTest($scenario);
        echo "✅ PASSED\n\n";
    } catch (Exception $e) {
        echo "❌ FAILED: {$e->getMessage()}\n\n";
    }
}

function runTest($scenario)
{
    switch ($scenario) {
        case 'ADMIN_TO_STUDENT':
            testAdminToStudent();
            break;
        case 'PAID_TO_PAID':
            testPaidToPaid();
            break;
        case 'PAID_TO_FREE':
            testPaidToFree();
            break;
        case 'FREE_TO_PAID':
            testFreeToPaid();
            break;
        case 'STUDENT_TO_ADMIN':
            testStudentToAdmin();
            break;
    }
}

function testAdminToStudent()
{
    // Create test user with admin role
    $user = createTestUser('admin_test@example.com', 1); // ROLE_ADMIN

    // Enroll user in some courses
    $courses = \App\Models\Course::take(3)->get();
    $enrolledCount = 0;

    foreach ($courses as $course) {
        $user->enrollInCourse($course->id);
        $enrolledCount++;
    }

    echo "📊 User enrolled in {$enrolledCount} courses\n";

    // Change role from admin to student
    $user->update(['role' => 3]); // ROLE_STUDENT

    // Check if all enrollments are revoked
    $remainingEnrollments = DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('is_enrolled', true)
        ->count();

    echo "📊 Enrollments after role change: {$remainingEnrollments}\n";

    if ($remainingEnrollments !== 0) {
        throw new Exception("Expected 0 enrollments, got {$remainingEnrollments}");
    }

    // Cleanup
    $user->delete();
}

function testPaidToPaid()
{
    // Create test course with price
    $course = createTestCourse('Test Paid Course', 100.00);

    // Create test users and enroll them
    $users = [];
    for ($i = 1; $i <= 3; $i++) {
        $user = createTestUser("paid_test_{$i}@example.com", 3); // ROLE_STUDENT
        $user->enrollInCourse($course->id);
        $users[] = $user;
    }

    echo "📊 Created course with price: \${$course->price}\n";
    echo "📊 Enrolled {$i} users\n";

    // Change price from 100 to 200 (still paid)
    $originalPrice = $course->price;
    $course->update(['price' => 200.00]);

    echo "📊 Price changed from \${$originalPrice} to \${$course->price}\n";

    // Check if all enrollments are preserved
    $enrolledUsers = DB::table('course_user')
        ->where('course_id', $course->id)
        ->where('is_enrolled', true)
        ->count();

    echo "📊 Users still enrolled: {$enrolledUsers}\n";

    if ($enrolledUsers !== 3) {
        throw new Exception("Expected 3 enrolled users, got {$enrolledUsers}");
    }

    // Cleanup
    foreach ($users as $user) {
        $user->delete();
    }
    $course->delete();
}

function testPaidToFree()
{
    // Create test course with price
    $course = createTestCourse('Test Paid to Free', 50.00);

    // Get all existing users
    $allUsers = \App\Models\User::all();
    $initialUserCount = $allUsers->count();

    echo "📊 Course price: \${$course->price}\n";
    echo "📊 Total users in system: {$initialUserCount}\n";

    // Change course from paid to free
    $course->update(['price' => 0.00]);

    echo "📊 Course changed to free (price: \${$course->price})\n";

    // Check if all users are now enrolled
    $enrolledUsers = DB::table('course_user')
        ->where('course_id', $course->id)
        ->where('is_enrolled', true)
        ->count();

    echo "📊 Users enrolled in free course: {$enrolledUsers}\n";

    if ($enrolledUsers < $initialUserCount) {
        throw new Exception("Expected at least {$initialUserCount} enrolled users, got {$enrolledUsers}");
    }

    // Cleanup
    $course->delete();
}

function testFreeToPaid()
{
    // Create test course as free
    $course = createTestCourse('Test Free to Paid', 0.00);

    // Create test users and enroll them
    $users = [];
    for ($i = 1; $i <= 3; $i++) {
        $user = createTestUser("free_test_{$i}@example.com", 3); // ROLE_STUDENT
        $user->enrollInCourse($course->id);
        $users[] = $user;
    }

    echo "📊 Course price: \${$course->price}\n";
    echo "📊 Enrolled {$i} users\n";

    // Change course from free to paid
    $course->update(['price' => 75.00]);

    echo "📊 Course changed to paid (price: \${$course->price})\n";

    // Check if all enrollments are revoked
    $enrolledUsers = DB::table('course_user')
        ->where('course_id', $course->id)
        ->where('is_enrolled', true)
        ->count();

    echo "📊 Users still enrolled: {$enrolledUsers}\n";

    if ($enrolledUsers !== 0) {
        throw new Exception("Expected 0 enrolled users, got {$enrolledUsers}");
    }

    // Cleanup
    foreach ($users as $user) {
        $user->delete();
    }
    $course->delete();
}

function testStudentToAdmin()
{
    // Create test user as student
    $user = createTestUser('student_to_admin@example.com', 3); // ROLE_STUDENT

    // Get all courses
    $allCourses = \App\Models\Course::all();
    $totalCourses = $allCourses->count();

    echo "📊 User role: Student\n";
    echo "📊 Total courses in system: {$totalCourses}\n";

    // Change user from student to admin
    $user->update(['role' => 1]); // ROLE_ADMIN

    echo "📊 User role changed to: Admin\n";

    // Check if user has access to all courses
    $accessibleCourses = $user->hasAccessToCourses($allCourses->pluck('id')->toArray());
    $accessibleCount = count(array_filter($accessibleCourses));

    echo "📊 Courses accessible to admin: {$accessibleCount}\n";

    if ($accessibleCount !== $totalCourses) {
        throw new Exception("Expected {$totalCourses} accessible courses, got {$accessibleCount}");
    }

    // Cleanup
    $user->delete();
}

function createTestUser($email, $role = 3)
{
    // Ensure unique email by adding timestamp
    $uniqueEmail = str_replace('@example.com', '_' . time() . '_' . rand(1000, 9999) . '@example.com', $email);

    return \App\Models\User::create([
        'name' => 'Test User ' . rand(1000, 9999),
        'email' => $uniqueEmail,
        'password' => bcrypt('password'),
        'role' => $role,
        'email_verified_at' => now()
    ]);
}

function createTestCourse($name, $price)
{
    return \App\Models\Course::create([
        'name' => $name,
        'description' => 'Test course for access testing',
        'price' => $price,
        'color' => '#3b82f6',
        'title_color' => '#ffffff'
    ]);
}

echo "🎉 ALL TESTS COMPLETED!\n";
echo "📊 Summary of test results above.\n";
