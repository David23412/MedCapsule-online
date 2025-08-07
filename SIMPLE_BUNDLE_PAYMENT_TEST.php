<?php

/**
 * 🔥 SIMPLE BUNDLE PAYMENT TEST 🔥
 * DIRECT TEST WITHOUT DATABASE COMPLICATIONS
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

echo "🔥 SIMPLE BUNDLE PAYMENT TEST 🔥" . PHP_EOL;
echo "================================" . PHP_EOL;

// Step 1: Find a regular user (not admin)
$testUser = User::where('id', '!=', 1)->first();
if (!$testUser) {
    echo "❌ No regular user found, using admin" . PHP_EOL;
    $testUser = User::find(1);
}

Auth::login($testUser);
echo "✅ Using user: {$testUser->name} (ID: {$testUser->id})" . PHP_EOL;

// Step 2: Clear existing payments for clean test
Payment::where('user_id', $testUser->id)->delete();
echo "✅ Cleared existing payments" . PHP_EOL;

// Step 3: Get courses
$courses = Course::where('price', '>', 0)->limit(2)->get();
$testCourseIds = $courses->pluck('id')->toArray();
echo "✅ Testing with courses: [" . implode(', ', $testCourseIds) . "]" . PHP_EOL;

// Step 4: Create bundle payment request
$requestData = [
    'courses' => $testCourseIds,
    'payment_method' => 'vodafone_cash',
    'transaction_id' => 'SIMPLE_TEST_' . time(),
    'phone_number' => '01234567890',
    'total_amount' => 200.00,
    '_token' => csrf_token()
];

echo "📤 Request data:" . PHP_EOL;
print_r($requestData);

// Step 5: Call controller directly
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
    
    $controller = new PaymentProcessingController(app(\App\Services\PaymentService::class));
    
    echo "🚀 Calling controller..." . PHP_EOL;
    $response = $controller->submitBundlePaymentProof($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "✅ Response:" . PHP_EOL;
    echo "   Status: " . $response->getStatusCode() . PHP_EOL;
    echo "   Success: " . ($responseData['success'] ? 'YES' : 'NO') . PHP_EOL;
    echo "   Message: " . ($responseData['message'] ?? 'No message') . PHP_EOL;
    
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
            if (isset($responseData['bundle_id'])) {
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        echo "   Bundle ID: " . $responseData['bundle_id'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

// Step 6: Check created payments
$payments = Payment::where('user_id', $testUser->id)->get();
echo "✅ Payments created: " . $payments->count() . PHP_EOL;

foreach ($payments as $payment) {
    $data = json_decode($payment->payment_data, true);
    echo "   - Payment {$payment->id}: Course {$payment->course_id}, Amount {$payment->amount}, Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
    echo "     Transaction ID: " . ($data['transaction_id'] ?? 'N/A') . PHP_EOL;
    echo "     Bundle ID: " . ($data['bundle_id'] ?? 'N/A') . PHP_EOL;
}

echo PHP_EOL;
echo "🔥 TEST COMPLETE 🔥" . PHP_EOL;