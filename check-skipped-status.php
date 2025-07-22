<?php

/**
 * Check and fix skipped status in the database and view
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Checking skipped status in database...\n";

// 1. Check all mistakes in the database
$allMistakes = DB::table('mistakes')->get();
echo "Total mistakes in database: " . $allMistakes->count() . "\n";

$skippedCount = 0;
foreach ($allMistakes as $mistake) {
    if ($mistake->skipped) {
        $skippedCount++;
        echo "- Mistake ID: {$mistake->id}, Question ID: {$mistake->question_id}, Skipped: " . var_export($mistake->skipped, true) . "\n";
    }
}
echo "Total skipped mistakes: $skippedCount\n\n";

// 2. Check the view code to ensure it's correctly displaying skipped status
echo "🔍 Checking view code...\n";
$viewPath = __DIR__ . '/resources/views/mistakes.blade.php';
$viewContent = file_get_contents($viewPath);

// Find the info-boxes section
$infoBoxesPattern = '/\<div class="info-boxes"\>.*?@if\(\$mistake->question_id == 5 \|\| \$mistake->skipped\)/s';
if (preg_match($infoBoxesPattern, $viewContent)) {
    echo "✅ View has correct condition for skipped indicator\n";
} else {
    echo "❌ View has incorrect condition for skipped indicator\n";
    
    // Fix the condition
    $oldPattern = '<div class="info-boxes">
                                    @if($mistake->question_id == 5';
    $newPattern = '<div class="info-boxes">
                                    @if($mistake->skipped';
    
    if (strpos($viewContent, $oldPattern) !== false) {
        $viewContent = str_replace($oldPattern, $newPattern, $viewContent);
        file_put_contents($viewPath, $viewContent);
        echo "✅ Fixed condition in view\n";
    } else {
        echo "❌ Could not find pattern to replace\n";
    }
}

// 3. Check the MistakeController to ensure it's correctly loading skipped status
echo "\n🔍 Checking MistakeController...\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/MistakeController.php';
$controllerContent = file_get_contents($controllerPath);

// Check if the controller is selecting all fields
if (strpos($controllerContent, "->select('*')") !== false) {
    echo "✅ Controller is selecting all fields\n";
} else {
    echo "❌ Controller might not be selecting all fields\n";
}

// 4. Force update all mistakes to have skipped=true for testing
echo "\n🔧 Would you like to force all mistakes to have skipped=true for testing? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) == 'y') {
    echo "Updating all mistakes to have skipped=true...\n";
    $updated = DB::table('mistakes')->update(['skipped' => true]);
    echo "✅ Updated $updated mistakes\n";
} else {
    echo "Skipping update\n";
}

echo "\n✅ Check completed!\n";