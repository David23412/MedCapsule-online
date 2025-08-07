<?php

/**
 * 🔥 COMPREHENSIVE PAYMENT METHODS TEST 🔥
 * Tests both single and bundle payments to ensure payment methods are recorded correctly
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Http\Controllers\Payment\PaymentProcessingController;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "🔥 COMPREHENSIVE PAYMENT METHODS TEST" . PHP_EOL;
echo "====================================" . PHP_EOL;

// Setup test environment
$user = User::first();
Auth::login($user);
echo "✅ Authenticated as: {$user->name} (ID: {$user->id})" . PHP_EOL;

// Get test courses
$testCourses = Course::where('price', '>', 0)->take(3)->get();
if ($testCourses->count() < 3) {
    echo "❌ Need at least 3 paid courses for testing" . PHP_EOL;
    exit(1);
}

// Remove existing enrollments for clean testing
DB::table('course_user')
    ->where('user_id', $user->id)
    ->whereIn('course_id', $testCourses->pluck('id'))
    ->delete();

echo "✅ Test courses prepared: " . $testCourses->pluck('name')->implode(', ') . PHP_EOL;
echo PHP_EOL;

// Clear existing test payments
Payment::where('payment_data->transaction_id', 'like', 'PAYMENT_METHOD_TEST_%')->delete();
echo "✅ Cleared existing test payments" . PHP_EOL;
echo PHP_EOL;

$controller = new PaymentProcessingController(app(PaymentService::class));

// TEST 1: Single Payment with Different Payment Methods
echo "📋 TEST 1: SINGLE PAYMENT WITH DIFFERENT PAYMENT METHODS" . PHP_EOL;

$paymentMethods = [
    'vodafone_cash' => 'Vodafone Cash',
    'fawry' => 'Fawry',
    'instapay' => 'InstaPay',
    'bank_transfer' => 'Bank Transfer'
];

$singlePaymentResults = [];

foreach ($paymentMethods as $method => $displayName) {
    echo "📤 Testing single payment with {$displayName}..." . PHP_EOL;
    
    $request = new Request();
    $request->merge([
        'transaction_id' => 'PAYMENT_METHOD_TEST_SINGLE_' . strtoupper($method) . '_' . time(),
        'payment_method' => $method
    ]);
    
    try {
        $response = $controller->submitProof($request, $testCourses[0]->id);
        $data = json_decode($response->getContent(), true);
        
        if ($data['success']) {
            $payment = Payment::find($data['payment_id']);
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            echo "   ✅ Payment created with method: {$payment->payment_method}" . PHP_EOL;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
            $singlePaymentResults[$method] = $payment->payment_method;
        } else {
            echo "   ❌ Failed: " . $data['message'] . PHP_EOL;
            $singlePaymentResults[$method] = 'FAILED';
        }
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $singlePaymentResults[$method] = 'EXCEPTION';
    }
    
    // Clean up for next test
    Payment::where('course_id', $testCourses[0]->id)
        ->where('user_id', $user->id)
        ->where('status', 'pending')
        ->delete();
}
echo PHP_EOL;

// TEST 2: Bundle Payment with Different Payment Methods
echo "📋 TEST 2: BUNDLE PAYMENT WITH DIFFERENT PAYMENT METHODS" . PHP_EOL;

$bundlePaymentResults = [];

foreach ($paymentMethods as $method => $displayName) {
    echo "📤 Testing bundle payment with {$displayName}..." . PHP_EOL;
    
    $request = new Request();
    $request->merge([
        'courses' => [$testCourses[1]->id, $testCourses[2]->id],
        'payment_method' => $method,
        'transaction_id' => 'PAYMENT_METHOD_TEST_BUNDLE_' . strtoupper($method) . '_' . time(),
        'phone_number' => '01234567890',
        'total_amount' => 229.99
    ]);
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    try {
        $response = $controller->submitBundlePaymentProof($request);
        $data = json_decode($response->getContent(), true);
        
        if ($data['success']) {
            $bundlePayments = Payment::where('payment_data->bundle_id', $data['bundle_id'])->get();
            $paymentMethods = $bundlePayments->pluck('payment_method')->unique();
            echo "   ✅ Bundle payments created with methods: " . $paymentMethods->implode(', ') . PHP_EOL;
            echo "   ✅ Payments count: " . $bundlePayments->count() . PHP_EOL;
            $bundlePaymentResults[$method] = $paymentMethods->first();
        } else {
            echo "   ❌ Failed: " . $data['message'] . PHP_EOL;
            $bundlePaymentResults[$method] = 'FAILED';
        }
    } catch (Exception $e) {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            echo "   ❌ Exception: " . $e->getMessage() . PHP_EOL;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        $bundlePaymentResults[$method] = 'EXCEPTION';
    }
    
    // Clean up for next test
    Payment::where('user_id', $user->id)
        ->where('status', 'pending')
        ->whereIn('course_id', [$testCourses[1]->id, $testCourses[2]->id])
        ->delete();
}
echo PHP_EOL;

// TEST 3: Database verification
echo "📋 TEST 3: DATABASE VERIFICATION" . PHP_EOL;

$allTestPayments = Payment::where('payment_data->transaction_id', 'like', 'PAYMENT_METHOD_TEST_%')->get();
echo "✅ Total test payments created: " . $allTestPayments->count() . PHP_EOL;

if ($allTestPayments->count() > 0) {
    echo "📊 Payment methods in database:" . PHP_EOL;
    foreach ($allTestPayments as $payment) {
        $transactionId = $payment->payment_data['transaction_id'] ?? 'N/A';
        $course = Course::find($payment->course_id);
        echo "   Payment ID: {$payment->id}" . PHP_EOL;
        echo "   Course: {$course->name}" . PHP_EOL;
        echo "   Method: {$payment->payment_method}" . PHP_EOL;
        echo "   Transaction: {$transactionId}" . PHP_EOL;
        echo "   Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
        echo "   ---" . PHP_EOL;
    }
}
echo PHP_EOL;

// FINAL RESULTS
echo "🎯 FINAL RESULTS SUMMARY" . PHP_EOL;
echo "========================" . PHP_EOL;

echo "📊 SINGLE PAYMENT RESULTS:" . PHP_EOL;
foreach ($singlePaymentResults as $method => $result) {
    $status = ($result === $method) ? '✅ CORRECT' : '❌ INCORRECT';
    echo "   {$method}: {$result} {$status}" . PHP_EOL;
}

echo PHP_EOL . "📊 BUNDLE PAYMENT RESULTS:" . PHP_EOL;
foreach ($bundlePaymentResults as $method => $result) {
    $status = ($result === $method) ? '✅ CORRECT' : '❌ INCORRECT';
    echo "   {$method}: {$result} {$status}" . PHP_EOL;
}

// Check if all tests passed
$singleTestsPassed = array_filter($singlePaymentResults, function($result, $method) {
    return $result === $method;
}, ARRAY_FILTER_USE_BOTH);

$bundleTestsPassed = array_filter($bundlePaymentResults, function($result, $method) {
    return $result === $method;
}, ARRAY_FILTER_USE_BOTH);

echo PHP_EOL;
if (count($singleTestsPassed) === count($paymentMethods) && count($bundleTestsPassed) === count($paymentMethods)) {
    echo "🚀 SUCCESS: ALL PAYMENT METHODS ARE WORKING CORRECTLY!" . PHP_EOL;
    echo "✅ Single payments: " . count($singleTestsPassed) . "/" . count($paymentMethods) . " passed" . PHP_EOL;
    echo "✅ Bundle payments: " . count($bundleTestsPassed) . "/" . count($paymentMethods) . " passed" . PHP_EOL;
} else {
    echo "❌ FAILURE: SOME PAYMENT METHODS ARE NOT WORKING CORRECTLY!" . PHP_EOL;
    echo "❌ Single payments: " . count($singleTestsPassed) . "/" . count($paymentMethods) . " passed" . PHP_EOL;
    echo "❌ Bundle payments: " . count($bundleTestsPassed) . "/" . count($paymentMethods) . " passed" . PHP_EOL;
}

echo PHP_EOL . "🔥 COMPREHENSIVE PAYMENT METHODS TEST COMPLETE 🔥" . PHP_EOL;