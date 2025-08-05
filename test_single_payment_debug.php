<?php

/**
 * 🔍 SINGLE PAYMENT DEBUG TEST 🔍
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

echo "🔍 SINGLE PAYMENT DEBUG TEST" . PHP_EOL;
echo "============================" . PHP_EOL;

// Setup
$user = User::first();
Auth::login($user);
$course = Course::where('price', '>', 0)->first();

echo "✅ User: {$user->name}" . PHP_EOL;
echo "✅ Course: {$course->name}" . PHP_EOL;

// Clear existing payments for this course
Payment::where('user_id', $user->id)->where('course_id', $course->id)->delete();

$controller = new PaymentProcessingController(app(PaymentService::class));

echo "📤 Testing single payment submission..." . PHP_EOL;

$request = new Request();
$request->merge([
    'transaction_id' => 'DEBUG_TEST_' . time(),
    'payment_method' => 'vodafone_cash'
]);

try {
    echo "📋 Request data: " . json_encode($request->all()) . PHP_EOL;
    
    $response = $controller->submitProof($request, $course->id);
    
    echo "✅ Response status: " . $response->getStatusCode() . PHP_EOL;
    echo "✅ Response content: " . $response->getContent() . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
    echo "❌ File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "❌ Trace: " . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "🔍 DEBUG TEST COMPLETE" . PHP_EOL;