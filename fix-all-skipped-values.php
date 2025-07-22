<?php

/**
 * Comprehensive fix for skipped values in the mistakes table
 * 
 * This script:
 * 1. Fixes NULL values in the skipped field
 * 2. Ensures all values are proper booleans
 * 3. Specifically fixes the issue with question_id 5
 * 4. Provides detailed reporting
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Starting comprehensive fix for skipped values...\n";

// 1. Fix NULL values
$nullCount = DB::table('mistakes')
    ->whereNull('skipped')
    ->count();

echo "Found {$nullCount} mistakes with NULL 'skipped' values\n";

if ($nullCount > 0) {
    $updated = DB::table('mistakes')
        ->whereNull('skipped')
        ->update(['skipped' => false]);
    
    echo "✅ Updated {$updated} records: NULL → false\n";
}

// 2. Fix non-boolean values (if any)
$mistakes = Mistake::all();
$nonBooleanCount = 0;

foreach ($mistakes as $mistake) {
    if (!is_bool($mistake->skipped)) {
        $nonBooleanCount++;
        $oldValue = $mistake->skipped;
        $mistake->skipped = (bool)$mistake->skipped;
        $mistake->save();
        
        echo "Fixed non-boolean value for mistake ID {$mistake->id}: {$oldValue} → {$mistake->skipped}\n";
    }
}

echo "Fixed {$nonBooleanCount} non-boolean values\n";

// 3. Specifically fix question_id 5
$mistake5 = Mistake::where('question_id', 5)->first();
if ($mistake5) {
    echo "\nSpecifically fixing question_id 5:\n";
    echo "- Before: " . var_export($mistake5->skipped, true) . " (type: " . gettype($mistake5->skipped) . ")\n";
    
    // Force update directly in the database to ensure it takes effect
    DB::statement("UPDATE mistakes SET skipped = true WHERE question_id = 5");
    
    // Reload the mistake to verify
    $mistake5 = Mistake::where('question_id', 5)->first();
    echo "- After: " . var_export($mistake5->skipped, true) . " (type: " . gettype($mistake5->skipped) . ")\n";
} else {
    echo "\nNo mistake found with question_id 5\n";
}

// 4. Summary
echo "\n📊 Summary:\n";
echo "- Total mistakes: " . $mistakes->count() . "\n";
echo "- Fixed NULL values: " . $nullCount . "\n";
echo "- Fixed non-boolean values: " . $nonBooleanCount . "\n";
echo "- Skipped mistakes after fix: " . Mistake::where('skipped', true)->count() . "\n";

echo "\n✅ Fix completed!\n";

// 5. Verify the database schema
echo "\n🔍 Verifying database schema:\n";
$columns = DB::select("SHOW COLUMNS FROM mistakes WHERE Field = 'skipped'");
if (count($columns) > 0) {
    $column = $columns[0];
    echo "- Field: {$column->Field}\n";
    echo "- Type: {$column->Type}\n";
    echo "- Null: {$column->Null}\n";
    echo "- Default: " . ($column->Default === null ? 'NULL' : $column->Default) . "\n";
}

echo "\nDone!\n";