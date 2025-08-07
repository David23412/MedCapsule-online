<?php

/**
 * 🔍 REAL SINGLE PAYMENT TEST 🔍
 * Test single payment exactly as it would happen from the browser
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

echo "🔍 REAL SINGLE PAYMENT TEST" . PHP_EOL;
echo "===========================" . PHP_EOL;

// Setup
$user = User::first();
Auth::login($user);
$course = Course::where('price', '>', 0)->first();

echo "✅ User: {$user->name}" . PHP_EOL;
echo "✅ Course: {$course->name}" . PHP_EOL;

// Clear existing payments for this course
Payment::where('user_id', $user->id)->where('course_id', $course->id)->delete();

echo "📤 Testing single payment exactly as browser would send it..." . PHP_EOL;

// Test 1: Without payment_method (current broken behavior)
echo PHP_EOL . "TEST 1: Without payment_method (current behavior)" . PHP_EOL;
try {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $request = Request::create("/payments/submit-proof-course/{$course->id}", 'POST', [
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        'transaction_id' => 'REAL_TEST_NO_METHOD_' . time(),
        '_token' => csrf_token()
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
    ]);
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    $response = app()->handle($request);
    echo "Response Status: " . $response->getStatusCode() . PHP_EOL;
    
    if ($response->getStatusCode() === 200) {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $data = json_decode($response->getContent(), true);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        if ($data && isset($data['success']) && $data['success']) {
            $payment = Payment::find($data['payment_id']);
            echo "✅ Payment created with method: " . $payment->payment_method . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

// Clean up
Payment::where('user_id', $user->id)->where('course_id', $course->id)->delete();

// Test 2: With payment_method (fixed behavior)
echo PHP_EOL . "TEST 2: With payment_method (fixed behavior)" . PHP_EOL;
try {
    $request = Request::create("/payments/submit-proof-course/{$course->id}", 'POST', [
        'transaction_id' => 'REAL_TEST_WITH_METHOD_' . time(),
        'payment_method' => 'vodafone_cash',
        '_token' => csrf_token()
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
    ]);
    
    $response = app()->handle($request);
    echo "Response Status: " . $response->getStatusCode() . PHP_EOL;
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        if ($data && isset($data['success']) && $data['success']) {
            $payment = Payment::find($data['payment_id']);
            echo "✅ Payment created with method: " . $payment->payment_method . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "🔍 REAL TEST COMPLETE" . PHP_EOL;