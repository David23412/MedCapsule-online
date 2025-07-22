<?php

/**
 * Test the actual view rendering to see if skipped badges appear
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;
use App\Models\User;

echo "🔍 Testing view rendering for skipped badges...\n";

// Get a user with mistakes
$user = User::find(11);
if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

// Simulate authentication
auth()->login($user);

echo "✅ Authenticated as user: {$user->name} (ID: {$user->id})\n";

// Get mistakes using the same logic as MistakeController
$mistakes = Mistake::where('user_id', $user->id)
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

echo "Found " . $mistakes->count() . " mistakes\n";

$skippedCount = 0;
foreach ($mistakes as $mistake) {
    if ($mistake->skipped) {
        $skippedCount++;
        echo "✅ Question {$mistake->question_id}: SKIPPED (will show badge)\n";
    } else {
        echo "⚪ Question {$mistake->question_id}: NOT SKIPPED\n";
    }
}

echo "\n📊 Summary:\n";
echo "- Total mistakes: " . $mistakes->count() . "\n";
echo "- Skipped mistakes: {$skippedCount}\n";
echo "- Expected badges to show: {$skippedCount}\n";

if ($skippedCount > 0) {
    echo "\n✅ Skipped badges SHOULD be visible on the mistakes page!\n";
    echo "If you don't see them:\n";
    echo "1. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)\n";
    echo "2. Check browser developer tools for any JavaScript errors\n";
    echo "3. Look for the yellow DEBUG messages on the page\n";
} else {
    echo "\n⚠️ No skipped mistakes found. Creating one for testing...\n";
    
    $firstMistake = $mistakes->first();
    if ($firstMistake) {
        $firstMistake->skipped = true;
        $firstMistake->save();
        echo "✅ Marked question {$firstMistake->question_id} as skipped\n";
    }
}

echo "\n🌐 Now visit: http://localhost:8000/mistakes\n";
echo "Look for:\n";
echo "- Pulsing blue 'SKIPPED' badges in the bottom-right of mistake cards\n";
echo "- Yellow DEBUG messages showing skipped=true\n";

echo "\n✅ Test completed!\n";