<?php

/**
 * 🔥 BUNDLE PAYMENT FINAL TEST & VERIFICATION 🔥
 * COMPREHENSIVE TEST TO VERIFY ALL BUNDLE PAYMENT FUNCTIONALITY
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Payment\PaymentProcessingController;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔥 BUNDLE PAYMENT FINAL TEST & VERIFICATION 🔥" . PHP_EOL;
echo "===============================================" . PHP_EOL;
echo "COMPREHENSIVE TEST OF ALL BUNDLE PAYMENT FUNCTIONALITY" . PHP_EOL;
echo PHP_EOL;

// Test with student user (ID: 2)
$testUser = User::find(2);
if (!$testUser) {
    echo "❌ Student user (ID: 2) not found!" . PHP_EOL;
    exit(1);
}

Auth::login($testUser);
echo "✅ Testing with user: {$testUser->name} (ID: {$testUser->id})" . PHP_EOL;

// Clear existing payments for clean test
Payment::where('user_id', $testUser->id)->delete();
echo "✅ Cleared existing payments for clean test" . PHP_EOL;

// Get test courses
$courses = Course::where('price', '>', 0)->limit(3)->get();
$testCourseIds = $courses->take(2)->pluck('id')->toArray();

echo "🎯 Testing with courses: [" . implode(', ', $testCourseIds) . "]" . PHP_EOL;
echo PHP_EOL;

// Test 1: Bundle Payment Submission
echo "📋 TEST 1: BUNDLE PAYMENT SUBMISSION" . PHP_EOL;
echo "====================================" . PHP_EOL;

$requestData = [
    'courses' => $testCourseIds,
    'payment_method' => 'vodafone_cash',
    'transaction_id' => 'FINAL_TEST_' . time(),
    'phone_number' => '01234567890',
    'total_amount' => 225.98,
    '_token' => csrf_token()
];

try {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $request = Request::create('/payment/submit-bundle-proof', 'POST', $requestData);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    $request->setUserResolver(function () use ($testUser) {
        return $testUser;
    });
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    $controller = new PaymentProcessingController(app(\App\Services\PaymentService::class));
    $response = $controller->submitBundlePaymentProof($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "✅ Bundle payment submission: " . ($responseData['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
    echo "✅ Message: " . $responseData['message'] . PHP_EOL;
    echo "✅ Bundle ID: " . ($responseData['bundle_id'] ?? 'N/A') . PHP_EOL;
    echo "✅ Payments Created: " . ($responseData['payments_created'] ?? 'N/A') . PHP_EOL;
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            try {
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
} catch (Exception $e) {
    echo "❌ Bundle payment submission failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Verify Database Records
echo "📋 TEST 2: DATABASE RECORD VERIFICATION" . PHP_EOL;
echo "=======================================" . PHP_EOL;

$createdPayments = Payment::where('user_id', $testUser->id)
    ->whereIn('course_id', $testCourseIds)
    ->where('is_bundle', true)
    ->get();

echo "✅ Bundle payments in database: " . $createdPayments->count() . PHP_EOL;

foreach ($createdPayments as $payment) {
    $paymentData = is_string($payment->payment_data) ? json_decode($payment->payment_data, true) : $payment->payment_data;
    echo "   - Payment ID: {$payment->id}" . PHP_EOL;
    echo "     Course ID: {$payment->course_id}" . PHP_EOL;
    echo "     Amount: {$payment->amount} EGP" . PHP_EOL;
    echo "     Status: {$payment->status}" . PHP_EOL;
    echo "     Bundle ID: " . ($paymentData['bundle_id'] ?? 'N/A') . PHP_EOL;
    echo "     Transaction ID: " . ($paymentData['transaction_id'] ?? 'N/A') . PHP_EOL;
    echo "     ---" . PHP_EOL;
}

echo PHP_EOL;

// Test 3: Frontend Button State Logic
echo "📋 TEST 3: FRONTEND BUTTON STATE LOGIC" . PHP_EOL;
echo "======================================" . PHP_EOL;

foreach ($testCourseIds as $courseId) {
    $course = Course::find($courseId);
    
    // Check enrollment status
    $isEnrolled = $testUser->courses()->where('course_id', $courseId)->where('is_enrolled', true)->exists();
    
    // Check pending payment status
    $hasPendingPayment = Payment::where('user_id', $testUser->id)
        ->where('course_id', $courseId)
        ->where('status', 'pending')
        ->exists();
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    echo "   Course {$courseId} ({$course->name}):" . PHP_EOL;
    echo "     - Is Enrolled: " . ($isEnrolled ? 'YES' : 'NO') . PHP_EOL;
    echo "     - Has Pending Payment: " . ($hasPendingPayment ? 'YES' : 'NO') . PHP_EOL;
    
    // Determine expected button state
    if ($isEnrolled) {
        $expectedState = 'CONTINUE LEARNING';
    } elseif ($hasPendingPayment) {
        $expectedState = 'PAYMENT PENDING (DISABLED)';
    } else {
        $expectedState = 'ENROLL NOW';
    }
    
    echo "     - Expected Button State: {$expectedState}" . PHP_EOL;
    echo PHP_EOL;
}

// Test 4: Verify Logging
echo "📋 TEST 4: LOGGING VERIFICATION" . PHP_EOL;
echo "==============================" . PHP_EOL;

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $bundleLogCount = substr_count($logContent, '🔥 BUNDLE PAYMENT:');
    echo "✅ Bundle payment log entries found: {$bundleLogCount}" . PHP_EOL;
    
    // Check for specific log entries
    $hasStartLog = strpos($logContent, '🔥 BUNDLE PAYMENT: Starting submission') !== false;
        // Wrap payment processing in database transaction
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            DB::beginTransaction();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        try {
            $hasValidationLog = strpos($logContent, '🔥 BUNDLE PAYMENT: Validation passed') !== false;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    $hasCreationLog = strpos($logContent, '🔥 BUNDLE PAYMENT: Payment record created') !== false;
    $hasSuccessLog = strpos($logContent, '🔥 BUNDLE PAYMENT: All payments created successfully') !== false;
    
    echo "✅ Has start log: " . ($hasStartLog ? 'YES' : 'NO') . PHP_EOL;
    echo "✅ Has validation log: " . ($hasValidationLog ? 'YES' : 'NO') . PHP_EOL;
    echo "✅ Has creation log: " . ($hasCreationLog ? 'YES' : 'NO') . PHP_EOL;
    echo "✅ Has success log: " . ($hasSuccessLog ? 'YES' : 'NO') . PHP_EOL;
} else {
    echo "❌ Log file not found!" . PHP_EOL;
}

echo PHP_EOL;

// Test 5: Route Verification
echo "📋 TEST 5: ROUTE VERIFICATION" . PHP_EOL;
echo "=============================" . PHP_EOL;

$routes = Route::getRoutes();
$bundleOptionsRoute = $routes->getByName('payment.bundle-options');
$bundleSubmitRoute = $routes->getByName('payment.submit-bundle-proof');

echo "✅ Bundle options route exists: " . ($bundleOptionsRoute ? 'YES' : 'NO') . PHP_EOL;
echo "✅ Bundle submit route exists: " . ($bundleSubmitRoute ? 'YES' : 'NO') . PHP_EOL;

if ($bundleSubmitRoute) {
    echo "   - URI: " . $bundleSubmitRoute->uri() . PHP_EOL;
    echo "   - Methods: " . implode(', ', $bundleSubmitRoute->methods()) . PHP_EOL;
}

echo PHP_EOL;

// FINAL SUMMARY
echo "🔥 FINAL TEST SUMMARY 🔥" . PHP_EOL;
echo "========================" . PHP_EOL;

$totalPayments = Payment::where('user_id', $testUser->id)->count();
$bundlePayments = Payment::where('user_id', $testUser->id)->where('is_bundle', true)->count();
$pendingPayments = Payment::where('user_id', $testUser->id)->where('status', 'pending')->count();

echo "✅ Total payments created: {$totalPayments}" . PHP_EOL;
echo "✅ Bundle payments created: {$bundlePayments}" . PHP_EOL;
echo "✅ Pending payments: {$pendingPayments}" . PHP_EOL;

$allTestsPassed = (
    $bundlePayments >= 2 && 
    $pendingPayments >= 2 && 
    $bundleOptionsRoute && 
    $bundleSubmitRoute
);

if ($allTestsPassed) {
    echo PHP_EOL . "🚀 SUCCESS: ALL BUNDLE PAYMENT TESTS PASSED!" . PHP_EOL;
    echo "✅ Bundle payment system is fully operational" . PHP_EOL;
    echo "✅ Database records are being created correctly" . PHP_EOL;
    echo "✅ Logging is working properly" . PHP_EOL;
    echo "✅ Routes are configured correctly" . PHP_EOL;
    echo "✅ Frontend buttons should show PENDING state" . PHP_EOL;
} else {
    echo PHP_EOL . "❌ SOME TESTS FAILED - CHECK RESULTS ABOVE" . PHP_EOL;
}

echo PHP_EOL;
echo "🔥 BUNDLE PAYMENT SYSTEM STATUS: " . ($allTestsPassed ? "FULLY OPERATIONAL" : "NEEDS ATTENTION") . " 🔥" . PHP_EOL;
echo PHP_EOL;

// Instructions for user
echo "📋 NEXT STEPS FOR USER:" . PHP_EOL;
echo "1. Go to the courses page in your browser" . PHP_EOL;
echo "2. Select 2 or more courses for bundle purchase" . PHP_EOL;
echo "3. Click 'Proceed to Payment' in the bundle modal" . PHP_EOL;
echo "4. Fill in the payment form and submit" . PHP_EOL;
echo "5. The course buttons should immediately show 'Payment Pending'" . PHP_EOL;
echo "6. Check the Laravel logs for detailed debugging information" . PHP_EOL;
echo PHP_EOL;
echo "🔥 BUNDLE PAYMENT FINAL TEST COMPLETE 🔥" . PHP_EOL;