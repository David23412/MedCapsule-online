<?php

/**
 * 🔍 BUNDLE PAYMENT FRONTEND ISSUE TESTER 🔍
 * This script simulates the exact frontend bundle payment flow
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "🔍 BUNDLE PAYMENT FRONTEND ISSUE TESTER" . PHP_EOL;
echo "=======================================" . PHP_EOL;

// Setup test user
$user = User::first();
Auth::login($user);
echo "✅ Authenticated as: {$user->name} (ID: {$user->id})" . PHP_EOL;

// Get test courses and ensure user is not enrolled
$testCourses = Course::where('price', '>', 0)->take(2)->get();
if ($testCourses->count() < 2) {
    echo "❌ Need at least 2 paid courses for testing" . PHP_EOL;
    exit(1);
}

// Remove existing enrollments for clean testing
\DB::table('course_user')
    ->where('user_id', $user->id)
    ->whereIn('course_id', $testCourses->pluck('id'))
    ->delete();

echo "✅ Test courses: " . $testCourses->pluck('name')->implode(', ') . PHP_EOL;
echo PHP_EOL;

// STEP 1: Test the bundle options route that frontend calls
echo "📋 STEP 1: TESTING FRONTEND BUNDLE OPTIONS ROUTE" . PHP_EOL;

$bundleData = [
    'courses' => $testCourses->pluck('id')->toArray(),
    'original_price' => $testCourses->sum('price'),
    'discount_amount' => 50,
    'total_price' => $testCourses->sum('price') - 50
];

echo "📤 Calling /payments/bundle/options with data:" . PHP_EOL;
echo "   Courses: [" . implode(', ', $bundleData['courses']) . "]" . PHP_EOL;
echo "   Original Price: {$bundleData['original_price']} EGP" . PHP_EOL;
echo "   Total Price: {$bundleData['total_price']} EGP" . PHP_EOL;

try {
    // Simulate the frontend AJAX call with proper session setup
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $request = Request::create('/payments/bundle/options', 'POST', $bundleData, [], [], [
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
    ]);
    
    // Add CSRF token to request
    $request->headers->set('X-CSRF-TOKEN', csrf_token());
    
    $response = app()->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "✅ Response Status: {$statusCode}" . PHP_EOL;
    
    if ($statusCode === 200) {
        $data = json_decode($content, true);
        echo "✅ Response Success: " . ($data['success'] ? 'YES' : 'NO') . PHP_EOL;
        if ($data['success']) {
            echo "✅ HTML Generated: " . (strlen($data['html']) > 0 ? 'YES' : 'NO') . PHP_EOL;
        } else {
            echo "❌ Error: " . $data['message'] . PHP_EOL;
        }
    } else {
        echo "❌ HTTP Error: {$statusCode}" . PHP_EOL;
        echo "   Response: " . substr($content, 0, 200) . "..." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// STEP 2: Test the bundle submission route
echo "📋 STEP 2: TESTING BUNDLE SUBMISSION ROUTE" . PHP_EOL;

$submissionData = [
    'courses' => $bundleData['courses'],
    'payment_method' => 'vodafone_cash',
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            'transaction_id' => 'FRONTEND_TEST_' . time(),
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    'total_amount' => $bundleData['total_price']
];

echo "📤 Calling /payment/submit-bundle-proof with data:" . PHP_EOL;
echo "   Transaction ID: {$submissionData['transaction_id']}" . PHP_EOL;

try {
    $request = Request::create('/payment/submit-bundle-proof', 'POST', $submissionData, [], [], [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            'HTTP_ACCEPT' => 'application/json'
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    ]);
    
    // Add CSRF token to request
    $request->headers->set('X-CSRF-TOKEN', csrf_token());
    
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $response = app()->handle($request);
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            DB::commit();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        } catch (Exception $e) {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            DB::rollback();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
            throw $e;
        }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "✅ Response Status: {$statusCode}" . PHP_EOL;
    
    if ($statusCode === 200) {
        $data = json_decode($content, true);
        echo "✅ Response Success: " . ($data['success'] ? 'YES' : 'NO') . PHP_EOL;
        if ($data['success']) {
            echo "✅ Payments Created: " . $data['payments_created'] . PHP_EOL;
            echo "✅ Bundle ID: " . $data['bundle_id'] . PHP_EOL;
        } else {
            echo "❌ Error: " . $data['message'] . PHP_EOL;
        }
    } else {
        echo "❌ HTTP Error: {$statusCode}" . PHP_EOL;
        echo "   Response: " . substr($content, 0, 200) . "..." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// STEP 3: Check if payments were actually created
echo "📋 STEP 3: CHECKING IF PAYMENTS WERE CREATED" . PHP_EOL;

$createdPayments = Payment::where('payment_data->transaction_id', $submissionData['transaction_id'])->get();
echo "✅ Payments found with transaction ID: " . $createdPayments->count() . PHP_EOL;

if ($createdPayments->count() > 0) {
    foreach ($createdPayments as $payment) {
        $course = Course::find($payment->course_id);
        echo "   Payment ID: {$payment->id}" . PHP_EOL;
        echo "   Course: {$course->name}" . PHP_EOL;
        echo "   Amount: {$payment->amount} EGP" . PHP_EOL;
        echo "   Status: {$payment->status}" . PHP_EOL;
        echo "   Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
        echo "   ---" . PHP_EOL;
    }
} else {
    echo "❌ No payments were created!" . PHP_EOL;
}

echo PHP_EOL . "🔍 FRONTEND TEST COMPLETE" . PHP_EOL;