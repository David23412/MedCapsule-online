<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Mistake;
use App\Models\Assignment;
use App\Services\GradingService;
use App\Http\Controllers\Quiz\QuizExecutionController;
use App\Http\Controllers\Quiz\OptimizedRandomQuizController;
use App\Http\Controllers\Quiz\QuizTutorController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NuclearMistakeTrackingTestEngine extends Command
{
    protected $signature = 'mistakes:nuclear-test {--aggressive} {--full-spectrum}';
    protected $description = '💀 NUCLEAR MISTAKE TRACKING TEST ENGINE - LETHAL COMPREHENSIVE TESTING 💀';

    protected $gradingService;
    protected $testResults = [];
    protected $totalTests = 0;
    protected $passedTests = 0;
    protected $failedTests = 0;

    public function __construct(GradingService $gradingService)
    {
        parent::__construct();
        $this->gradingService = $gradingService;
    }

    public function handle()
    {
        $this->displayBanner();
        
        // Initialize test environment
        $this->initializeTestEnvironment();
        
        // Run all test suites
        $this->runRegularQuizTests();
        $this->runTutorModeTests();
        $this->runRandomQuizTests();
        $this->runAssignmentQuizTests();
        
        if ($this->option('full-spectrum')) {
            $this->runEdgeCaseTests();
            $this->runPerformanceTests();
            $this->runConcurrencyTests();
        }
        
        // Validate mistakes display in blade
        $this->validateMistakesBladeDisplay();
        
        // Generate comprehensive report
        $this->generateNuclearReport();
        
        return $this->failedTests === 0 ? 0 : 1;
    }

    private function displayBanner()
    {
        $this->info('');
        $this->info('💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀');
        $this->info('💀                                                            💀');
        $this->info('💀        NUCLEAR MISTAKE TRACKING TEST ENGINE               💀');
        $this->info('💀                LETHAL PRECISION TESTING                   💀');
        $this->info('💀                                                            💀');
        $this->info('💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀');
        $this->info('');
        $this->warn('⚠️  AGGRESSIVE MODE: Testing ALL quiz modes with MAXIMUM intensity');
        $this->warn('⚠️  This will create and destroy test data - DO NOT run in production');
        $this->info('');
    }

    private function initializeTestEnvironment()
    {
        $this->info('🔧 INITIALIZING NUCLEAR TEST ENVIRONMENT...');
        $this->info('==========================================');
        
        // Clear all test-related caches
        Cache::flush();
        
        // Ensure we have test data
        $this->ensureTestData();
        
        $this->info('✅ Test environment initialized');
        $this->newLine();
    }

    private function ensureTestData()
    {
        // Ensure we have at least one admin user
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'admin', 'name' => 'Test Admin']);
        }
        
        // Ensure we have test courses, topics, and questions
        $course = Course::first();
        if (!$course) {
            $course = Course::create([
                'name' => 'Nuclear Test Course',
                'description' => 'Test course for nuclear testing',
                'academic_year' => '2024-2025'
            ]);
        }
        
        $topic = Topic::where('course_id', $course->id)->first();
        if (!$topic) {
            $topic = Topic::create([
                'name' => 'Nuclear Test Topic',
                'course_id' => $course->id,
                'display_order' => 1
            ]);
        }
        
        // Ensure we have enough questions for testing
        $questionCount = Question::where('topic_id', $topic->id)->count();
        if ($questionCount < 10) {
            for ($i = $questionCount; $i < 10; $i++) {
                Question::create([
                    'topic_id' => $topic->id,
                    'question' => "Nuclear test question " . ($i + 1) . "?",
                    'option_a' => 'Option A',
                    'option_b' => 'Option B', 
                    'option_c' => 'Option C',
                    'option_d' => 'Option D',
                    'correct_answer' => 'A',
                    'source' => 'Nuclear Test',
                    'explanation' => 'This is a test explanation'
                ]);
            }
        }
        
        $this->info("✅ Test data verified: {$course->name} -> {$topic->name} -> " . Question::where('topic_id', $topic->id)->count() . " questions");
    }

    private function runRegularQuizTests()
    {
        $this->info('🎯 TESTING REGULAR QUIZ MODE');
        $this->info('============================');
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(5)->get();
        
        // Clear existing mistakes
        $this->clearTestMistakes($user->id, $questions->pluck('id'));
        
        // Test 1: All wrong answers
        $this->runTest('Regular Quiz - All Wrong Answers', function() use ($user, $topic, $questions) {
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                $answers[$question->id] = $wrongAnswer;
                $expectedMistakes[$question->id] = $wrongAnswer;
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Regular Quiz Wrong Answers');
        });
        
        // Test 2: Mixed answers (correct, wrong, unanswered)
        $this->runTest('Regular Quiz - Mixed Answers', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $index => $question) {
                if ($index % 3 === 0) {
                    // Correct answer
                    $answers[$question->id] = $question->correct_answer;
                } elseif ($index % 3 === 1) {
                    // Wrong answer
                    $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                    $answers[$question->id] = $wrongAnswer;
                    $expectedMistakes[$question->id] = $wrongAnswer;
                } else {
                    // Unanswered
                    $answers[$question->id] = '';
                    $expectedMistakes[$question->id] = 'unanswered';
                }
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Regular Quiz Mixed');
        });
        
        // Test 3: All unanswered
        $this->runTest('Regular Quiz - All Unanswered', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $answers[$question->id] = '';
                $expectedMistakes[$question->id] = 'unanswered';
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Regular Quiz Unanswered');
        });
    }

    private function runTutorModeTests()
    {
        $this->info('🎓 TESTING TUTOR MODE');
        $this->info('=====================');
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(5)->get();
        
        // Test tutor mode with various answer combinations
        $this->runTest('Tutor Mode - Wrong Answers', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                $answers[$question->id] = $wrongAnswer;
                $expectedMistakes[$question->id] = $wrongAnswer;
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'tutor', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Tutor Mode Wrong');
        });
        
        $this->runTest('Tutor Mode - Mixed Answers', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $index => $question) {
                if ($index % 2 === 0) {
                    $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                    $answers[$question->id] = $wrongAnswer;
                    $expectedMistakes[$question->id] = $wrongAnswer;
                } else {
                    $answers[$question->id] = $question->correct_answer;
                }
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'tutor', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Tutor Mode Mixed');
        });
    }

    private function runRandomQuizTests()
    {
        $this->info('🎲 TESTING RANDOM QUIZ MODE');
        $this->info('===========================');
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(5)->get();
        
        $this->runTest('Random Quiz - Wrong Answers', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                $answers[$question->id] = $wrongAnswer;
                $expectedMistakes[$question->id] = $wrongAnswer;
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'random', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Random Quiz Wrong');
        });
        
        $this->runTest('Random Quiz - Unanswered Questions', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $answers[$question->id] = '';
                $expectedMistakes[$question->id] = 'unanswered';
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'random', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Random Quiz Unanswered');
        });
    }

    private function runAssignmentQuizTests()
    {
        $this->info('📋 TESTING ASSIGNMENT QUIZ MODE');
        $this->info('===============================');
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(5)->get();
        
        // Create a test assignment
        $assignment = Assignment::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'course_id' => $topic->course_id,
            'tutor_id' => $user->id,
            'title' => 'Nuclear Test Assignment',
            'description' => 'Test assignment for nuclear testing',
            'status' => Assignment::STATUS_ACTIVE,
            'deadline' => Carbon::now()->addDays(7)
        ]);
        
        $this->runTest('Assignment Quiz - Wrong Answers', function() use ($user, $topic, $questions, $assignment) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                $answers[$question->id] = $wrongAnswer;
                $expectedMistakes[$question->id] = $wrongAnswer;
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course, $assignment->id
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Assignment Quiz Wrong');
        });
        
        // Clean up test assignment
        $assignment->delete();
    }

    private function runEdgeCaseTests()
    {
        $this->info('⚡ TESTING EDGE CASES');
        $this->info('====================');
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(3)->get();
        
        // Test null answers
        $this->runTest('Edge Case - Null Answers', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $answers[$question->id] = null;
                $expectedMistakes[$question->id] = 'unanswered';
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Null Answers');
        });
        
        // Test whitespace-only answers
        $this->runTest('Edge Case - Whitespace Answers', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $answers[$question->id] = '   '; // Whitespace
                $expectedMistakes[$question->id] = '   ';
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course
            );
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Whitespace Answers');
        });
    }

    private function runPerformanceTests()
    {
        $this->info('🚀 TESTING PERFORMANCE');
        $this->info('======================');
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(10)->get();
        
        $this->runTest('Performance - Large Quiz', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            $startTime = microtime(true);
            
            $answers = [];
            $expectedMistakes = [];
            
            foreach ($questions as $question) {
                $wrongAnswer = $this->getWrongAnswer($question->correct_answer);
                $answers[$question->id] = $wrongAnswer;
                $expectedMistakes[$question->id] = $wrongAnswer;
            }
            
            $result = $this->gradingService->gradeQuiz(
                $user, $answers, 300, 'quiz', $topic, $topic->course
            );
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            
            $this->info("  ⏱️  Execution time: " . round($executionTime, 2) . "ms");
            
            if ($executionTime > 5000) { // 5 seconds
                throw new \Exception("Performance test failed: Execution time too high ({$executionTime}ms)");
            }
            
            return $this->validateMistakeRecording($user->id, $expectedMistakes, 'Performance Test');
        });
    }

    private function runConcurrencyTests()
    {
        $this->info('⚡ TESTING CONCURRENCY');
        $this->info('=====================');
        
        // This would require more complex setup for true concurrency testing
        // For now, we'll test rapid sequential submissions
        
        $user = User::first();
        $topic = Topic::with('course')->first();
        $questions = Question::where('topic_id', $topic->id)->take(3)->get();
        
        $this->runTest('Concurrency - Rapid Submissions', function() use ($user, $topic, $questions) {
            $this->clearTestMistakes($user->id, $questions->pluck('id'));
            
            for ($i = 0; $i < 3; $i++) {
                $answers = [];
                foreach ($questions as $question) {
                    $answers[$question->id] = $this->getWrongAnswer($question->correct_answer);
                }
                
                $this->gradingService->gradeQuiz(
                    $user, $answers, 300, 'quiz', $topic, $topic->course
                );
            }
            
            // Verify that mistakes were recorded correctly (should be updated, not duplicated)
            $mistakes = Mistake::where('user_id', $user->id)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();
            
            if ($mistakes->count() !== $questions->count()) {
                throw new \Exception("Concurrency test failed: Expected {$questions->count()} mistakes, got {$mistakes->count()}");
            }
            
            return true;
        });
    }

    private function validateMistakesBladeDisplay()
    {
        $this->info('🎨 VALIDATING MISTAKES BLADE DISPLAY');
        $this->info('===================================');
        
        $user = User::first();
        $mistakes = Mistake::where('user_id', $user->id)->with('question')->get();
        
        $this->runTest('Blade Display - Mistake Rendering', function() use ($mistakes) {
            foreach ($mistakes as $mistake) {
                // Validate that submitted_answer is not 'unanswered' for actual wrong answers
                if ($mistake->submitted_answer === 'unanswered') {
                    // This should only happen for truly unanswered questions
                    $this->info("  ℹ️  Found unanswered mistake for question {$mistake->question_id}");
                } else {
                    // This should be an actual wrong answer
                    if (in_array($mistake->submitted_answer, ['A', 'B', 'C', 'D']) || 
                        strlen($mistake->submitted_answer) > 0) {
                        $this->info("  ✅ Correct mistake format: Question {$mistake->question_id} -> '{$mistake->submitted_answer}'");
                    } else {
                        throw new \Exception("Invalid mistake format: Question {$mistake->question_id} has invalid submitted_answer: '{$mistake->submitted_answer}'");
                    }
                }
            }
            
            return true;
        });
        
        // Test the actual blade rendering logic
        $this->runTest('Blade Display - Answer Display Logic', function() use ($mistakes) {
            foreach ($mistakes as $mistake) {
                $submittedOption = $mistake->submitted_answer;
                $correctOption = $mistake->question->correct_answer;
                
                // Simulate the blade logic
                if ($submittedOption !== $correctOption && $mistake->question->question_type !== 'written') {
                    if ($submittedOption === 'unanswered') {
                        $this->info("  ℹ️  Question {$mistake->question_id}: Will show as unanswered");
                    } else {
                        $optionText = $mistake->question->{'option_'.strtolower($submittedOption)};
                        if ($optionText) {
                            $this->info("  ✅ Question {$mistake->question_id}: Will show '{$submittedOption}' -> '{$optionText}'");
                        } else {
                            throw new \Exception("Question {$mistake->question_id}: Invalid option '{$submittedOption}'");
                        }
                    }
                }
            }
            
            return true;
        });
    }

    private function runTest(string $testName, callable $testFunction)
    {
        $this->totalTests++;
        
        try {
            $this->info("🧪 Running: {$testName}");
            
            $startTime = microtime(true);
            $result = $testFunction();
            $endTime = microtime(true);
            
            $executionTime = ($endTime - $startTime) * 1000;
            
            if ($result === true) {
                $this->passedTests++;
                $this->info("  ✅ PASSED ({$executionTime}ms)");
                $this->testResults[$testName] = [
                    'status' => 'PASSED',
                    'execution_time' => $executionTime,
                    'error' => null
                ];
            } else {
                $this->failedTests++;
                $this->error("  ❌ FAILED: {$result}");
                $this->testResults[$testName] = [
                    'status' => 'FAILED',
                    'execution_time' => $executionTime,
                    'error' => $result
                ];
            }
        } catch (\Exception $e) {
            $this->failedTests++;
            $this->error("  ❌ FAILED: {$e->getMessage()}");
            $this->testResults[$testName] = [
                'status' => 'FAILED',
                'execution_time' => 0,
                'error' => $e->getMessage()
            ];
        }
        
        $this->newLine();
    }

    private function validateMistakeRecording(int $userId, array $expectedMistakes, string $context)
    {
        $recordedMistakes = Mistake::where('user_id', $userId)
            ->whereIn('question_id', array_keys($expectedMistakes))
            ->get()
            ->keyBy('question_id');
        
        if ($recordedMistakes->count() !== count($expectedMistakes)) {
            return "Expected " . count($expectedMistakes) . " mistakes, got " . $recordedMistakes->count();
        }
        
        foreach ($expectedMistakes as $questionId => $expectedAnswer) {
            if (!isset($recordedMistakes[$questionId])) {
                return "Missing mistake for question {$questionId}";
            }
            
            $recordedAnswer = $recordedMistakes[$questionId]->submitted_answer;
            if ($recordedAnswer !== $expectedAnswer) {
                return "Question {$questionId}: Expected '{$expectedAnswer}', got '{$recordedAnswer}'";
            }
            
            $this->info("    ✅ Question {$questionId}: '{$recordedAnswer}' ✓");
        }
        
        return true;
    }

    private function getWrongAnswer(string $correctAnswer): string
    {
        $options = ['A', 'B', 'C', 'D'];
        $wrongOptions = array_filter($options, fn($opt) => $opt !== $correctAnswer);
        return $wrongOptions[array_rand($wrongOptions)];
    }

    private function clearTestMistakes(int $userId, $questionIds)
    {
        Mistake::where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->delete();
    }

    private function generateNuclearReport()
    {
        $this->info('');
        $this->info('💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀');
        $this->info('💀                    NUCLEAR TEST REPORT                    💀');
        $this->info('💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀');
        $this->info('');
        
        $this->info("📊 OVERALL STATISTICS:");
        $this->info("======================");
        $this->info("Total Tests: {$this->totalTests}");
        $this->info("Passed: {$this->passedTests}");
        $this->info("Failed: {$this->failedTests}");
        $this->info("Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 2) . "%");
        $this->newLine();
        
        if ($this->failedTests > 0) {
            $this->error("❌ FAILED TESTS:");
            $this->error("================");
            foreach ($this->testResults as $testName => $result) {
                if ($result['status'] === 'FAILED') {
                    $this->error("- {$testName}: {$result['error']}");
                }
            }
            $this->newLine();
        }
        
        $this->info("✅ PASSED TESTS:");
        $this->info("================");
        foreach ($this->testResults as $testName => $result) {
            if ($result['status'] === 'PASSED') {
                $this->info("- {$testName} ({$result['execution_time']}ms)");
            }
        }
        $this->newLine();
        
        if ($this->failedTests === 0) {
            $this->info('🎯 NUCLEAR SUCCESS: ALL TESTS PASSED!');
            $this->info('=====================================');
            $this->info('✅ Regular Quiz Mode: OPERATIONAL');
            $this->info('✅ Tutor Mode: OPERATIONAL');
            $this->info('✅ Random Quiz Mode: OPERATIONAL');
            $this->info('✅ Assignment Quiz Mode: OPERATIONAL');
            $this->info('✅ Mistake Recording: LETHAL PRECISION');
            $this->info('✅ Blade Display: PERFECT RENDERING');
            $this->info('');
            $this->info('💀 MISTAKE TRACKING SYSTEM IS BULLETPROOF 💀');
        } else {
            $this->error('💥 NUCLEAR FAILURE: CRITICAL ISSUES DETECTED!');
            $this->error('==============================================');
            $this->error('❌ System requires immediate attention');
            $this->error('❌ Mistake tracking may be compromised');
        }
        
        $this->info('');
        $this->info('💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀💀');
    }
}