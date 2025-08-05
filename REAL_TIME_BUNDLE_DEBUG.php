<?php

/**
 * 🔥 REAL-TIME BUNDLE PAYMENT DEBUG MONITOR 🔥
 * RUN THIS SCRIPT AND THEN TRY YOUR BUNDLE PAYMENT
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔥 REAL-TIME BUNDLE PAYMENT DEBUG MONITOR 🔥" . PHP_EOL;
echo "=============================================" . PHP_EOL;
echo "This script will monitor for bundle payment attempts in real-time." . PHP_EOL;
echo "NOW GO TO YOUR BROWSER AND TRY THE BUNDLE PAYMENT!" . PHP_EOL;
echo PHP_EOL;

$logFile = storage_path('logs/laravel.log');
$lastSize = file_exists($logFile) ? filesize($logFile) : 0;

echo "📋 Monitoring log file: {$logFile}" . PHP_EOL;
echo "📋 Current log size: {$lastSize} bytes" . PHP_EOL;
echo "📋 Waiting for new log entries..." . PHP_EOL;
echo PHP_EOL;

// Also check current payments count
use App\Models\Payment;
$currentPaymentCount = Payment::count();
echo "📊 Current total payments in database: {$currentPaymentCount}" . PHP_EOL;
echo PHP_EOL;

$startTime = time();
$maxWaitTime = 300; // 5 minutes

while (true) {
    if (time() - $startTime > $maxWaitTime) {
        echo "⏰ Timeout reached. Stopping monitor." . PHP_EOL;
        break;
    }
    
    if (file_exists($logFile)) {
        $currentSize = filesize($logFile);
        
        if ($currentSize > $lastSize) {
            echo "🚨 NEW LOG ACTIVITY DETECTED!" . PHP_EOL;
            echo "📈 Log size changed from {$lastSize} to {$currentSize} bytes" . PHP_EOL;
            
            // Read the new content
            $handle = fopen($logFile, 'r');
            fseek($handle, $lastSize);
            $newContent = fread($handle, $currentSize - $lastSize);
            fclose($handle);
            
            echo "📝 NEW LOG CONTENT:" . PHP_EOL;
            echo "==================" . PHP_EOL;
            echo $newContent;
            echo "==================" . PHP_EOL;
            
            // Check if it's bundle payment related
            if (strpos($newContent, 'bundle') !== false || strpos($newContent, 'BUNDLE') !== false) {
                echo "🎯 BUNDLE PAYMENT ACTIVITY DETECTED!" . PHP_EOL;
            }
            
            if (strpos($newContent, 'submit-bundle-proof') !== false) {
                echo "🎯 BUNDLE PAYMENT SUBMISSION DETECTED!" . PHP_EOL;
            }
            
            if (strpos($newContent, 'error') !== false || strpos($newContent, 'ERROR') !== false) {
                echo "❌ ERROR DETECTED IN LOGS!" . PHP_EOL;
            }
            
            $lastSize = $currentSize;
            echo PHP_EOL;
        }
    }
    
    // Check for new payments every 5 seconds
    static $lastPaymentCheck = 0;
    if (time() - $lastPaymentCheck >= 5) {
        $newPaymentCount = Payment::count();
        if ($newPaymentCount > $currentPaymentCount) {
            echo "💰 NEW PAYMENT DETECTED!" . PHP_EOL;
            echo "📊 Payment count changed from {$currentPaymentCount} to {$newPaymentCount}" . PHP_EOL;
            
            // Get the latest payments
            $latestPayments = Payment::orderBy('created_at', 'desc')->limit($newPaymentCount - $currentPaymentCount)->get();
            foreach ($latestPayments as $payment) {
                echo "   - Payment ID: {$payment->id}" . PHP_EOL;
                echo "     User ID: {$payment->user_id}" . PHP_EOL;
                echo "     Course ID: {$payment->course_id}" . PHP_EOL;
                echo "     Amount: {$payment->amount}" . PHP_EOL;
                echo "     Status: {$payment->status}" . PHP_EOL;
                echo "     Is Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
                echo "     Created: {$payment->created_at}" . PHP_EOL;
                echo "     ---" . PHP_EOL;
            }
            
            $currentPaymentCount = $newPaymentCount;
            echo PHP_EOL;
        }
        $lastPaymentCheck = time();
    }
    
    sleep(1); // Check every second
}

echo "🔥 REAL-TIME MONITOR STOPPED 🔥" . PHP_EOL;