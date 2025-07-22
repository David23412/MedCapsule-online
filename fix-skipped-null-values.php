<?php

/**
 * Fix for NULL values in the skipped field of the mistakes table
 * 
 * This script directly updates the database to ensure all NULL values
 * in the skipped field are set to false (0).
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Starting fix for NULL skipped values...\n";

// 1. Count mistakes with NULL skipped values
$nullCount = DB::table('mistakes')
    ->whereNull('skipped')
    ->count();

echo "Found {$nullCount} mistakes with NULL 'skipped' values\n";

// 2. Update NULL values to false (0)
if ($nullCount > 0) {
    $updated = DB::table('mistakes')
        ->whereNull('skipped')
        ->update(['skipped' => false]);
    
    echo "✅ Updated {$updated} records: NULL → false\n";
}

// 3. Verify the fix
$remainingNullCount = DB::table('mistakes')
    ->whereNull('skipped')
    ->count();

echo "Remaining NULL values: {$remainingNullCount}\n";

// 4. Check for specific mistake with ID 5
$mistake5 = DB::table('mistakes')->where('question_id', 5)->first();
if ($mistake5) {
    echo "Mistake with question_id 5:\n";
    echo "- skipped value: " . var_export($mistake5->skipped, true) . "\n";
    echo "- skipped type: " . gettype($mistake5->skipped) . "\n";
    
    // Fix this specific mistake if needed
    if ($mistake5->skipped === null) {
        DB::table('mistakes')
            ->where('question_id', 5)
            ->update(['skipped' => false]);
        
        echo "✅ Fixed skipped value for question_id 5\n";
        
        // Verify the fix
        $mistake5Updated = DB::table('mistakes')->where('question_id', 5)->first();
        echo "Updated value: " . var_export($mistake5Updated->skipped, true) . "\n";
    }
} else {
    echo "No mistake found with question_id 5\n";
}

echo "\n✅ Fix completed!\n";