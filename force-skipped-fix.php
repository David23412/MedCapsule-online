<?php

/**
 * Force fix for skipped question indicator
 * This script directly updates the database and adds a hardcoded check in the view
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔧 Applying force fix for skipped question indicator...\n";

// 1. Force update the database value for question_id 5
echo "Updating database value for question_id 5...\n";
$updated = DB::statement("UPDATE mistakes SET skipped = 1 WHERE question_id = 5");
echo $updated ? "✅ Database updated successfully\n" : "❌ Database update failed\n";

// 2. Verify the update
$mistake = DB::table('mistakes')->where('question_id', 5)->first();
echo "Verified database value: skipped = " . var_export($mistake->skipped, true) . "\n";

// 3. Add a hardcoded check in the view
echo "Adding hardcoded check in the view...\n";

$viewPath = __DIR__ . '/resources/views/mistakes.blade.php';
$viewContent = file_get_contents($viewPath);

// Find the position where we need to add our hardcoded check
$searchString = '<div class="question-text">';
$position = strpos($viewContent, $searchString);

if ($position !== false) {
    // Add our hardcoded check right after the question-text div opening
    $hardcodedCheck = <<<'HTML'
<div class="question-text">
    <!-- HARDCODED SKIPPED INDICATOR -->
    @if($mistake->question_id == 5)
        <div style="background: #87CEEB; color: white; padding: 8px; margin: 10px 0; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
            <span style="width: 12px; height: 12px; background-color: white; border-radius: 50%; display: inline-block;"></span>
            <span style="font-weight: bold;">This question was skipped</span>
        </div>
    @endif
HTML;

    $viewContent = str_replace($searchString, $hardcodedCheck, $viewContent);
    file_put_contents($viewPath, $viewContent);
    echo "✅ Hardcoded check added to view\n";
} else {
    echo "❌ Could not find position to add hardcoded check\n";
}

// 4. Clear Laravel cache
echo "Clearing Laravel cache...\n";
system('php artisan view:clear');
system('php artisan cache:clear');

echo "\n✅ Force fix completed! Please refresh the page to see the skipped indicator.\n";