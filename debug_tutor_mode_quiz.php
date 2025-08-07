<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 TUTOR MODE QUIZ ATTEMPT DEBUG SCRIPT\n";
echo "=====================================\n\n";

try {
    // Test user (assuming user ID 1 exists)
    $user = \App\Models\User::find(1);
    if (!$user) {
        echo "❌ User not found. Creating test user...\n";
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);
    }
    
    echo "✅ User found: {$user->name} (ID: {$user->id})\n";
    
    // Get a test topic
    $topic = \App\Models\Topic::first();
    if (!$topic) {
        echo "❌ No topics found in database\n";
        exit(1);
    }
    
    echo "✅ Topic found: {$topic->name} (ID: {$topic->id})\n";
    
    // Get a test question from this topic
    $question = \App\Models\Question::where('topic_id', $topic->id)->first();
    if (!$question) {
        echo "❌ No questions found for topic {$topic->id}\n";
        exit(1);
    }
    
    echo "✅ Question found: {$question->id}\n";
    
    // Simulate tutor mode quiz submission
    $answers = [
        $question->id => 'A' // Assuming this is a multiple choice question
    ];
    
    echo "\n🧪 Testing GradingService::gradeQuiz with tutor mode...\n";
    
    // Create GradingService instance
    $gradingService = app(\App\Services\GradingService::class);
    
    // Test the grading
    $result = $gradingService->gradeQuiz(
        $user,
        $answers,
        30, // 30 seconds duration
        'tutor', // tutor mode
        $topic,
        $topic->course ?? new \App\Models\Course(['id' => 1, 'name' => 'Test Course']),
        null // no assignment
    );
    
    echo "✅ Grading completed successfully!\n";
    echo "Score: {$result['score']}/{$result['totalQuestions']}\n";
    echo "Percentage: {$result['percentageGrade']}%\n";
    
    // Check if QuizAttempt was created
    $latestAttempt = \App\Models\QuizAttempt::where('user_id', $user->id)
        ->where('topic_id', $topic->id)
        ->where('study_mode', 'tutor')
        ->latest()
        ->first();
    
    if ($latestAttempt) {
        echo "✅ QuizAttempt created successfully!\n";
        echo "Attempt ID: {$latestAttempt->id}\n";
        echo "Study Mode: {$latestAttempt->study_mode}\n";
        echo "Quiz Type: {$latestAttempt->quiz_type}\n";
        echo "Source: {$latestAttempt->source}\n";
        echo "Score: {$latestAttempt->score}/{$latestAttempt->total_questions}\n";
    } else {
        echo "❌ No QuizAttempt found for tutor mode!\n";
        
        // Check all recent attempts
        $recentAttempts = \App\Models\QuizAttempt::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->latest()
            ->take(5)
            ->get();
        
        echo "Recent attempts for this user/topic:\n";
        foreach ($recentAttempts as $attempt) {
            echo "- ID: {$attempt->id}, Mode: {$attempt->study_mode}, Type: {$attempt->quiz_type}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🔍 Checking database for quiz_attempts table structure...\n";

try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('quiz_attempts');
    echo "✅ quiz_attempts table columns: " . implode(', ', $columns) . "\n";
    
    // Check if study_mode column exists and allows 'tutor'
    $connection = \Illuminate\Support\Facades\DB::connection();
    $columnInfo = $connection->select("DESCRIBE quiz_attempts study_mode");
    if (!empty($columnInfo)) {
        echo "✅ study_mode column info: " . json_encode($columnInfo[0]) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Database check error: " . $e->getMessage() . "\n";
}

echo "\n✅ Debug script completed.\n";