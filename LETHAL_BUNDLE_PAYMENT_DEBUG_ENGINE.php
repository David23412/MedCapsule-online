<?php

/**
 * 🔥 LETHAL BUNDLE PAYMENT DEBUG ENGINE 🔥
 * AGGRESSIVE DEBUGGING TO FIND WHAT THE FUCK IS WRONG WITH BUNDLE PAYMENTS
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

echo "🔥 LETHAL BUNDLE PAYMENT DEBUG ENGINE ACTIVATED 🔥" . PHP_EOL;
echo "=================================================" . PHP_EOL;
echo "AGGRESSIVE DEBUGGING TO FIND BUNDLE PAYMENT ISSUES" . PHP_EOL;
echo PHP_EOL;

// Step 1: Check database structure
echo "📋 STEP 1: CHECKING DATABASE STRUCTURE" . PHP_EOL;
echo "======================================" . PHP_EOL;

try {
    $paymentColumns = DB::select("DESCRIBE payments");
    echo "✅ Payments table structure:" . PHP_EOL;
    foreach ($paymentColumns as $column) {
        echo "   - {$column->Field}: {$column->Type}" . PHP_EOL;
    }
    
    $hasIsBundleColumn = collect($paymentColumns)->contains(function($col) {
        return $col->Field === 'is_bundle';
    });
    
    echo "✅ Has is_bundle column: " . ($hasIsBundleColumn ? 'YES' : 'NO') . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Database structure check failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Step 2: Check existing payments
echo "📋 STEP 2: CHECKING EXISTING PAYMENTS" . PHP_EOL;
echo "====================================" . PHP_EOL;

$totalPayments = Payment::count();
$bundlePayments = Payment::where('is_bundle', true)->count();
$pendingPayments = Payment::where('status', 'pending')->count();

echo "✅ Total payments: {$totalPayments}" . PHP_EOL;
echo "✅ Bundle payments: {$bundlePayments}" . PHP_EOL;
echo "✅ Pending payments: {$pendingPayments}" . PHP_EOL;

// Show recent payments
$recentPayments = Payment::orderBy('created_at', 'desc')->limit(5)->get();
echo "✅ Recent payments:" . PHP_EOL;
foreach ($recentPayments as $payment) {
    $paymentData = json_decode($payment->payment_data, true);
    echo "   - ID: {$payment->id}, Course: {$payment->course_id}, Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . ", Status: {$payment->status}" . PHP_EOL;
    if (isset($paymentData['bundle_id'])) {
        echo "     Bundle ID: {$paymentData['bundle_id']}" . PHP_EOL;
    }
}

echo PHP_EOL;

// Step 3: Test user authentication
echo "📋 STEP 3: TESTING USER AUTHENTICATION" . PHP_EOL;
echo "======================================" . PHP_EOL;

$testUser = User::first();
if (!$testUser) {
    echo "❌ No users found in database!" . PHP_EOL;
    exit(1);
}

Auth::login($testUser);
echo "✅ Logged in as user: {$testUser->name} (ID: {$testUser->id})" . PHP_EOL;

echo PHP_EOL;

// Step 4: Test bundle payment routes
echo "📋 STEP 4: TESTING BUNDLE PAYMENT ROUTES" . PHP_EOL;
echo "========================================" . PHP_EOL;

$routes = Route::getRoutes();
$bundleOptionsRoute = $routes->getByName('payment.bundle-options');
$bundleSubmitRoute = $routes->getByName('payment.submit-bundle-proof');

echo "✅ Bundle options route exists: " . ($bundleOptionsRoute ? 'YES' : 'NO') . PHP_EOL;
echo "✅ Bundle submit route exists: " . ($bundleSubmitRoute ? 'YES' : 'NO') . PHP_EOL;

if ($bundleOptionsRoute) {
    echo "   - URI: " . $bundleOptionsRoute->uri() . PHP_EOL;
    echo "   - Methods: " . implode(', ', $bundleOptionsRoute->methods()) . PHP_EOL;
}

if ($bundleSubmitRoute) {
    echo "   - URI: " . $bundleSubmitRoute->uri() . PHP_EOL;
    echo "   - Methods: " . implode(', ', $bundleSubmitRoute->methods()) . PHP_EOL;
}

echo PHP_EOL;

// Step 5: Test courses availability
echo "📋 STEP 5: TESTING COURSES AVAILABILITY" . PHP_EOL;
echo "=======================================" . PHP_EOL;

$courses = Course::where('price', '>', 0)->limit(3)->get();
echo "✅ Available paid courses: " . $courses->count() . PHP_EOL;

foreach ($courses as $course) {
    echo "   - Course {$course->id}: {$course->name} - {$course->price} EGP" . PHP_EOL;
}

if ($courses->count() < 2) {
    echo "❌ Not enough courses for bundle testing!" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Step 6: AGGRESSIVE BUNDLE PAYMENT SIMULATION
echo "📋 STEP 6: AGGRESSIVE BUNDLE PAYMENT SIMULATION" . PHP_EOL;
echo "===============================================" . PHP_EOL;

$testCourseIds = $courses->take(2)->pluck('id')->toArray();
echo "🎯 Testing with courses: [" . implode(', ', $testCourseIds) . "]" . PHP_EOL;

// Clear any existing payments for this user and these courses
Payment::where('user_id', $testUser->id)
    ->whereIn('course_id', $testCourseIds)
    ->delete();

echo "🧹 Cleared existing payments for test courses" . PHP_EOL;

// Create test request data
$requestData = [
    'courses' => $testCourseIds,
    'payment_method' => 'vodafone_cash',
    'transaction_id' => 'TEST_BUNDLE_' . time(),
    'phone_number' => '01234567890',
    'total_amount' => 150.99,
    '_token' => csrf_token()
];

echo "📤 Simulating bundle payment submission with data:" . PHP_EOL;
foreach ($requestData as $key => $value) {
    if (is_array($value)) {
        echo "   - {$key}: [" . implode(', ', $value) . "]" . PHP_EOL;
    } else {
        echo "   - {$key}: {$value}" . PHP_EOL;
    }
}

echo PHP_EOL;

// Step 7: DIRECT CONTROLLER TESTING WITH LOGGING
echo "📋 STEP 7: DIRECT CONTROLLER TESTING WITH LOGGING" . PHP_EOL;
echo "=================================================" . PHP_EOL;

// Enable aggressive logging
Log::info('🔥 LETHAL DEBUG: Starting bundle payment test', [
    'user_id' => $testUser->id,
    'courses' => $testCourseIds,
    'timestamp' => now()
]);

try {
    $request = Request::create('/payment/submit-bundle-proof', 'POST', $requestData);
    $request->setUserResolver(function () use ($testUser) {
        return $testUser;
    });
    
    $controller = new PaymentProcessingController(app(\App\Services\PaymentService::class));
    
    echo "🚀 Calling submitBundlePaymentProof method..." . PHP_EOL;
    
    $response = $controller->submitBundlePaymentProof($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "✅ Controller response:" . PHP_EOL;
    echo "   - Status Code: " . $response->getStatusCode() . PHP_EOL;
    echo "   - Success: " . ($responseData['success'] ? 'YES' : 'NO') . PHP_EOL;
    echo "   - Message: " . ($responseData['message'] ?? 'No message') . PHP_EOL;
    
    if (isset($responseData['bundle_id'])) {
        echo "   - Bundle ID: " . $responseData['bundle_id'] . PHP_EOL;
    }
    
    if (isset($responseData['payments_created'])) {
        echo "   - Payments Created: " . $responseData['payments_created'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . PHP_EOL;
    echo "❌ Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL;

// Step 8: VERIFY DATABASE RECORDS
echo "📋 STEP 8: VERIFYING DATABASE RECORDS" . PHP_EOL;
echo "=====================================" . PHP_EOL;

$createdPayments = Payment::where('user_id', $testUser->id)
    ->whereIn('course_id', $testCourseIds)
    ->orderBy('created_at', 'desc')
    ->get();

echo "✅ Payments found after submission: " . $createdPayments->count() . PHP_EOL;

foreach ($createdPayments as $payment) {
    $paymentData = json_decode($payment->payment_data, true);
    echo "   - Payment ID: {$payment->id}" . PHP_EOL;
    echo "     Course ID: {$payment->course_id}" . PHP_EOL;
    echo "     Amount: {$payment->amount} EGP" . PHP_EOL;
    echo "     Status: {$payment->status}" . PHP_EOL;
    echo "     Is Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
    echo "     Payment Method: {$payment->payment_method}" . PHP_EOL;
    echo "     Transaction ID: " . ($paymentData['transaction_id'] ?? 'N/A') . PHP_EOL;
    echo "     Bundle ID: " . ($paymentData['bundle_id'] ?? 'N/A') . PHP_EOL;
    echo "     Created: {$payment->created_at}" . PHP_EOL;
    echo "     ---" . PHP_EOL;
}

echo PHP_EOL;

// Step 9: CHECK LOGS
echo "📋 STEP 9: CHECKING RECENT LOGS" . PHP_EOL;
echo "===============================" . PHP_EOL;

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $recentLines = array_slice($logLines, -20); // Last 20 lines
    
    echo "✅ Recent log entries:" . PHP_EOL;
    foreach ($recentLines as $line) {
        if (!empty(trim($line))) {
            echo "   " . $line . PHP_EOL;
        }
    }
} else {
    echo "❌ Log file not found!" . PHP_EOL;
}

echo PHP_EOL;

// Step 10: FRONTEND BUTTON STATE TESTING
echo "📋 STEP 10: FRONTEND BUTTON STATE TESTING" . PHP_EOL;
echo "==========================================" . PHP_EOL;

echo "🎯 Testing how courses.blade.php should display buttons..." . PHP_EOL;

foreach ($testCourseIds as $courseId) {
    $course = Course::find($courseId);
    $userPayment = Payment::where('user_id', $testUser->id)
        ->where('course_id', $courseId)
        ->where('status', 'pending')
        ->first();
    
    $isEnrolled = $testUser->courses()->where('course_id', $courseId)->where('is_enrolled', true)->exists();
    $hasPendingPayment = (bool) $userPayment;
    
    echo "   Course {$courseId} ({$course->name}):" . PHP_EOL;
    echo "     - Is Enrolled: " . ($isEnrolled ? 'YES' : 'NO') . PHP_EOL;
    echo "     - Has Pending Payment: " . ($hasPendingPayment ? 'YES' : 'NO') . PHP_EOL;
    echo "     - Expected Button State: " . ($isEnrolled ? 'CONTINUE LEARNING' : ($hasPendingPayment ? 'PAYMENT PENDING' : 'ENROLL NOW')) . PHP_EOL;
    echo PHP_EOL;
}

echo PHP_EOL;

// FINAL SUMMARY
echo "🔥 LETHAL DEBUG ENGINE SUMMARY 🔥" . PHP_EOL;
echo "=================================" . PHP_EOL;

$finalPaymentCount = Payment::where('user_id', $testUser->id)
    ->whereIn('course_id', $testCourseIds)
    ->count();

$finalBundleCount = Payment::where('user_id', $testUser->id)
    ->whereIn('course_id', $testCourseIds)
    ->where('is_bundle', true)
    ->count();

echo "✅ Final payment records created: {$finalPaymentCount}" . PHP_EOL;
echo "✅ Final bundle payment records: {$finalBundleCount}" . PHP_EOL;

if ($finalPaymentCount > 0 && $finalBundleCount > 0) {
    echo "🚀 SUCCESS: Bundle payments are being created!" . PHP_EOL;
    echo "🎯 Issue might be in frontend display or different user context" . PHP_EOL;
} else {
    echo "❌ FAILURE: Bundle payments are NOT being created!" . PHP_EOL;
    echo "🔍 Check controller logic and database constraints" . PHP_EOL;
}

echo PHP_EOL;
echo "🔥 DEBUG ENGINE COMPLETE - CHECK RESULTS ABOVE 🔥" . PHP_EOL;