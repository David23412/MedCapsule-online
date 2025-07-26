<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Mistake;
use App\Services\GradingService;
use Illuminate\Support\Facades\DB;

class TestMistakeRecordingFix extends Command
{
    protected $signature = 'mistakes:test-fix';
    protected $description = 'Test the mistake recording fix with various scenarios';

    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        parent::__construct();
        $this->gradingService = $gradingService;
    }

    public function handle()
    {
        $this->info('💀 TESTING MISTAKE RECORDING FIX 💀');
        $this->info('==================================');
        $this->newLine();

        // Get test data
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(3)->get();

        if (!$user || !$topic || $questions->count() < 3) {
            $this->error('❌ Insufficient test data. Need at least 1 user, 1 topic, and 3 questions.');
            return 1;
        }

        $this->info("🧪 Test Setup:");
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info("Topic: {$topic->name} (ID: {$topic->id})");
        $this->info("Course: {$topic->course->name} (ID: {$topic->course->id})");
        $this->info("Questions: {$questions->count()}");
        $this->newLine();

        // Clear existing mistakes for clean test
        Mistake::where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->delete();

        $this->info('🧹 Cleared existing test mistakes');
        $this->newLine();

        // Test Scenario 1: User gives wrong answers
        $this->info('📝 TEST SCENARIO 1: User gives wrong answers');
        $this->info('================================================');
        
        $wrongAnswers = [];
        foreach ($questions as $question) {
            // Give a wrong answer (not the correct one)
            $correctAnswer = $question->correct_answer;
            $options = ['A', 'B', 'C', 'D'];
            $wrongOptions = array_filter($options, fn($opt) => $opt !== $correctAnswer);
            $wrongAnswer = $wrongOptions[array_rand($wrongOptions)];
            $wrongAnswers[$question->id] = $wrongAnswer;
            
            $this->line("Question {$question->id}: Correct={$correctAnswer}, Submitted={$wrongAnswer}");
        }

        try {
            $result1 = $this->gradingService->gradeQuiz(
                $user,
                $wrongAnswers,
                300, // 5 minutes
                'quiz',
                $topic,
                $topic->course
            );

            $this->info("✅ Grading completed: {$result1['score']}/{$result1['totalQuestions']} correct");
            
            // Check recorded mistakes
            $recordedMistakes1 = Mistake::where('user_id', $user->id)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();

            $this->info("📊 Recorded mistakes: {$recordedMistakes1->count()}");
            foreach ($recordedMistakes1 as $mistake) {
                $this->line("  - Question {$mistake->question_id}: '{$mistake->submitted_answer}' (Expected: wrong answer, not 'unanswered')");
                
                if ($mistake->submitted_answer === 'unanswered') {
                    $this->error("    ❌ BUG: Should be actual wrong answer, not 'unanswered'");
                } else {
                    $this->info("    ✅ CORRECT: Actual wrong answer recorded");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Test 1 failed: {$e->getMessage()}");
        }

        $this->newLine();

        // Test Scenario 2: User leaves questions unanswered (empty strings)
        $this->info('📝 TEST SCENARIO 2: User leaves questions unanswered');
        $this->info('==================================================');

        // Clear previous mistakes
        Mistake::where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->delete();

        $unansweredAnswers = [];
        foreach ($questions as $question) {
            $unansweredAnswers[$question->id] = ''; // Empty string (unanswered)
            $this->line("Question {$question->id}: Submitted='' (empty string)");
        }

        try {
            $result2 = $this->gradingService->gradeQuiz(
                $user,
                $unansweredAnswers,
                300,
                'quiz',
                $topic,
                $topic->course
            );

            $this->info("✅ Grading completed: {$result2['score']}/{$result2['totalQuestions']} correct");
            
            // Check recorded mistakes
            $recordedMistakes2 = Mistake::where('user_id', $user->id)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();

            $this->info("📊 Recorded mistakes: {$recordedMistakes2->count()}");
            foreach ($recordedMistakes2 as $mistake) {
                $this->line("  - Question {$mistake->question_id}: '{$mistake->submitted_answer}' (Expected: 'unanswered')");
                
                if ($mistake->submitted_answer === 'unanswered') {
                    $this->info("    ✅ CORRECT: Properly marked as 'unanswered'");
                } else {
                    $this->error("    ❌ BUG: Should be 'unanswered', not '{$mistake->submitted_answer}'");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Test 2 failed: {$e->getMessage()}");
        }

        $this->newLine();

        // Test Scenario 3: Mixed answers (some correct, some wrong, some unanswered)
        $this->info('📝 TEST SCENARIO 3: Mixed answers');
        $this->info('=================================');

        // Clear previous mistakes
        Mistake::where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->delete();

        $mixedAnswers = [];
        $expectedMistakes = [];
        
        foreach ($questions as $index => $question) {
            if ($index === 0) {
                // First question: correct answer
                $mixedAnswers[$question->id] = $question->correct_answer;
                $this->line("Question {$question->id}: Correct answer '{$question->correct_answer}'");
            } elseif ($index === 1) {
                // Second question: wrong answer
                $correctAnswer = $question->correct_answer;
                $options = ['A', 'B', 'C', 'D'];
                $wrongOptions = array_filter($options, fn($opt) => $opt !== $correctAnswer);
                $wrongAnswer = $wrongOptions[array_rand($wrongOptions)];
                $mixedAnswers[$question->id] = $wrongAnswer;
                $expectedMistakes[$question->id] = $wrongAnswer;
                $this->line("Question {$question->id}: Wrong answer '{$wrongAnswer}' (correct: '{$correctAnswer}')");
            } else {
                // Third question: unanswered
                $mixedAnswers[$question->id] = '';
                $expectedMistakes[$question->id] = 'unanswered';
                $this->line("Question {$question->id}: Unanswered (empty string)");
            }
        }

        try {
            $result3 = $this->gradingService->gradeQuiz(
                $user,
                $mixedAnswers,
                300,
                'quiz',
                $topic,
                $topic->course
            );

            $this->info("✅ Grading completed: {$result3['score']}/{$result3['totalQuestions']} correct");
            
            // Check recorded mistakes
            $recordedMistakes3 = Mistake::where('user_id', $user->id)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();

            $this->info("📊 Recorded mistakes: {$recordedMistakes3->count()} (Expected: " . count($expectedMistakes) . ")");
            
            foreach ($recordedMistakes3 as $mistake) {
                $expected = $expectedMistakes[$mistake->question_id] ?? 'NONE';
                $this->line("  - Question {$mistake->question_id}: '{$mistake->submitted_answer}' (Expected: '{$expected}')");
                
                if ($mistake->submitted_answer === $expected) {
                    $this->info("    ✅ CORRECT: Matches expected value");
                } else {
                    $this->error("    ❌ BUG: Expected '{$expected}', got '{$mistake->submitted_answer}'");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Test 3 failed: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('💀 TESTING COMPLETE 💀');
        $this->info('======================');
        $this->info('Review the results above to verify the fix is working correctly.');
        $this->info('✅ Correct behavior: Wrong answers show actual answer, unanswered shows "unanswered"');
        $this->info('❌ Bug behavior: Wrong answers show "unanswered" instead of actual answer');

        return 0;
    }
}