<?php

/**
 * 🔍 BUNDLE PAYMENT ISSUE DIAGNOSTIC SCRIPT 🔍
 * This script will help identify why bundle payments aren't being recorded on your website
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🔍 BUNDLE PAYMENT ISSUE DIAGNOSTIC SCRIPT" . PHP_EOL;
echo "=========================================" . PHP_EOL;

// STEP 1: Check recent payment attempts
echo "📋 STEP 1: CHECKING RECENT PAYMENT ATTEMPTS" . PHP_EOL;

$recentPayments = Payment::where('created_at', '>=', now()->subDays(7))
    ->orderBy('created_at', 'desc')
    ->get();

echo "✅ Recent payments (last 7 days): " . $recentPayments->count() . PHP_EOL;

if ($recentPayments->count() > 0) {
    echo "📊 RECENT PAYMENT BREAKDOWN:" . PHP_EOL;
    foreach ($recentPayments as $payment) {
        $course = Course::find($payment->course_id);
        echo "   Payment ID: {$payment->id}" . PHP_EOL;
        echo "   Course: " . ($course ? $course->name : 'DELETED') . " (ID: {$payment->course_id})" . PHP_EOL;
        echo "   User ID: {$payment->user_id}" . PHP_EOL;
        echo "   Amount: {$payment->amount} EGP" . PHP_EOL;
        echo "   Method: {$payment->payment_method}" . PHP_EOL;
        echo "   Status: {$payment->status}" . PHP_EOL;
        echo "   Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            echo "   Created: {$payment->created_at}" . PHP_EOL;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        if ($payment->payment_data) {
            $data = is_string($payment->payment_data) ? json_decode($payment->payment_data, true) : $payment->payment_data;
            if (isset($data['transaction_id'])) {
                echo "   Transaction ID: {$data['transaction_id']}" . PHP_EOL;
            }
            if (isset($data['bundle_id'])) {
                echo "   Bundle ID: {$data['bundle_id']}" . PHP_EOL;
            }
        }
        echo "   ---" . PHP_EOL;
    }
} else {
    echo "❌ No recent payments found - this might indicate the issue!" . PHP_EOL;
}
echo PHP_EOL;

// STEP 2: Check for bundle payments specifically
echo "📋 STEP 2: CHECKING FOR BUNDLE PAYMENTS" . PHP_EOL;

$bundlePayments = Payment::where('is_bundle', true)->get();
echo "✅ Total bundle payments in database: " . $bundlePayments->count() . PHP_EOL;

if ($bundlePayments->count() > 0) {
    echo "📊 BUNDLE PAYMENT ANALYSIS:" . PHP_EOL;
    $bundleGroups = $bundlePayments->groupBy(function($payment) {
        $data = is_string($payment->payment_data) ? json_decode($payment->payment_data, true) : $payment->payment_data;
        return $data['bundle_id'] ?? 'unknown';
    });
    
    foreach ($bundleGroups as $bundleId => $payments) {
        echo "   Bundle ID: {$bundleId}" . PHP_EOL;
        echo "   Payments in bundle: " . $payments->count() . PHP_EOL;
        echo "   Total amount: " . $payments->sum('amount') . " EGP" . PHP_EOL;
        echo "   Status: " . $payments->pluck('status')->unique()->implode(', ') . PHP_EOL;
        echo "   ---" . PHP_EOL;
    }
} else {
    echo "❌ No bundle payments found in database!" . PHP_EOL;
}
echo PHP_EOL;

// STEP 3: Check application logs for bundle payment errors
echo "📋 STEP 3: CHECKING APPLICATION LOGS FOR ERRORS" . PHP_EOL;

try {
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
        $bundleErrors = [];
        
        // Look for bundle payment related errors
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -1000); // Last 1000 lines
        
        foreach ($recentLines as $line) {
            if (stripos($line, 'bundle') !== false && (stripos($line, 'error') !== false || stripos($line, 'exception') !== false)) {
                $bundleErrors[] = $line;
            }
        }
        
        if (!empty($bundleErrors)) {
            echo "❌ Found bundle payment errors in logs:" . PHP_EOL;
            foreach (array_slice($bundleErrors, -5) as $error) { // Show last 5 errors
                echo "   " . $error . PHP_EOL;
            }
        } else {
            echo "✅ No bundle payment errors found in recent logs" . PHP_EOL;
        }
    } else {
        echo "⚠️ Log file not found at: {$logPath}" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Error reading logs: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// STEP 4: Check route registration
echo "📋 STEP 4: CHECKING ROUTE REGISTRATION" . PHP_EOL;

$routes = app('router')->getRoutes();
$bundleRoutes = [
    'payment.bundle-options' => null,
    'payment.submit-bundle-proof' => null
];

foreach ($bundleRoutes as $routeName => $route) {
    $route = $routes->getByName($routeName);
    if ($route) {
        echo "✅ Route '{$routeName}' exists: " . $route->uri() . " [" . implode(', ', $route->methods()) . "]" . PHP_EOL;
    } else {
        echo "❌ Route '{$routeName}' NOT FOUND!" . PHP_EOL;
    }
}
echo PHP_EOL;

// STEP 5: Check database schema
echo "📋 STEP 5: CHECKING DATABASE SCHEMA" . PHP_EOL;

try {
    $columns = DB::select("DESCRIBE payments");
    $requiredColumns = ['is_bundle', 'bundle_courses', 'original_price', 'discount_amount'];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $existingColumns)) {
            echo "✅ Column '{$column}' exists in payments table" . PHP_EOL;
        } else {
            echo "❌ Column '{$column}' MISSING from payments table!" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking database schema: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// STEP 6: Test bundle payment controller directly
echo "📋 STEP 6: TESTING BUNDLE PAYMENT CONTROLLER DIRECTLY" . PHP_EOL;

try {
    $testUser = User::first();
    if ($testUser) {
        auth()->login($testUser);
        
        $testCourses = Course::where('price', '>', 0)->take(2)->pluck('id')->toArray();
        
        if (count($testCourses) >= 2) {
            $controller = new \App\Http\Controllers\Payment\PaymentOptionsController(app(\App\Services\PaymentService::class));
            $request = new \Illuminate\Http\Request();
            $request->merge(['courses' => $testCourses]);
            
            $response = $controller->getBundlePaymentOptions($request);
            $data = json_decode($response->getContent(), true);
            
            echo "✅ Bundle options controller test: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;
            if (!$data['success']) {
                echo "   Error: " . $data['message'] . PHP_EOL;
            }
        } else {
            echo "⚠️ Not enough paid courses for testing" . PHP_EOL;
        }
    } else {
        echo "❌ No users found for testing" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// STEP 7: Check for JavaScript/AJAX issues
echo "📋 STEP 7: FRONTEND INTEGRATION CHECKLIST" . PHP_EOL;
echo "⚠️ MANUAL CHECKS NEEDED:" . PHP_EOL;
echo "   1. Check browser console for JavaScript errors when submitting bundle payment" . PHP_EOL;
echo "   2. Check Network tab in browser dev tools for failed AJAX requests" . PHP_EOL;
echo "   3. Verify CSRF token is being sent with bundle payment requests" . PHP_EOL;
echo "   4. Check if bundle payment form is using correct route URLs" . PHP_EOL;
echo "   5. Verify form data is being serialized correctly" . PHP_EOL;
echo PHP_EOL;

// FINAL DIAGNOSIS
echo "🎯 DIAGNOSTIC SUMMARY" . PHP_EOL;
echo "====================" . PHP_EOL;

$issues = [];

if ($recentPayments->count() === 0) {
    $issues[] = "No recent payments found - possible frontend submission issue";
}

if ($bundlePayments->count() === 0) {
    $issues[] = "No bundle payments in database - bundle submission not working";
}

$bundleOptionsRoute = $routes->getByName('payment.bundle-options');
$bundleSubmitRoute = $routes->getByName('payment.submit-bundle-proof');

if (!$bundleOptionsRoute) {
    $issues[] = "Bundle options route missing";
}

if (!$bundleSubmitRoute) {
    $issues[] = "Bundle submit route missing";
}

if (empty($issues)) {
    echo "✅ No obvious backend issues found" . PHP_EOL;
    echo "🔍 The issue is likely in the frontend JavaScript or form submission" . PHP_EOL;
    echo "📋 Next steps:" . PHP_EOL;
    echo "   1. Check browser console for errors" . PHP_EOL;
    echo "   2. Check network requests in browser dev tools" . PHP_EOL;
    echo "   3. Verify bundle payment form is calling the correct endpoints" . PHP_EOL;
} else {
    echo "❌ ISSUES FOUND:" . PHP_EOL;
    foreach ($issues as $issue) {
        echo "   • {$issue}" . PHP_EOL;
    }
}

echo PHP_EOL . "🔍 DIAGNOSTIC COMPLETE" . PHP_EOL;