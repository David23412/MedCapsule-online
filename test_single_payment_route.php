<?php

/**
 * 🔍 SINGLE PAYMENT ROUTE TEST 🔍
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "🔍 SINGLE PAYMENT ROUTE TEST" . PHP_EOL;
echo "============================" . PHP_EOL;

// Setup
$user = User::first();
Auth::login($user);
$course = Course::where('price', '>', 0)->first();

echo "✅ User: {$user->name}" . PHP_EOL;
echo "✅ Course: {$course->name}" . PHP_EOL;

// Clear existing payments for this course
Payment::where('user_id', $user->id)->where('course_id', $course->id)->delete();

echo "📤 Testing route: /payments/submit-proof-course/{$course->id}" . PHP_EOL;

try {
    // Create a proper request to the route
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $request = Request::create("/payments/submit-proof-course/{$course->id}", 'POST', [
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        'transaction_id' => 'ROUTE_TEST_' . time(),
        'payment_method' => 'vodafone_cash'
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        'HTTP_X_CSRF_TOKEN' => csrf_token()
    ]);
    
    echo "📋 Request URL: " . $request->url() . PHP_EOL;
    echo "📋 Request method: " . $request->method() . PHP_EOL;
    echo "📋 Request data: " . json_encode($request->all()) . PHP_EOL;
    
    $response = app()->handle($request);
    
    echo "✅ Response status: " . $response->getStatusCode() . PHP_EOL;
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            echo "✅ Response content: " . substr($response->getContent(), 0, 500) . PHP_EOL;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        if ($data && isset($data['success'])) {
            echo "✅ JSON Response: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
            if (isset($data['payment_id'])) {
                $payment = Payment::find($data['payment_id']);
                echo "✅ Payment created with method: " . $payment->payment_method . PHP_EOL;
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
    echo "❌ File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "🔍 ROUTE TEST COMPLETE" . PHP_EOL;