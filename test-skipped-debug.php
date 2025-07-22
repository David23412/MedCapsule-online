<?php

/**
 * Simple test to debug why skipped badges aren't showing
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;
use App\Models\User;

echo "🔍 DEBUGGING SKIPPED BADGE ISSUE...\n\n";

// 1. Check raw database data
echo "1. RAW DATABASE CHECK:\n";
$rawMistakes = DB::table('mistakes')->where('skipped', 1)->get();
echo "Found " . $rawMistakes->count() . " mistakes with skipped=1 in database\n";

foreach ($rawMistakes->take(3) as $mistake) {
    echo "- ID: {$mistake->id}, Question: {$mistake->question_id}, Skipped: {$mistake->skipped} (type: " . gettype($mistake->skipped) . ")\n";
}

// 2. Check Eloquent model data
echo "\n2. ELOQUENT MODEL CHECK:\n";
$eloquentMistakes = Mistake::where('skipped', true)->get();
echo "Found " . $eloquentMistakes->count() . " mistakes with skipped=true via Eloquent\n";

foreach ($eloquentMistakes->take(3) as $mistake) {
    echo "- ID: {$mistake->id}, Question: {$mistake->question_id}, Skipped: " . var_export($mistake->skipped, true) . " (type: " . gettype($mistake->skipped) . ")\n";
}

// 3. Test the exact controller query
echo "\n3. CONTROLLER QUERY TEST:\n";
$userId = 11; // Your user ID
$controllerMistakes = Mistake::where('user_id', $userId)
    ->where('mastered', false)
    ->select('*')
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

echo "Controller query returned " . $controllerMistakes->count() . " mistakes\n";

$skippedInController = 0;
foreach ($controllerMistakes as $mistake) {
    if ($mistake->skipped) {
        $skippedInController++;
        echo "✅ Question {$mistake->question_id}: SKIPPED = " . var_export($mistake->skipped, true) . "\n";
    } else {
        echo "⚪ Question {$mistake->question_id}: NOT SKIPPED = " . var_export($mistake->skipped, true) . "\n";
    }
}

echo "\nSkipped mistakes in controller query: {$skippedInController}\n";

// 4. Test the Blade condition directly
echo "\n4. BLADE CONDITION TEST:\n";
foreach ($controllerMistakes->take(3) as $mistake) {
    $bladeCondition = $mistake->skipped;
    echo "Question {$mistake->question_id}:\n";
    echo "  - \$mistake->skipped = " . var_export($mistake->skipped, true) . "\n";
    echo "  - Blade @if(\$mistake->skipped) would: " . ($bladeCondition ? "PASS (show badge)" : "FAIL (hide badge)") . "\n";
    echo "  - Alternative @if(\$mistake->skipped == true) would: " . ($mistake->skipped == true ? "PASS" : "FAIL") . "\n";
    echo "  - Alternative @if(\$mistake->skipped === true) would: " . ($mistake->skipped === true ? "PASS" : "FAIL") . "\n";
}

// 5. Generate actual HTML to test
echo "\n5. HTML GENERATION TEST:\n";
$testMistake = $controllerMistakes->where('skipped', true)->first();
if ($testMistake) {
    echo "Testing HTML generation for question {$testMistake->question_id}:\n";
    
    $html = '';
    if ($testMistake->skipped) {
        $html = '<div class="info-box skipped-badge" style="background: rgba(135, 206, 235, 0.2) !important; border: 2px solid rgba(135, 206, 235, 0.6) !important; z-index: 10 !important;">';
        $html .= '<i class="fas fa-forward" style="color: #4a90e2 !important; margin-right: 4px; font-size: 0.8rem;"></i>';
        $html .= '<div class="info-box-value" style="color: #2c5aa0 !important; font-weight: 600 !important; font-size: 0.8rem;">SKIPPED</div>';
        $html .= '</div>';
        
        echo "✅ HTML WOULD BE GENERATED:\n";
        echo $html . "\n";
    } else {
        echo "❌ NO HTML WOULD BE GENERATED\n";
    }
} else {
    echo "❌ No skipped mistakes found to test HTML generation\n";
}

echo "\n6. SUMMARY:\n";
echo "- Raw DB skipped count: " . $rawMistakes->count() . "\n";
echo "- Eloquent skipped count: " . $eloquentMistakes->count() . "\n";
echo "- Controller query skipped count: {$skippedInController}\n";

if ($skippedInController > 0) {
    echo "\n✅ BADGES SHOULD BE VISIBLE!\n";
    echo "If you don't see them, the issue is likely:\n";
    echo "1. Browser cache - try hard refresh (Ctrl+F5)\n";
    echo "2. CSS hiding the badges\n";
    echo "3. JavaScript interfering\n";
    echo "4. Different user logged in\n";
} else {
    echo "\n❌ NO SKIPPED BADGES EXPECTED\n";
    echo "The data shows no skipped mistakes for this user.\n";
}

echo "\n✅ Debug completed!\n";