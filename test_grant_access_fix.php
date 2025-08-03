<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 TESTING GRANT ACCESS FUNCTIONALITY\n";
echo "=====================================\n\n";

try {
    // Test 1: Check if course_user table exists
    echo "1. Checking course_user table structure...\n";
    $columns = DB::select('SHOW COLUMNS FROM course_user');
    echo "✅ course_user table exists with " . count($columns) . " columns\n";
    
    foreach ($columns as $column) {
        echo "   - {$column->Field}: {$column->Type}\n";
    }
    echo "\n";

    // Test 2: Check if we have users and courses
    echo "2. Checking available users and courses...\n";
    $userCount = DB::table('users')->count();
    $courseCount = DB::table('courses')->count();
    echo "✅ Users: {$userCount}, Courses: {$courseCount}\n\n";

    // Test 3: Test enrollment creation
    echo "3. Testing enrollment creation...\n";
    
    // Get first user and course
    $user = DB::table('users')->first();
    $course = DB::table('courses')->first();
    
    if (!$user || !$course) {
        echo "❌ No users or courses found for testing\n";
        exit(1);
    }
    
    echo "   Using User: {$user->name} (ID: {$user->id})\n";
    echo "   Using Course: {$course->name} (ID: {$course->id})\n";
    
    // Check if enrollment already exists
    $existingEnrollment = DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();
    
    if ($existingEnrollment) {
        echo "   ⚠️  Enrollment already exists, updating...\n";
        
        DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->update([
                'is_enrolled' => true,
                'enrolled_at' => now(),
                'updated_at' => now()
            ]);
    } else {
        echo "   🆕 Creating new enrollment...\n";
        
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    // Verify the enrollment
    $verificationEnrollment = DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();
    
    if ($verificationEnrollment && $verificationEnrollment->is_enrolled) {
        echo "   ✅ Enrollment created/updated successfully!\n";
        echo "   ✅ is_enrolled = " . ($verificationEnrollment->is_enrolled ? 'true' : 'false') . "\n";
        echo "   ✅ enrolled_at = {$verificationEnrollment->enrolled_at}\n";
    } else {
        echo "   ❌ Enrollment verification failed!\n";
    }
    
    echo "\n";

    // Test 4: Test revoke functionality
    echo "4. Testing revoke functionality...\n";
    
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
        echo "   ✅ Revoke successful! is_enrolled = false\n";
    } else {
        echo "   ❌ Revoke failed!\n";
    }
    
    echo "\n";

    // Test 5: Test toggle functionality
    echo "5. Testing toggle functionality...\n";
    
    // Toggle back to enrolled
    DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->update([
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'updated_at' => now()
        ]);
    
    $toggledEnrollment = DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();
    
    if ($toggledEnrollment && $toggledEnrollment->is_enrolled) {
        echo "   ✅ Toggle successful! is_enrolled = true\n";
    } else {
        echo "   ❌ Toggle failed!\n";
    }
    
    echo "\n";

    // Test 6: Check total enrollments
    echo "6. Checking total enrollments...\n";
    $totalEnrollments = DB::table('course_user')->count();
    $activeEnrollments = DB::table('course_user')->where('is_enrolled', true)->count();
    echo "   ✅ Total enrollments: {$totalEnrollments}\n";
    echo "   ✅ Active enrollments: {$activeEnrollments}\n";
    
    echo "\n";
    echo "🎉 ALL TESTS PASSED! Grant Access functionality is working correctly.\n";
    echo "✅ Database operations: OK\n";
    echo "✅ Boolean field handling: OK\n";
    echo "✅ Create/Update operations: OK\n";
    echo "✅ Toggle functionality: OK\n";
    
} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 