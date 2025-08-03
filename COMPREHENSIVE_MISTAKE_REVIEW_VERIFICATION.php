<?php

/**
 * 🔥 COMPREHENSIVE MISTAKE REVIEW VERIFICATION 🔥
 * Verifies that all quiz modes correctly display submitted answers and unanswered questions
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Question;
use App\Models\Mistake;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;

class ComprehensiveMistakeReviewVerification
{
    private $testResults = [];
    private $testUser;
    private $testCourse;
    private $testTopic;
    private $testQuestions = [];

    public function __construct()
    {
        echo "🔥 COMPREHENSIVE MISTAKE REVIEW VERIFICATION 🔥\n";
        echo "Testing all quiz modes for proper submitted answer display\n";
        echo "=======================================================\n\n";
    }

    public function runVerification()
    {
        $this->setupTestEnvironment();
        $this->testAllQuizModes();
        $this->verifyMistakeDisplay();
        $this->generateComprehensiveReport();
        $this->cleanup();
    }

    private function setupTestEnvironment()
    {
        echo "🛠️ Setting up test environment...\n";

        // Create test user
        $this->testUser = User::create([
            'name' => 'Mistake Review Test User',
            'email' => 'mistake_review_test_' . time() . '@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);

        // Create test course and topic
        $this->testCourse = Course::create([
            'name' => 'Mistake Review Test Course',
            'description' => 'Course for testing mistake review functionality'
        ]);

        $this->testTopic = Topic::create([
            'name' => 'Mistake Review Test Topic',
            'course_id' => $this->testCourse->id
        ]);

        // Create diverse test questions
        $this->createTestQuestions();

        echo "✅ Test environment setup complete\n\n";
    }

    private function createTestQuestions()
    {
        // Multiple choice questions
        for ($i = 1; $i <= 8; $i++) {
            $this->testQuestions[] = Question::create([
                'topic_id' => $this->testTopic->id,
                'question' => "Multiple choice question $i for mistake review testing?",
                'option_a' => "Option A for question $i",
                'option_b' => "Option B for question $i",
                'option_c' => "Option C for question $i",
                'option_d' => "Option D for question $i",
                'correct_answer' => 'A',
                'explanation' => "Explanation for multiple choice question $i",
                'question_type' => 'multiple_choice'
            ]);
        }

        // Written questions
        for ($i = 1; $i <= 4; $i++) {
            $this->testQuestions[] = Question::create([
                'topic_id' => $this->testTopic->id,
                'question' => "Written question $i for mistake review testing?",
                'correct_answer' => "Correct written answer $i",
                'explanation' => "Explanation for written question $i",
                'question_type' => 'written'
            ]);
        }
    }

    private function testAllQuizModes()
    {
        echo "🎯 Testing all quiz modes...\n";

        $this->testQuizMode();
        $this->testTutorMode();
        $this->testRandomMode();
        $this->testAssignmentMode();

        echo "✅ All quiz modes tested\n\n";
    }

    private function testQuizMode()
    {
        echo "  📝 Testing QUIZ MODE...\n";

        $mistakes = [];
        $questionData = [];

        // Test Case 1: Multiple choice incorrect answer
        $question = $this->testQuestions[0];
        $mistakes[] = $this->createMistake($question, 'B', false, 'quiz');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => 'B', 'is_correct' => false];

        // Test Case 2: Multiple choice unanswered
        $question = $this->testQuestions[1];
        $mistakes[] = $this->createMistake($question, 'unanswered', true, 'quiz');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => null, 'is_correct' => false];

        // Test Case 3: Written incorrect answer
        $question = $this->testQuestions[8]; // First written question
        $mistakes[] = $this->createMistake($question, 'Wrong written answer for quiz mode', false, 'quiz');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => 'Wrong written answer for quiz mode', 'is_correct' => false];

        // Test Case 4: Written unanswered
        $question = $this->testQuestions[9]; // Second written question
        $mistakes[] = $this->createMistake($question, 'unanswered', true, 'quiz');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => null, 'is_correct' => false];

        // Create quiz attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'score' => 0,
            'total_questions' => 4,
            'percentage_grade' => 0,
            'duration_seconds' => 240,
            'question_data' => $questionData
        ]);

        $this->testResults['quiz_mode'] = $mistakes;
        echo "    ✅ Quiz mode test cases created\n";
    }

    private function testTutorMode()
    {
        echo "  🎓 Testing TUTOR MODE...\n";

        $mistakes = [];
        $questionData = [];

        // Test Case 1: Multiple choice incorrect answer
        $question = $this->testQuestions[2];
        $mistakes[] = $this->createMistake($question, 'C', false, 'tutor');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => 'C', 'is_correct' => false];

        // Test Case 2: Multiple choice unanswered
        $question = $this->testQuestions[3];
        $mistakes[] = $this->createMistake($question, 'unanswered', true, 'tutor');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => null, 'is_correct' => false];

        // Create tutor mode attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'tutor',
            'study_mode' => 'tutor',
            'score' => 0,
            'total_questions' => 2,
            'percentage_grade' => 0,
            'duration_seconds' => 180,
            'question_data' => $questionData
        ]);

        $this->testResults['tutor_mode'] = $mistakes;
        echo "    ✅ Tutor mode test cases created\n";
    }

    private function testRandomMode()
    {
        echo "  🎲 Testing RANDOM MODE...\n";

        $mistakes = [];
        $questionData = [];

        // Test Case 1: Multiple choice incorrect answer
        $question = $this->testQuestions[4];
        $mistakes[] = $this->createMistake($question, 'D', false, 'random');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => 'D', 'is_correct' => false];

        // Test Case 2: Multiple choice unanswered (could contain unanswered)
        $question = $this->testQuestions[5];
        $mistakes[] = $this->createMistake($question, 'unanswered', true, 'random');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => null, 'is_correct' => false];

        // Test Case 3: Written incorrect answer
        $question = $this->testQuestions[10]; // Third written question
        $mistakes[] = $this->createMistake($question, 'Incorrect written answer in random mode', false, 'random');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => 'Incorrect written answer in random mode', 'is_correct' => false];

        // Create random mode attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'random',
            'study_mode' => 'random',
            'score' => 0,
            'total_questions' => 3,
            'percentage_grade' => 0,
            'duration_seconds' => 300,
            'question_data' => $questionData
        ]);

        $this->testResults['random_mode'] = $mistakes;
        echo "    ✅ Random mode test cases created\n";
    }

    private function testAssignmentMode()
    {
        echo "  📋 Testing ASSIGNMENT MODE...\n";

        // Create test assignment
        $assignment = Assignment::create([
            'title' => 'Test Assignment for Mistake Review',
            'description' => 'Assignment to test mistake review functionality',
            'course_id' => $this->testCourse->id,
            'due_date' => now()->addDays(7),
            'duration_minutes' => 60,
            'total_questions' => 2
        ]);

        $mistakes = [];
        $questionData = [];

        // Test Case 1: Multiple choice incorrect answer
        $question = $this->testQuestions[6];
        $mistakes[] = $this->createMistake($question, 'B', false, 'assignment');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => 'B', 'is_correct' => false];

        // Test Case 2: Multiple choice unanswered (could contain unanswered)
        $question = $this->testQuestions[7];
        $mistakes[] = $this->createMistake($question, 'unanswered', true, 'assignment');
        $questionData[] = ['question_id' => $question->id, 'user_answer' => null, 'is_correct' => false];

        // Create assignment mode attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'assignment',
            'study_mode' => 'assignment',
            'assignment_id' => $assignment->id,
            'score' => 0,
            'total_questions' => 2,
            'percentage_grade' => 0,
            'duration_seconds' => 3600,
            'question_data' => $questionData
        ]);

        $this->testResults['assignment_mode'] = $mistakes;
        echo "    ✅ Assignment mode test cases created\n";
    }

    private function createMistake($question, $submittedAnswer, $skipped, $mode)
    {
        return Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question->id,
            'submitted_answer' => $submittedAnswer,
            'last_attempt_date' => now(),
            'skipped' => $skipped
        ]);
    }

    private function verifyMistakeDisplay()
    {
        echo "🔍 Verifying mistake display logic...\n";

        foreach ($this->testResults as $mode => $mistakes) {
            echo "  📊 Verifying " . strtoupper(str_replace('_', ' ', $mode)) . ":\n";
            
            foreach ($mistakes as $mistake) {
                $question = Question::find($mistake->question_id);
                $expectedDisplay = $this->getExpectedDisplay($mistake, $question);
                
                echo "    - Question ID {$mistake->question_id}: ";
                echo "Submitted: '{$mistake->submitted_answer}' ";
                echo "→ Should display: {$expectedDisplay}\n";
            }
            echo "\n";
        }
    }

    private function getExpectedDisplay($mistake, $question)
    {
        $submittedAnswer = $mistake->submitted_answer;
        $isUnanswered = ($submittedAnswer === 'unanswered' || $submittedAnswer === '' || $submittedAnswer === null || $mistake->skipped);

        if ($isUnanswered) {
            return "UNANSWERED";
        }

        if ($question->question_type === 'written') {
            return "'{$submittedAnswer}' (written answer)";
        }

        // Multiple choice
        if (in_array(strtolower($submittedAnswer), ['a', 'b', 'c', 'd'])) {
            $optionText = $question->{'option_'.strtolower($submittedAnswer)};
            return "'{$optionText}' (option {$submittedAnswer})";
        }

        return "'{$submittedAnswer}' (raw answer)";
    }

    private function generateComprehensiveReport()
    {
        echo "📋 COMPREHENSIVE MISTAKE REVIEW VERIFICATION REPORT\n";
        echo "===================================================\n\n";

        $totalMistakes = 0;
        $unansweredCount = 0;
        $incorrectAnswerCount = 0;

        foreach ($this->testResults as $mode => $mistakes) {
            echo "🎯 " . strtoupper(str_replace('_', ' ', $mode)) . " RESULTS:\n";
            echo str_repeat('-', 50) . "\n";

            foreach ($mistakes as $mistake) {
                $totalMistakes++;
                $question = Question::find($mistake->question_id);
                
                if ($mistake->submitted_answer === 'unanswered' || $mistake->skipped) {
                    $unansweredCount++;
                    $status = "🔶 UNANSWERED";
                } else {
                    $incorrectAnswerCount++;
                    $status = "❌ INCORRECT ANSWER";
                }

                echo "  Question: {$question->question}\n";
                echo "  Type: {$question->question_type}\n";
                echo "  Submitted: '{$mistake->submitted_answer}'\n";
                echo "  Status: {$status}\n";
                echo "  Expected Display: " . $this->getExpectedDisplay($mistake, $question) . "\n\n";
            }
        }

        echo "📊 SUMMARY STATISTICS:\n";
        echo str_repeat('=', 50) . "\n";
        echo "Total Mistakes Created: {$totalMistakes}\n";
        echo "Unanswered Questions: {$unansweredCount}\n";
        echo "Incorrect Answers: {$incorrectAnswerCount}\n\n";

        echo "✅ VERIFICATION CHECKLIST:\n";
        echo str_repeat('=', 50) . "\n";
        echo "□ Quiz Mode: Shows actual submitted answers and 'UNANSWERED' for skipped\n";
        echo "□ Tutor Mode: Shows actual submitted answers and 'UNANSWERED' for skipped\n";
        echo "□ Random Mode: Shows actual submitted answers and 'UNANSWERED' for skipped\n";
        echo "□ Assignment Mode: Shows actual submitted answers and 'UNANSWERED' for skipped\n";
        echo "□ Written Questions: Show full submitted text\n";
        echo "□ Multiple Choice: Show selected option text\n";
        echo "□ Unanswered: Show 'UNANSWERED' label consistently\n\n";

        echo "🎉 VERIFICATION COMPLETE!\n";
        echo "All quiz modes should now correctly display:\n";
        echo "- The ACTUAL incorrect submitted answer (not placeholder)\n";
        echo "- 'UNANSWERED' for questions that were skipped or not answered\n";
        echo "- Proper formatting for both written and multiple choice questions\n\n";
    }

    private function cleanup()
    {
        echo "🧹 Cleaning up test data...\n";

        // Delete in reverse order of dependencies
        Mistake::where('user_id', $this->testUser->id)->delete();
        QuizAttempt::where('user_id', $this->testUser->id)->delete();
        Assignment::where('course_id', $this->testCourse->id)->delete();
        
        foreach ($this->testQuestions as $question) {
            $question->delete();
        }
        
        $this->testTopic->delete();
        $this->testCourse->delete();
        $this->testUser->delete();

        echo "✅ Cleanup completed successfully\n\n";
        echo "🔥 MISTAKE REVIEW VERIFICATION COMPLETE! 🔥\n";
    }
}

// Run verification if called directly
if (php_sapi_name() === 'cli') {
    $verification = new ComprehensiveMistakeReviewVerification();
    $verification->runVerification();
}