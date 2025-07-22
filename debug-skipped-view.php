<?php

/**
 * Debug script to add temporary debug output to the view
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Adding temporary debug output to the view...\n";

$viewPath = __DIR__ . '/resources/views/mistakes.blade.php';
$viewContent = file_get_contents($viewPath);

// Find the info-boxes section
$infoBoxesPattern = '<div class="info-boxes">';
$position = strpos($viewContent, $infoBoxesPattern);

if ($position !== false) {
    // Add debug output before the info-boxes section
    $debugOutput = <<<'HTML'
<div class="info-boxes">
                                    <!-- DEBUG OUTPUT -->
                                    <div style="background: #f8d7da; color: #721c24; padding: 5px; margin-bottom: 10px; border-radius: 4px; font-size: 12px;">
                                        DEBUG: skipped={{ var_export($mistake->skipped, true) }} | 
                                        type={{ gettype($mistake->skipped) }} | 
                                        skipped==1: {{ $mistake->skipped == 1 ? 'true' : 'false' }} | 
                                        skipped===true: {{ $mistake->skipped === true ? 'true' : 'false' }}
                                    </div>
HTML;

    $viewContent = str_replace($infoBoxesPattern, $debugOutput, $viewContent);
    file_put_contents($viewPath, $viewContent);
    echo "✅ Added debug output to view\n";
} else {
    echo "❌ Could not find position to add debug output\n";
}

// Force update all mistakes to have skipped=1
echo "Updating all mistakes to have skipped=1...\n";
$updated = DB::table('mistakes')->update(['skipped' => 1]);
echo "✅ Updated $updated mistakes\n";

echo "\n✅ Debug completed! Please refresh the page to see the debug output.\n";