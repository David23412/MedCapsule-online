<?php

/**
 * Debug script to test the actual view rendering logic
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Testing actual view rendering logic...\n";

// Get the exact same query as the MistakeController
$userId = 11; // Based on your database output
$mistakes = Mistake::where('user_id', $userId)
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

echo "Found " . $mistakes->count() . " mistakes for user {$userId}\n\n";

foreach ($mistakes as $mistake) {
    echo "Question ID: {$mistake->question_id}\n";
    echo "- Raw skipped value: " . var_export($mistake->skipped, true) . "\n";
    echo "- Type: " . gettype($mistake->skipped) . "\n";
    echo "- Blade condition (\$mistake->skipped): " . ($mistake->skipped ? 'TRUE (will show badge)' : 'FALSE (will NOT show badge)') . "\n";
    echo "- Alternative check (\$mistake->skipped == true): " . ($mistake->skipped == true ? 'TRUE' : 'FALSE') . "\n";
    echo "- Alternative check (\$mistake->skipped === true): " . ($mistake->skipped === true ? 'TRUE' : 'FALSE') . "\n";
    echo "- Alternative check ((bool)\$mistake->skipped): " . ((bool)$mistake->skipped ? 'TRUE' : 'FALSE') . "\n";
    
    // Test the exact HTML that would be generated
    if ($mistake->skipped) {
        echo "✅ HTML would be generated:\n";
        echo '<div class="info-box" style="background: rgba(135, 206, 235, 0.1); border-color: rgba(135, 206, 235, 0.4);">' . "\n";
        echo '    <i class="fas fa-forward" style="color: #87CEEB; margin-right: 4px;"></i>' . "\n";
        echo '    <div class="info-box-value" style="color: #5a9fd4; font-weight: 500;">skipped</div>' . "\n";
        echo '</div>' . "\n";
    } else {
        echo "❌ NO HTML would be generated (badge hidden)\n";
    }
    
    echo "---\n";
}

echo "\n✅ Debug completed!\n";