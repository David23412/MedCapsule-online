<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Http\Controllers\UserCourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

echo "🚀 TESTING GRANT ACCESS API ENDPOINTS\n";
echo "=====================================\n\n";

// Get test data
$user = User::first();
$course = Course::first();

if (!$user || !$course) {
    echo "❌ Need at least 1 user and 1 course to test\n";
    exit(1);
}

echo "Testing with:\n";
echo "- User: {$user->name} (ID: {$user->id})\n";
echo "- Course: {$course->name} (ID: {$course->id})\n\n";

// Clean up any existing enrollment
DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->delete();

echo "1. Testing ENROLL endpoint...\n";

// Create controller instance
$controller = new UserCourseController();

// Create request for enrollment
$enrollRequest = new Request([
    'user_id' => $user->id,
    'course_id' => $course->id,
    'send_notification' => false,
    'immediate_access' => true
]);

try {
    $enrollResponse = $controller->enroll($enrollRequest);
    $enrollData = json_decode($enrollResponse->getContent(), true);
    
    if ($enrollData['success']) {
        echo "✅ Enroll endpoint successful!\n";
        echo "   Message: {$enrollData['message']}\n";
        echo "   User: {$enrollData['enrollment']['user_name']}\n";
        echo "   Course: {$enrollData['enrollment']['course_name']}\n";
        
        // Verify in database
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        if ($enrollment && $enrollment->is_enrolled) {
            echo "✅ Database verification: User is enrolled\n";
        } else {
            echo "❌ Database verification: Enrollment not found or not active\n";
        }
    } else {
        echo "❌ Enroll endpoint failed: {$enrollData['message']}\n";
    }
} catch (Exception $e) {
    echo "❌ Enroll endpoint error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing REVOKE endpoint...\n";

// Create request for revoke
$revokeRequest = new Request([
    'user_id' => $user->id,
    'course_id' => $course->id
]);

try {
    $revokeResponse = $controller->revoke($revokeRequest);
    $revokeData = json_decode($revokeResponse->getContent(), true);
    
    if ($revokeData['success']) {
        echo "✅ Revoke endpoint successful!\n";
        echo "   Message: {$revokeData['message']}\n";
        echo "   New Status: " . ($revokeData['is_enrolled'] ? 'ENROLLED' : 'NOT ENROLLED') . "\n";
        
        // Verify in database
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        if ($enrollment) {
            echo "✅ Database verification: is_enrolled = " . ($enrollment->is_enrolled ? 'true' : 'false') . "\n";
            
            if ($enrollment->is_enrolled == $revokeData['is_enrolled']) {
                echo "✅ Response matches database\n";
            } else {
                echo "❌ Response doesn't match database\n";
            }
        } else {
            echo "❌ Database verification: Enrollment record not found\n";
        }
    } else {
        echo "❌ Revoke endpoint failed: {$revokeData['message']}\n";
    }
} catch (Exception $e) {
    echo "❌ Revoke endpoint error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing TOGGLE functionality...\n";

// Test multiple toggles
for ($i = 1; $i <= 3; $i++) {
    echo "Toggle #{$i}:\n";
    
    $toggleRequest = new Request([
        'user_id' => $user->id,
        'course_id' => $course->id
    ]);
    
    try {
        $toggleResponse = $controller->revoke($toggleRequest);
        $toggleData = json_decode($toggleResponse->getContent(), true);
        
        if ($toggleData['success']) {
            echo "   ✅ Status: " . ($toggleData['is_enrolled'] ? 'ENROLLED' : 'NOT ENROLLED') . "\n";
        } else {
            echo "   ❌ Toggle failed: {$toggleData['message']}\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Toggle error: " . $e->getMessage() . "\n";
    }
}

echo "\n4. Testing User Access Verification...\n";

// Test hasAccessToCourse method
$hasAccess = $user->hasAccessToCourse($course->id);
echo "User hasAccessToCourse: " . ($hasAccess ? 'YES' : 'NO') . "\n";

// Get final enrollment status
$finalEnrollment = DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->first();

if ($finalEnrollment) {
    echo "Final database status: " . ($finalEnrollment->is_enrolled ? 'ENROLLED' : 'NOT ENROLLED') . "\n";
    
    // Verify consistency
    if (($hasAccess && $finalEnrollment->is_enrolled) || (!$hasAccess && !$finalEnrollment->is_enrolled)) {
        echo "✅ User model and database are consistent\n";
    } else {
        echo "❌ User model and database are inconsistent\n";
    }
} else {
    echo "No enrollment record found\n";
}

echo "\n🎉 GRANT ACCESS API TEST COMPLETE!\n";

// Clean up
DB::table('course_user')
    ->where('user_id', $user->id)
    ->where('course_id', $course->id)
    ->delete();

echo "🧹 Test data cleaned up\n";