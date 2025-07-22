<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\QuizAttempt;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create test user
$testUser = User::where('email', 'debug.streak@example.com')->first();
if (!$testUser) {
    $testUser = User::create([
        'name' => 'Debug Streak User',
        'email' => 'debug.streak@example.com',
        'password' => bcrypt('password'),
        'role' => 'student',
        'study_streak_days' => 0,
        'correct_answers_count' => 0,
        'total_questions_attempted' => 0,
        'xp' => 0
    ]);
}

// Clear existing quiz attempts
QuizAttempt::where('user_id', $testUser->id)->delete();

echo "🔍 DEBUGGING STREAK CALCULATION\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Create quiz attempts for 5 consecutive days
$dates = [
    now()->subDays(4)->startOfDay()->addHours(10), // 5 days ago
    now()->subDays(3)->startOfDay()->addHours(14), // 4 days ago
    now()->subDays(2)->startOfDay()->addHours(9),  // 3 days ago
    now()->subDays(1)->startOfDay()->addHours(16), // Yesterday
    now()->startOfDay()->addHours(11)              // Today
];

echo "Creating quiz attempts for consecutive days:\n";
foreach ($dates as $i => $date) {
    $questionId = \App\Models\Question::inRandomOrder()->first()->id ?? 1;
    
    $questionData = [];
    for ($j = 0; $j < 10; $j++) {
        $questionData[] = [
            'question_id' => $questionId,
            'user_answer' => 'Test Answer',
            'is_correct' => $j < 7
        ];
    }

    // Use DB::table to bypass model events and set exact timestamps
    $attemptId = \Illuminate\Support\Facades\DB::table('quiz_attempts')->insertGetId([
        'user_id' => $testUser->id,
        'topic_id' => 1,
        'quiz_type' => 'normal',
        'study_mode' => 'quiz',
        'source' => 'test',
        'score' => 7,
        'total_questions' => 10,
        'percentage_grade' => 70,
        'duration_seconds' => 600,
        'question_data' => json_encode($questionData),
        'created_at' => $date,
        'updated_at' => $date
    ]);
    
    $attempt = QuizAttempt::find($attemptId);
    
    echo "  Day " . ($i + 1) . ": {$date->format('Y-m-d H:i:s')} (ID: {$attempt->id})\n";
}

echo "\nNow testing streak calculation...\n";

// Test the calculateConsecutiveDays method manually
echo "\nManual consecutive days calculation:\n";
$streak = 0;
$currentDate = now()->startOfDay();

for ($i = 0; $i < 10; $i++) {
    $hadActivity = QuizAttempt::where('user_id', $testUser->id)
        ->whereDate('created_at', $currentDate)
        ->exists();
        
    echo "  Day {$currentDate->format('Y-m-d')}: " . ($hadActivity ? "✅ Active" : "❌ No activity") . "\n";
    
    if (!$hadActivity) {
        break;
    }
    
    $streak++;
    $currentDate->subDay();
}

echo "\nCalculated streak: $streak\n";

// Now test the actual method
echo "\nTesting updateStudyStreak method:\n";
$actualStreak = $testUser->updateStudyStreak();
echo "Method returned: $actualStreak\n";

$testUser->refresh();
echo "Database value: {$testUser->study_streak_days}\n";

// Check what quiz attempts exist
echo "\nQuiz attempts in database:\n";
$attempts = QuizAttempt::where('user_id', $testUser->id)
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($attempts as $attempt) {
    echo "  {$attempt->created_at->format('Y-m-d H:i:s')} (Date: {$attempt->created_at->startOfDay()->format('Y-m-d')})\n";
}

// Cleanup
QuizAttempt::where('user_id', $testUser->id)->delete();
$testUser->delete();

echo "\n✅ Debug completed!\n";