<?php

/**
 * 🔍 SINGLE PAYMENT WITH HIDDEN FIELD TEST 🔍
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "🔍 SINGLE PAYMENT WITH HIDDEN FIELD TEST" . PHP_EOL;
echo "=========================================" . PHP_EOL;

// Setup
$user = User::find(2); // Use user ID 2 like in the log
Auth::login($user);
$course = Course::find(2); // Use course ID 2 like in the log

echo "✅ User: {$user->name} (ID: {$user->id})" . PHP_EOL;
echo "✅ Course: {$course->name} (ID: {$course->id})" . PHP_EOL;

// Clear existing payments for this course
Payment::where('user_id', $user->id)->where('course_id', $course->id)->delete();

echo "📤 Testing single payment with hidden field approach..." . PHP_EOL;

try {
    // Simulate form submission with hidden payment_method field
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $request = Request::create("/payments/submit-proof-course/{$course->id}", 'POST', [
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        'transaction_id' => 'HIDDEN_FIELD_TEST_' . time(),
        'payment_method' => 'vodafone_cash', // This would come from the hidden field
        '_token' => csrf_token()
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
    ]);
    
    echo "📋 Request data: " . json_encode($request->all()) . PHP_EOL;
    
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $response = app()->handle($request);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    
    echo "✅ Response status: " . $response->getStatusCode() . PHP_EOL;
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        if ($data && isset($data['success']) && $data['success']) {
            $payment = Payment::find($data['payment_id']);
            echo "✅ Payment created with method: " . $payment->payment_method . PHP_EOL;
            echo "✅ Payment ID: " . $payment->id . PHP_EOL;
        } else {
            echo "❌ Payment failed: " . ($data['message'] ?? 'Unknown error') . PHP_EOL;
        }
    } else {
        echo "❌ HTTP Error: " . $response->getStatusCode() . PHP_EOL;
        echo "Response: " . substr($response->getContent(), 0, 200) . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "🔍 HIDDEN FIELD TEST COMPLETE" . PHP_EOL;