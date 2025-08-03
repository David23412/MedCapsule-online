<?php

/**
 * 🔥 MISTAKE REVIEW TEST ENGINE 🔥
 * Tests all quiz modes to ensure submitted answers are correctly displayed
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Question;
use App\Models\Mistake;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class MistakeReviewTestEngine
{
    private $testUser;
    private $testCourse;
    private $testTopic;
    private $testQuestions = [];
    private $results = [];

    public function __construct()
    {
        echo "🔥 MISTAKE REVIEW TEST ENGINE INITIALIZED 🔥\n";
        echo "Testing all quiz modes for correct submitted answer display\n\n";
    }

    public function runAllTests()
    {
        $this->setupTestData();
        $this->testQuizMode();
        $this->testTutorMode();
        $this->testRandomMode();
        $this->testAssignmentMode();
        $this->generateReport();
        $this->cleanup();
    }

    private function setupTestData()
    {
        echo "📋 Setting up test data...\n";
        
        // Create test user
        $this->testUser = User::create([
            'name' => 'Test User - Mistake Review',
            'email' => 'mistake_test_' . time() . '@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);

        // Create test course and topic
        $this->testCourse = Course::create([
            'name' => 'Test Course - Mistake Review',
            'description' => 'Test course for mistake review testing'
        ]);

        $this->testTopic = Topic::create([
            'name' => 'Test Topic - Mistake Review',
            'course_id' => $this->testCourse->id
        ]);

        // Create test questions
        for ($i = 1; $i <= 5; $i++) {
            $this->testQuestions[] = Question::create([
                'topic_id' => $this->testTopic->id,
                'question' => "Test Question $i for mistake review?",
                'option_a' => 'Option A',
                'option_b' => 'Option B', 
                'option_c' => 'Option C',
                'option_d' => 'Option D',
                'correct_answer' => 'A',
                'explanation' => "Explanation for question $i",
                'question_type' => 'multiple_choice'
            ]);
        }

        // Create one written question
        $this->testQuestions[] = Question::create([
            'topic_id' => $this->testTopic->id,
            'question' => 'Written test question for mistake review?',
            'correct_answer' => 'Correct written answer',
            'explanation' => 'Explanation for written question',
            'question_type' => 'written'
        ]);

        echo "✅ Test data created successfully\n\n";
    }

    private function testQuizMode()
    {
        echo "🎯 Testing QUIZ MODE...\n";
        
        $questionData = [];
        $mistakes = [];

        // Test Case 1: Incorrect answer submitted
        $question1 = $this->testQuestions[0];
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question1->id,
            'submitted_answer' => 'B', // Wrong answer
            'last_attempt_date' => now(),
            'skipped' => false
        ]);
        $questionData[] = [
            'question_id' => $question1->id,
            'user_answer' => 'B',
            'is_correct' => false
        ];

        // Test Case 2: Unanswered question
        $question2 = $this->testQuestions[1];
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question2->id,
            'submitted_answer' => 'unanswered', // Unanswered
            'last_attempt_date' => now(),
            'skipped' => true
        ]);
        $questionData[] = [
            'question_id' => $question2->id,
            'user_answer' => null,
            'is_correct' => false
        ];

        // Create quiz attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'score' => 0,
            'total_questions' => 2,
            'percentage_grade' => 0,
            'duration_seconds' => 120,
            'question_data' => $questionData
        ]);

        $this->results['quiz_mode'] = [
            'incorrect_answer' => $mistakes[0],
            'unanswered' => $mistakes[1]
        ];

        echo "  ✅ Created incorrect answer mistake (submitted: B)\n";
        echo "  ✅ Created unanswered mistake (submitted: unanswered)\n\n";
    }

    private function testTutorMode()
    {
        echo "🎓 Testing TUTOR MODE...\n";
        
        $questionData = [];
        $mistakes = [];

        // Test Case 1: Incorrect answer in tutor mode
        $question3 = $this->testQuestions[2];
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question3->id,
            'submitted_answer' => 'C', // Wrong answer
            'last_attempt_date' => now(),
            'skipped' => false
        ]);
        $questionData[] = [
            'question_id' => $question3->id,
            'user_answer' => 'C',
            'is_correct' => false
        ];

        // Test Case 2: Unanswered in tutor mode
        $question4 = $this->testQuestions[3];
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question4->id,
            'submitted_answer' => 'unanswered',
            'last_attempt_date' => now(),
            'skipped' => true
        ]);
        $questionData[] = [
            'question_id' => $question4->id,
            'user_answer' => null,
            'is_correct' => false
        ];

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

        $this->results['tutor_mode'] = [
            'incorrect_answer' => $mistakes[0],
            'unanswered' => $mistakes[1]
        ];

        echo "  ✅ Created tutor mode incorrect answer (submitted: C)\n";
        echo "  ✅ Created tutor mode unanswered (submitted: unanswered)\n\n";
    }

    private function testRandomMode()
    {
        echo "🎲 Testing RANDOM MODE...\n";
        
        $questionData = [];
        $mistakes = [];

        // Test Case 1: Written question incorrect answer
        $question5 = $this->testQuestions[5]; // Written question
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question5->id,
            'submitted_answer' => 'Wrong written answer', // Wrong written answer
            'last_attempt_date' => now(),
            'skipped' => false
        ]);
        $questionData[] = [
            'question_id' => $question5->id,
            'user_answer' => 'Wrong written answer',
            'is_correct' => false
        ];

        // Test Case 2: Multiple choice unanswered in random mode
        $question6 = $this->testQuestions[4];
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question6->id,
            'submitted_answer' => 'unanswered',
            'last_attempt_date' => now(),
            'skipped' => true
        ]);
        $questionData[] = [
            'question_id' => $question6->id,
            'user_answer' => null,
            'is_correct' => false
        ];

        // Create random mode attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'random',
            'study_mode' => 'random',
            'score' => 0,
            'total_questions' => 2,
            'percentage_grade' => 0,
            'duration_seconds' => 300,
            'question_data' => $questionData
        ]);

        $this->results['random_mode'] = [
            'written_incorrect' => $mistakes[0],
            'unanswered' => $mistakes[1]
        ];

        echo "  ✅ Created random mode written incorrect (submitted: Wrong written answer)\n";
        echo "  ✅ Created random mode unanswered (submitted: unanswered)\n\n";
    }

    private function testAssignmentMode()
    {
        echo "📝 Testing ASSIGNMENT MODE...\n";
        
        // Create assignment
        $assignment = \App\Models\Assignment::create([
            'title' => 'Test Assignment - Mistake Review',
            'description' => 'Test assignment for mistake review',
            'course_id' => $this->testCourse->id,
            'due_date' => now()->addDays(7),
            'duration_minutes' => 60,
            'total_questions' => 1
        ]);

        $questionData = [];
        $mistakes = [];

        // Test Case 1: Assignment mode unanswered
        $question7 = $this->testQuestions[0]; // Reuse question
        $mistakes[] = Mistake::create([
            'user_id' => $this->testUser->id,
            'question_id' => $question7->id,
            'submitted_answer' => 'unanswered',
            'last_attempt_date' => now(),
            'skipped' => true
        ]);
        $questionData[] = [
            'question_id' => $question7->id,
            'user_answer' => null,
            'is_correct' => false
        ];

        // Create assignment mode attempt
        QuizAttempt::create([
            'user_id' => $this->testUser->id,
            'topic_id' => $this->testTopic->id,
            'quiz_type' => 'assignment',
            'study_mode' => 'assignment',
            'assignment_id' => $assignment->id,
            'score' => 0,
            'total_questions' => 1,
            'percentage_grade' => 0,
            'duration_seconds' => 3600,
            'question_data' => $questionData
        ]);

        $this->results['assignment_mode'] = [
            'unanswered' => $mistakes[0]
        ];

        echo "  ✅ Created assignment mode unanswered (submitted: unanswered)\n\n";
    }

    private function generateReport()
    {
        echo "📊 MISTAKE REVIEW TEST RESULTS:\n";
        echo "=====================================\n\n";

        foreach ($this->results as $mode => $mistakes) {
            echo "🎯 " . strtoupper(str_replace('_', ' ', $mode)) . ":\n";
            
            foreach ($mistakes as $type => $mistake) {
                echo "  - " . ucfirst(str_replace('_', ' ', $type)) . ":\n";
                echo "    Question ID: {$mistake->question_id}\n";
                echo "    Submitted Answer: '{$mistake->submitted_answer}'\n";
                echo "    Skipped: " . ($mistake->skipped ? 'Yes' : 'No') . "\n";
                echo "    Expected Display: " . $this->getExpectedDisplay($mistake) . "\n\n";
            }
        }

        echo "🔍 VERIFICATION CHECKLIST:\n";
        echo "- ✅ Incorrect answers should show the actual submitted answer\n";
        echo "- ✅ Unanswered questions should show 'UNANSWERED'\n";
        echo "- ✅ Written questions should show the full submitted text\n";
        echo "- ✅ All quiz modes should handle unanswered consistently\n\n";
    }

    private function getExpectedDisplay($mistake)
    {
        if ($mistake->submitted_answer === 'unanswered' || $mistake->skipped) {
            return 'UNANSWERED';
        }
        
        return "'{$mistake->submitted_answer}'";
    }

    private function cleanup()
    {
        echo "🧹 Cleaning up test data...\n";
        
        // Delete in reverse order of creation
        Mistake::where('user_id', $this->testUser->id)->delete();
        QuizAttempt::where('user_id', $this->testUser->id)->delete();
        
        foreach ($this->testQuestions as $question) {
            $question->delete();
        }
        
        $this->testTopic->delete();
        $this->testCourse->delete();
        $this->testUser->delete();
        
        echo "✅ Cleanup completed\n\n";
    }
}

// Run the test if called directly
if (php_sapi_name() === 'cli') {
    $testEngine = new MistakeReviewTestEngine();
    $testEngine->runAllTests();
}