<?php

/**
 * Debug the exact controller query to see what's happening
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mistake;

echo "🔍 Debugging controller query...\n";

$userId = 11;
$courseFilter = null;

// Exact same query as in the controller
$mistakesQuery = Mistake::where('user_id', $userId)
    ->where('mastered', false)  // Only show non-mastered mistakes
    ->select('*')  // Explicitly select all fields including 'skipped'
    ->with(['question' => function($query) {
        $query->select('id', 'question', 'topic_id', 'image_url', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation');
        $query->with(['topic' => function($query) {
            $query->select('id', 'name', 'course_id');
            $query->with(['course' => function($query) {
                $query->select('id', 'name');
            }]);
        }]);
    }]);

// Apply course filter if specified
if ($courseFilter) {
    $mistakesQuery->whereHas('question.topic.course', function($query) use ($courseFilter) {
        $query->where('id', $courseFilter);
    });
}

$mistakes = $mistakesQuery->orderBy('last_attempt_date', 'desc')->get();

echo "Found " . $mistakes->count() . " mistakes\n";

foreach ($mistakes as $mistake) {
    echo "Mistake ID: {$mistake->id}\n";
    echo "- Question ID: {$mistake->question_id}\n";
    echo "- Skipped (raw): " . var_export($mistake->getRawOriginal('skipped'), true) . "\n";
    echo "- Skipped (cast): " . var_export($mistake->skipped, true) . " (type: " . gettype($mistake->skipped) . ")\n";
    echo "- All attributes: " . json_encode($mistake->getAttributes()) . "\n";
    echo "\n";
}

// Also test a direct query without Eloquent
echo "🔍 Direct database query:\n";
$rawMistake = DB::table('mistakes')->where('question_id', 5)->first();
if ($rawMistake) {
    echo "Raw database skipped value: " . var_export($rawMistake->skipped, true) . " (type: " . gettype($rawMistake->skipped) . ")\n";
}

echo "\n✅ Debug completed!\n";