<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Test the processTutorAnswers method
    echo "🔥 TESTING TUTOR MODE DEBUG\n";
    
    // Create test data
    $user = User::first();
    if (!$user) {
        echo "❌ No users found\n";
        exit(1);
    }
    
    $course = Course::first();
    if (!$course) {
        echo "❌ No courses found\n";
        exit(1);
    }
    
    $topic = Topic::first();
    if (!$topic) {
        echo "❌ No topics found\n";
        exit(1);
    }
    
    echo "✅ Found user: {$user->name}\n";
    echo "✅ Found course: {$course->name}\n";
    echo "✅ Found topic: {$topic->name}\n";
    
    // Check if questions exist
    $questions = Question::where('topic_id', $topic->id)->get();
    echo "✅ Found {$questions->count()} questions\n";
    
    if ($questions->count() === 0) {
        echo "❌ No questions found for topic\n";
        exit(1);
    }
    
    // Test the GradingService
    $gradingService = app(\App\Services\GradingService::class);
    echo "✅ GradingService instantiated\n";
    
    // Simulate answers
    $answers = [];
    foreach ($questions->take(2) as $question) {
        $answers[$question->id] = $question->correct_answer;
    }
    
    echo "✅ Created test answers: " . json_encode($answers) . "\n";
    
    // Test grading
    try {
        $result = $gradingService->gradeQuiz($user, $answers, 120, 'quiz', $topic, $course);
        echo "✅ Grading successful\n";
        echo "Score: {$result['score']}/{$result['totalQuestions']}\n";
        echo "Percentage: {$result['percentageGrade']}%\n";
    } catch (Exception $e) {
        echo "❌ Grading failed: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}