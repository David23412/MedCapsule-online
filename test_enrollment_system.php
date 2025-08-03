<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

echo "🚀 TESTING ENROLLMENT SYSTEM\n";
echo "============================\n\n";

// Test 1: Check database structure
echo "1. Checking database structure...\n";
try {
    $columns = DB::select('SHOW COLUMNS FROM course_user');
    echo "✅ course_user table columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column->Field} ({$column->Type})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error checking database: " . $e->getMessage() . "\n\n";
}

// Test 2: Check if we have users and courses
echo "2. Checking data availability...\n";
$userCount = User::count();
$courseCount = Course::count();
echo "✅ Users: {$userCount}\n";
echo "✅ Courses: {$courseCount}\n\n";

if ($userCount == 0 || $courseCount == 0) {
    echo "❌ Need at least 1 user and 1 course to test enrollment\n";
    exit(1);
}

// Test 3: Test enrollment functionality
echo "3. Testing enrollment functionality...\n";
$user = User::first();
$course = Course::first();

echo "Testing with User: {$user->name} (ID: {$user->id})\n";
echo "Testing with Course: {$course->name} (ID: {$course->id})\n\n";

// Check current enrollment status
$currentEnrollment = DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->first();

if ($currentEnrollment) {
    echo "Current enrollment status: " . ($currentEnrollment->is_enrolled ? 'ENROLLED' : 'NOT ENROLLED') . "\n";
} else {
    echo "No enrollment record exists\n";
}

// Test enrollment creation/update
echo "Creating/updating enrollment...\n";
DB::table('course_user')->updateOrInsert(
    [
        'user_id' => $user->id,
        'course_id' => $course->id
    ],
    [
        'is_enrolled' => true,
        'enrolled_at' => now(),
        'updated_at' => now(),
        'created_at' => now()
    ]
);

// Verify enrollment
$newEnrollment = DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->first();

if ($newEnrollment && $newEnrollment->is_enrolled) {
    echo "✅ Enrollment successful!\n";
    echo "   - User ID: {$newEnrollment->user_id}\n";
    echo "   - Course ID: {$newEnrollment->course_id}\n";
    echo "   - Is Enrolled: " . ($newEnrollment->is_enrolled ? 'YES' : 'NO') . "\n";
    echo "   - Enrolled At: {$newEnrollment->enrolled_at}\n";
} else {
    echo "❌ Enrollment failed!\n";
}

echo "\n4. Testing revoke functionality...\n";

// Test revoke (toggle to false)
DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->update([
        'is_enrolled' => false,
        'updated_at' => now()
    ]);

$revokedEnrollment = DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->first();

if ($revokedEnrollment && !$revokedEnrollment->is_enrolled) {
    echo "✅ Revoke successful!\n";
    echo "   - Is Enrolled: " . ($revokedEnrollment->is_enrolled ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ Revoke failed!\n";
}

echo "\n5. Testing toggle functionality...\n";

// Test toggle back to enrolled
$currentStatus = $revokedEnrollment->is_enrolled;
$newStatus = !$currentStatus;

DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->update([
        'is_enrolled' => $newStatus,
        'updated_at' => now()
    ]);

$toggledEnrollment = DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->first();

if ($toggledEnrollment && $toggledEnrollment->is_enrolled == $newStatus) {
    echo "✅ Toggle successful!\n";
    echo "   - Previous Status: " . ($currentStatus ? 'ENROLLED' : 'NOT ENROLLED') . "\n";
    echo "   - New Status: " . ($newStatus ? 'ENROLLED' : 'NOT ENROLLED') . "\n";
} else {
    echo "❌ Toggle failed!\n";
}

echo "\n🎉 ENROLLMENT SYSTEM TEST COMPLETE!\n";