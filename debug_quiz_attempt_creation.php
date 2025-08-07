<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 DEBUGGING QUIZ ATTEMPT CREATION\n";
echo "=================================\n\n";

try {
    // Clean up
    DB::table('quiz_attempts')->where('user_id', 1)->where('study_mode', 'tutor')->delete();
    
    $user = \App\Models\User::find(1);
    $topic = \App\Models\Topic::first();
    $questions = \App\Models\Question::where('topic_id', $topic->id)->take(2)->get();
    
    auth()->login($user);
    
    echo "✅ Setup complete\n";
    echo "   User: {$user->id}\n";
    echo "   Topic: {$topic->id}\n";
    echo "   Questions: {$questions->count()}\n";
    
    // Test direct QuizAttempt creation
    echo "\n🧪 Testing direct QuizAttempt creation...\n";
    
    $directAttempt = \App\Models\QuizAttempt::create([
        'user_id' => $user->id,
        'topic_id' => $topic->id,
        'quiz_type' => 'normal',
        'study_mode' => 'tutor',
        'source' => 'Test',
        'score' => 1,
        'total_questions' => 2,
        'percentage_grade' => 50.0,
        'duration_seconds' => 30,
        'question_data' => [
            ['question_id' => $questions[0]->id, 'user_answer' => 'A', 'is_correct' => true],
            ['question_id' => $questions[1]->id, 'user_answer' => 'B', 'is_correct' => false]
        ],
        'correct_answers' => 1
    ]);
    
    echo "✅ Direct creation successful: ID {$directAttempt->id}\n";
    echo "   Quiz Type: {$directAttempt->quiz_type}\n";
    echo "   Study Mode: {$directAttempt->study_mode}\n";
    
    // Test GradingService
    echo "\n🧪 Testing GradingService...\n";
    
    $answers = [
        $questions[0]->id => $questions[0]->correct_answer,
        $questions[1]->id => 'B' // Wrong answer
    ];
    
    $gradingService = app(\App\Services\GradingService::class);
    
    // Enable query logging
    DB::enableQueryLog();
    
    $result = $gradingService->gradeQuiz(
        $user,
        $answers,
        45,
        'tutor',
        $topic,
        $topic->course,
        null
    );
    
    $queries = DB::getQueryLog();
    echo "✅ GradingService completed\n";
    echo "   Queries executed: " . count($queries) . "\n";
    echo "   Result score: {$result['score']}/{$result['totalQuestions']}\n";
    
    // Check database
    $attempts = \App\Models\QuizAttempt::where('user_id', $user->id)
        ->where('topic_id', $topic->id)
        ->where('study_mode', 'tutor')
        ->get();
    
    echo "   Attempts in DB: {$attempts->count()}\n";
    
    foreach ($attempts as $attempt) {
        echo "   - ID: {$attempt->id}, Type: {$attempt->quiz_type}, Mode: {$attempt->study_mode}, Source: {$attempt->source}\n";
    }
    
    // Show recent queries
    echo "\n📊 Recent queries:\n";
    foreach (array_slice($queries, -5) as $query) {
        echo "   " . $query['query'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Debug completed\n";