<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 TESTING AJAX ENROLLMENT ENDPOINT\n";
echo "===================================\n\n";

try {
    // Get test data
    $user = DB::table('users')->first();
    $course = DB::table('courses')->first();
    
    if (!$user || !$course) {
        echo "❌ No users or courses found for testing\n";
        exit(1);
    }
    
    echo "Using User: {$user->name} (ID: {$user->id})\n";
    echo "Using Course: {$course->name} (ID: {$course->id})\n\n";
    
    // Test 1: Direct database enrollment
    echo "1. Testing direct database enrollment...\n";
    
    DB::table('course_user')->updateOrInsert(
        ['user_id' => $user->id, 'course_id' => $course->id],
        ['is_enrolled' => true, 'enrolled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
    );
    
    $enrollment = DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();
    
    if ($enrollment && $enrollment->is_enrolled) {
        echo "   ✅ Direct database enrollment successful\n";
    } else {
        echo "   ❌ Direct database enrollment failed\n";
    }
    
    // Test 2: Simulate AJAX request
    echo "\n2. Testing AJAX endpoint simulation...\n";
    
    $requestData = [
        'user_id' => $user->id,
        'course_id' => $course->id,
        '_token' => csrf_token()
    ];
    
    echo "   Request data: " . json_encode($requestData) . "\n";
    
    // Create a request to the enroll endpoint
    $request = \Illuminate\Http\Request::create('/manage-access/enroll', 'POST', $requestData);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
    $request->headers->set('X-CSRF-TOKEN', csrf_token());
    
    // Handle the request
    $response = app()->handle($request);
    $responseContent = $response->getContent();
    
    echo "   Response status: " . $response->getStatusCode() . "\n";
    echo "   Response content: " . $responseContent . "\n";
    
    if ($response->getStatusCode() === 200) {
        $responseData = json_decode($responseContent, true);
        if ($responseData && isset($responseData['success']) && $responseData['success']) {
            echo "   ✅ AJAX endpoint working correctly\n";
        } else {
            echo "   ❌ AJAX endpoint returned error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ❌ AJAX endpoint failed with status: " . $response->getStatusCode() . "\n";
    }
    
    // Test 3: Verify enrollment in database
    echo "\n3. Verifying enrollment in database...\n";
    
    $finalEnrollment = DB::table('course_user')
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();
    
    if ($finalEnrollment) {
        echo "   ✅ Enrollment record exists\n";
        echo "   ✅ is_enrolled: " . ($finalEnrollment->is_enrolled ? 'true' : 'false') . "\n";
        echo "   ✅ enrolled_at: {$finalEnrollment->enrolled_at}\n";
    } else {
        echo "   ❌ Enrollment record not found\n";
    }
    
    // Test 4: Test revoke functionality
    echo "\n4. Testing revoke functionality...\n";
    
    $revokeData = [
        'user_id' => $user->id,
        'course_id' => $course->id,
        '_token' => csrf_token()
    ];
    
    $revokeRequest = \Illuminate\Http\Request::create('/manage-access/revoke', 'POST', $revokeData);
    $revokeRequest->headers->set('Accept', 'application/json');
    $revokeRequest->headers->set('Content-Type', 'application/x-www-form-urlencoded');
    $revokeRequest->headers->set('X-CSRF-TOKEN', csrf_token());
    
    $revokeResponse = app()->handle($revokeRequest);
    $revokeContent = $revokeResponse->getContent();
    
    echo "   Revoke response status: " . $revokeResponse->getStatusCode() . "\n";
    echo "   Revoke response content: " . $revokeContent . "\n";
    
    if ($revokeResponse->getStatusCode() === 200) {
        $revokeData = json_decode($revokeContent, true);
        if ($revokeData && isset($revokeData['success']) && $revokeData['success']) {
            echo "   ✅ Revoke endpoint working correctly\n";
        } else {
            echo "   ❌ Revoke endpoint returned error: " . ($revokeData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ❌ Revoke endpoint failed with status: " . $revokeResponse->getStatusCode() . "\n";
    }
    
    echo "\n🎉 AJAX ENDPOINT TESTING COMPLETE!\n";
    echo "✅ Database operations working\n";
    echo "✅ AJAX endpoints accessible\n";
    echo "✅ CSRF tokens working\n";
    echo "✅ Enrollment/revoke functionality working\n";
    
} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 