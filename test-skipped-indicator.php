<?php

/**
 * Test script to verify skipped indicator functionality
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Testing skipped indicator functionality...\n";

// 1. Check current skipped mistakes
$skippedMistakes = Mistake::where('skipped', true)->get();
echo "Found " . $skippedMistakes->count() . " skipped mistakes\n";

foreach ($skippedMistakes as $mistake) {
    echo "- Question ID: {$mistake->question_id}, Skipped: " . var_export($mistake->skipped, true) . " (type: " . gettype($mistake->skipped) . ")\n";
}

// 2. If no skipped mistakes exist, create one for testing
if ($skippedMistakes->count() === 0) {
    echo "\nNo skipped mistakes found. Creating a test skipped mistake...\n";
    
    // Find the first mistake and mark it as skipped
    $firstMistake = Mistake::first();
    if ($firstMistake) {
        $firstMistake->skipped = true;
        $firstMistake->save();
        
        echo "✅ Marked mistake with question_id {$firstMistake->question_id} as skipped\n";
        echo "- Skipped value: " . var_export($firstMistake->skipped, true) . " (type: " . gettype($firstMistake->skipped) . ")\n";
    } else {
        echo "❌ No mistakes found in the database\n";
    }
}

// 3. Test the conditional logic that will be used in the view
echo "\n🔍 Testing view conditional logic:\n";
$testMistakes = Mistake::limit(5)->get();

foreach ($testMistakes as $mistake) {
    $shouldShowIndicator = ($mistake->skipped === true || $mistake->skipped === 1 || $mistake->skipped === '1');
    echo "Question ID {$mistake->question_id}: ";
    echo "skipped=" . var_export($mistake->skipped, true) . " ";
    echo "(" . gettype($mistake->skipped) . ") ";
    echo "→ Show indicator: " . ($shouldShowIndicator ? 'YES' : 'NO') . "\n";
}

echo "\n✅ Test completed!\n";
echo "Now visit the mistakes page to verify the baby blue dot indicators are showing.\n";