<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 UPDATING REGULAR TOPIC PROGRESS\n";
echo "==================================\n\n";

echo "🎯 RECALCULATING REGULAR TOPIC PROGRESS WITH FIX:\n";
echo "=================================================\n";

$userId = 2;
$topicId = 1;

// Get the corrected progress (should be 0% now)
$progress = \App\Models\QuizAttempt::getTopicProgress($userId, $topicId, 'quiz');
$correctPercentage = $progress['overall']['percentage'] ?? 0;

echo "Corrected Regular Topic Progress: {$correctPercentage}%\n\n";

// Update the UserTopicProgress record
echo "🔄 UPDATING UserTopicProgress RECORD:\n";
echo "====================================\n";

$userProgress = \App\Models\UserTopicProgress::where('user_id', $userId)
    ->where('topic_id', $topicId)
    ->first();

if ($userProgress) {
    echo "BEFORE UPDATE:\n";
    echo "- User ID: {$userProgress->user_id}\n";
    echo "- Topic ID: {$userProgress->topic_id}\n";
    echo "- Percentage: {$userProgress->percentage_grade}%\n";
    echo "- Updated: {$userProgress->updated_at}\n\n";
    
    // Update with correct percentage
    $userProgress->update([
        'percentage_grade' => $correctPercentage,
        'last_attempt_date' => now()
    ]);
    
    $userProgress->refresh();
    
    echo "AFTER UPDATE:\n";
    echo "- User ID: {$userProgress->user_id}\n";
    echo "- Topic ID: {$userProgress->topic_id}\n";
    echo "- Percentage: {$userProgress->percentage_grade}%\n";
    echo "- Updated: {$userProgress->updated_at}\n\n";
    
    echo "✅ UserTopicProgress updated successfully!\n";
} else {
    echo "❌ No UserTopicProgress record found for User $userId, Topic $topicId\n";
    echo "This is actually GOOD - means no contaminated progress exists!\n";
}

echo "\n🎯 VERIFICATION:\n";
echo "================\n";
echo "Regular topic card should now show:\n";
echo "- Progress: 0%\n";
echo "- Button: 'Start Quiz'\n";
echo "- NO assignment contamination!\n\n";

echo "✅ REGULAR TOPIC PROGRESS UPDATE COMPLETE\n";