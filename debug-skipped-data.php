<?php

/**
 * Debug script to check what's happening with the skipped data
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Debugging skipped data for question_id 5...\n";

// 1. Check raw database value
$rawData = DB::table('mistakes')->where('question_id', 5)->first();
if ($rawData) {
    echo "Raw database data:\n";
    echo "- ID: {$rawData->id}\n";
    echo "- question_id: {$rawData->question_id}\n";
    echo "- skipped: " . var_export($rawData->skipped, true) . " (type: " . gettype($rawData->skipped) . ")\n";
    echo "- skipped == 1: " . ($rawData->skipped == 1 ? 'true' : 'false') . "\n";
    echo "- skipped === 1: " . ($rawData->skipped === 1 ? 'true' : 'false') . "\n";
    echo "- (bool)skipped: " . ((bool)$rawData->skipped ? 'true' : 'false') . "\n";
} else {
    echo "❌ No raw data found for question_id 5\n";
}

// 2. Check Eloquent model data
$mistake = Mistake::where('question_id', 5)->first();
if ($mistake) {
    echo "\nEloquent model data:\n";
    echo "- ID: {$mistake->id}\n";
    echo "- question_id: {$mistake->question_id}\n";
    echo "- skipped: " . var_export($mistake->skipped, true) . " (type: " . gettype($mistake->skipped) . ")\n";
    echo "- skipped == true: " . ($mistake->skipped == true ? 'true' : 'false') . "\n";
    echo "- skipped === true: " . ($mistake->skipped === true ? 'true' : 'false') . "\n";
    echo "- (bool)skipped: " . ((bool)$mistake->skipped ? 'true' : 'false') . "\n";
    
    // Check if the condition in the view would pass
    echo "\nView condition tests:\n";
    echo "- \$mistake->skipped: " . ($mistake->skipped ? 'PASS' : 'FAIL') . "\n";
    echo "- \$mistake->skipped == true: " . ($mistake->skipped == true ? 'PASS' : 'FAIL') . "\n";
    echo "- \$mistake->skipped === true: " . ($mistake->skipped === true ? 'PASS' : 'FAIL') . "\n";
} else {
    echo "❌ No Eloquent model found for question_id 5\n";
}

// 3. Test the exact query used in MistakeController
echo "\n🔍 Testing MistakeController query...\n";
$userId = 11; // Based on the database output you showed
$mistakes = Mistake::where('user_id', $userId)
    ->where('mastered', false)
    ->with(['question' => function($query) {
        $query->select('id', 'question', 'topic_id', 'image_url', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation');
        $query->with(['topic' => function($query) {
            $query->select('id', 'name', 'course_id');
            $query->with(['course' => function($query) {
                $query->select('id', 'name');
            }]);
        }]);
    }])
    ->orderBy('last_attempt_date', 'desc')
    ->get();

echo "Found " . $mistakes->count() . " mistakes\n";
foreach ($mistakes as $mistake) {
    if ($mistake->question_id == 5) {
        echo "Question 5 found in controller query:\n";
        echo "- skipped: " . var_export($mistake->skipped, true) . " (type: " . gettype($mistake->skipped) . ")\n";
        echo "- View condition would: " . ($mistake->skipped ? 'PASS' : 'FAIL') . "\n";
        break;
    }
}

echo "\n✅ Debug completed!\n";