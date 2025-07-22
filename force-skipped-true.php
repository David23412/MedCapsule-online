<?php

/**
 * Force all mistakes to have skipped=true
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔧 Forcing all mistakes to have skipped=true...\n";

// Direct database update to ensure it works
$updated = DB::statement("UPDATE mistakes SET skipped = 1");
echo "✅ Updated database directly: " . ($updated ? "Success" : "Failed") . "\n";

// Verify the update
$allMistakes = Mistake::all();
$skippedCount = 0;
foreach ($allMistakes as $mistake) {
    if ($mistake->skipped) {
        $skippedCount++;
    }
}
echo "Total mistakes: " . $allMistakes->count() . "\n";
echo "Skipped mistakes after update: $skippedCount\n";

echo "\n✅ Force update completed!\n";