<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Notification;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create test user
$testUser = User::where('email', 'debug.notifications@example.com')->first();
if (!$testUser) {
    $testUser = User::create([
        'name' => 'Debug Notifications User',
        'email' => 'debug.notifications@example.com',
        'password' => bcrypt('password'),
        'role' => 'student',
        'study_streak_days' => 6, // Set to 6 so 7 is a milestone
        'correct_answers_count' => 0,
        'total_questions_attempted' => 0,
        'xp' => 0
    ]);
}

// Clear existing data
QuizAttempt::where('user_id', $testUser->id)->delete();
Notification::where('user_id', $testUser->id)->delete();

echo "🔍 DEBUGGING STREAK NOTIFICATIONS\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "Initial user streak: {$testUser->study_streak_days}\n";

// Create 7 consecutive days of quiz attempts
for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->startOfDay()->addHours(rand(9, 18));
    
    $questionId = \App\Models\Question::inRandomOrder()->first()->id ?? 1;
    
    $questionData = [];
    for ($j = 0; $j < 10; $j++) {
        $questionData[] = [
            'question_id' => $questionId,
            'user_answer' => 'Test Answer',
            'is_correct' => $j < 7
        ];
    }

    \Illuminate\Support\Facades\DB::table('quiz_attempts')->insert([
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
    
    echo "Created quiz attempt for: {$date->format('Y-m-d H:i:s')}\n";
}

echo "\nCalling updateStudyStreak...\n";
$newStreak = $testUser->updateStudyStreak();
echo "New streak: $newStreak\n";

$testUser->refresh();
echo "Database streak: {$testUser->study_streak_days}\n";

echo "\nChecking for notifications...\n";
$notifications = Notification::where('user_id', $testUser->id)->get();

echo "Total notifications: " . $notifications->count() . "\n";

foreach ($notifications as $notification) {
    $data = json_decode($notification->data, true);
    echo "- Type: {$notification->type}, Title: {$notification->title}\n";
    if (isset($data['streak_days'])) {
        echo "  Streak days: {$data['streak_days']}\n";
    }
}

// Check specifically for streak notifications
$streakNotifications = Notification::where('user_id', $testUser->id)
    ->where('type', 'study_streak')
    ->get();

echo "\nStreak notifications: " . $streakNotifications->count() . "\n";

foreach ($streakNotifications as $notification) {
    $data = json_decode($notification->data, true);
    echo "- Title: {$notification->title}, Streak: {$data['streak_days']}\n";
}

// Check for week notification specifically
$weekNotification = Notification::where('user_id', $testUser->id)
    ->where('type', 'study_streak')
    ->whereRaw("JSON_EXTRACT(data, '$.streak_days') = ?", [7])
    ->first();

echo "\nWeek notification found: " . ($weekNotification ? "YES" : "NO") . "\n";

// Debug the JSON query
$allStreakNotifications = Notification::where('user_id', $testUser->id)
    ->where('type', 'study_streak')
    ->get();

echo "\nAll streak notifications:\n";
foreach ($allStreakNotifications as $notif) {
    echo "- Data: {$notif->data}\n";
    $data = json_decode($notif->data, true);
    echo "- Parsed streak_days: " . ($data['streak_days'] ?? 'NULL') . "\n";
    echo "- Type check: " . (gettype($data['streak_days'] ?? null)) . "\n";
    echo "- Equals 7: " . (($data['streak_days'] ?? null) == 7 ? 'YES' : 'NO') . "\n";
    echo "- Strict equals 7: " . (($data['streak_days'] ?? null) === 7 ? 'YES' : 'NO') . "\n";
}

if ($weekNotification) {
    echo "Week notification title: {$weekNotification->title}\n";
    echo "Contains 'Week': " . (str_contains($weekNotification->title, 'Week') ? "YES" : "NO") . "\n";
}

// Cleanup
QuizAttempt::where('user_id', $testUser->id)->delete();
Notification::where('user_id', $testUser->id)->delete();
$testUser->delete();

echo "\n✅ Debug completed!\n";