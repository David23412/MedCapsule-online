<?php

/**
 * Fix for skipped questions indicator not showing properly in mistakes.blade.php
 * 
 * This script checks for any inconsistencies in the 'skipped' field in the mistakes table
 * and ensures that the UI correctly displays the baby blue dot indicator for skipped questions.
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Starting skipped questions indicator fix...\n";

// 1. Check for any mistakes with NULL skipped values and fix them
$nullSkippedCount = DB::table('mistakes')
    ->whereNull('skipped')
    ->count();

if ($nullSkippedCount > 0) {
    echo "⚠️ Found {$nullSkippedCount} mistakes with NULL 'skipped' values. Fixing...\n";
    
    DB::table('mistakes')
        ->whereNull('skipped')
        ->update(['skipped' => false]);
    
    echo "✅ Fixed NULL values in 'skipped' field\n";
} else {
    echo "✅ No NULL values found in 'skipped' field\n";
}

// 2. Check for type inconsistencies (should be boolean)
$mistakes = Mistake::all();
$typeIssues = 0;

foreach ($mistakes as $mistake) {
    if (!is_bool($mistake->skipped)) {
        $typeIssues++;
        echo "⚠️ Found type issue with mistake ID {$mistake->id}, question ID {$mistake->question_id}. ";
        echo "Current type: " . gettype($mistake->skipped) . ", Value: " . var_export($mistake->skipped, true) . "\n";
        
        // Fix the type issue
        $mistake->skipped = (bool)$mistake->skipped;
        $mistake->save();
        echo "   Fixed type issue for mistake ID {$mistake->id}\n";
    }
}

if ($typeIssues === 0) {
    echo "✅ No type issues found in 'skipped' field\n";
} else {
    echo "✅ Fixed {$typeIssues} type issues in 'skipped' field\n";
}

// 3. Verify the UI rendering in mistakes.blade.php
echo "\n🔍 Checking UI rendering in mistakes.blade.php...\n";

// Check if the skipped indicator code exists in the view
$mistakesView = file_get_contents(__DIR__ . '/resources/views/mistakes.blade.php');
if (strpos($mistakesView, 'skipped-indicator') !== false && 
    strpos($mistakesView, 'skipped-dot') !== false && 
    strpos($mistakesView, 'skipped-text') !== false) {
    echo "✅ Skipped indicator HTML elements found in mistakes.blade.php\n";
} else {
    echo "❌ Skipped indicator HTML elements not found in mistakes.blade.php. Please check the view file.\n";
}

// 4. Summary
echo "\n📊 Summary:\n";
echo "- Total mistakes: " . $mistakes->count() . "\n";
echo "- Skipped mistakes: " . $mistakes->where('skipped', true)->count() . "\n";
echo "- Fixed NULL values: " . ($nullSkippedCount > 0 ? "Yes ({$nullSkippedCount})" : "None found") . "\n";
echo "- Fixed type issues: " . ($typeIssues > 0 ? "Yes ({$typeIssues})" : "None found") . "\n";

echo "\n✅ Fix completed. Please check the mistakes page to verify the baby blue dot indicators are now showing correctly.\n";