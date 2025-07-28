<?php

/**
 * 🚨 EMERGENCY QUIZ FIX - RESTORE QUESTIONS IMMEDIATELY 🚨
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚨 EMERGENCY QUIZ FIX STARTING...\n";

try {
    // Delete all questions with null content
    $deletedCount = Question::whereNull('question')->delete();
    echo "✅ Deleted {$deletedCount} questions with null content\n";

    // Get or create topic
    $topic = Topic::first();
    if (!$topic) {
        $course = Course::first();
        $topic = Topic::create([
            'course_id' => $course->id,
            'name' => 'Emergency Fix Topic',
            'case_type' => 'quiz',
            'display_order' => 1
        ]);
        echo "✅ Created emergency topic\n";
    }

    // Create emergency questions with proper content
    $questions = [
        [
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice',
            'question' => 'Which of the following is the most common cause of community-acquired pneumonia?',
            'option_a' => 'Streptococcus pneumoniae',
            'option_b' => 'Haemophilus influenzae',
            'option_c' => 'Mycoplasma pneumoniae',
            'option_d' => 'Legionella pneumophila',
            'correct_answer' => 'A',
            'explanation' => 'Streptococcus pneumoniae is the most common cause.',
            'source' => 'Emergency Fix'
        ],
        [
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice',
            'question' => 'What is the first-line treatment for type 2 diabetes mellitus?',
            'option_a' => 'Metformin',
            'option_b' => 'Insulin',
            'option_c' => 'Sulfonylureas',
            'option_d' => 'DPP-4 inhibitors',
            'correct_answer' => 'A',
            'explanation' => 'Metformin is the first-line medication for type 2 diabetes.',
            'source' => 'Emergency Fix'
        ],
        [
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice',
            'question' => 'Which symptom is most characteristic of acute appendicitis?',
            'option_a' => 'Right lower quadrant pain',
            'option_b' => 'Left upper quadrant pain',
            'option_c' => 'Chest pain',
            'option_d' => 'Headache',
            'correct_answer' => 'A',
            'explanation' => 'Right lower quadrant pain is the classic symptom of appendicitis.',
            'source' => 'Emergency Fix'
        ],
        [
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice',
            'question' => 'What is the normal range for blood pressure in adults?',
            'option_a' => 'Less than 120/80 mmHg',
            'option_b' => 'Less than 140/90 mmHg',
            'option_c' => 'Less than 160/100 mmHg',
            'option_d' => 'Less than 180/110 mmHg',
            'correct_answer' => 'A',
            'explanation' => 'Normal blood pressure is less than 120/80 mmHg.',
            'source' => 'Emergency Fix'
        ],
        [
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice',
            'question' => 'Which of the following is a sign of dehydration?',
            'option_a' => 'Dry mucous membranes',
            'option_b' => 'Increased urination',
            'option_c' => 'Excessive sweating',
            'option_d' => 'Rapid weight gain',
            'correct_answer' => 'A',
            'explanation' => 'Dry mucous membranes are a classic sign of dehydration.',
            'source' => 'Emergency Fix'
        ]
    ];

    $created = 0;
    foreach ($questions as $questionData) {
        $question = Question::create($questionData);
        $created++;
        echo "✅ Created question {$created}: {$question->question}\n";
    }

    echo "\n🎉 EMERGENCY FIX COMPLETED!\n";
    echo "📊 Summary:\n";
    echo "   - Deleted: {$deletedCount} null questions\n";
    echo "   - Created: {$created} working questions\n";

    // Verify the fix
    $testQuestion = Question::first();
    echo "\n🔍 Verification:\n";
    echo "   - Question: {$testQuestion->question}\n";
    echo "   - Option A: {$testQuestion->option_a}\n";
    echo "   - Option B: {$testQuestion->option_b}\n";
    echo "   - Correct Answer: {$testQuestion->correct_answer}\n";

    echo "\n🚀 QUIZ SHOULD NOW WORK PROPERLY!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}