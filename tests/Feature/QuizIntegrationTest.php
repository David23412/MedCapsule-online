<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Mistake;
use App\Models\QuizAttempt;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $course;
    protected $topic;
    protected $questions;
    protected $gradingService;
    protected $quizService;
    protected $cacheService;
    protected $userStatsService;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data manually to avoid factory issues
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'xp' => 0,
            'correct_answers_count' => 0,
            'total_questions_attempted' => 0
        ]);

        $this->course = Course::create([
            'name' => 'Test Course',
            'description' => 'Test course description',
            'price' => 0, // Free course for testing
            'user_id' => $this->user->id
        ]);

        $this->topic = Topic::create([
            'name' => 'Test Topic',
            'description' => 'Test topic description',
            'course_id' => $this->course->id,
            'user_id' => $this->user->id,
            'case_type' => 'cases',
            'display_order' => 1
        ]);

        // Create test questions manually
        $this->questions = collect([
            Question::create([
                'topic_id' => $this->topic->id,
                'question' => 'What is 2+2?',
                'correct_answer' => 'B',
                'option_a' => '3',
                'option_b' => '4',
                'option_c' => '5',
                'option_d' => '6',
                'question_type' => 'multiple_choice',
                'source' => 'Test Source 1',
                'explanation' => 'Basic arithmetic'
            ]),
            Question::create([
                'topic_id' => $this->topic->id,
                'question' => 'What is the capital of France?',
                'correct_answer' => 'C',
                'option_a' => 'London',
                'option_b' => 'Berlin',
                'option_c' => 'Paris',
                'option_d' => 'Madrid',
                'question_type' => 'multiple_choice',
                'source' => 'Test Source 2',
                'explanation' => 'Geography knowledge'
            ]),
            Question::create([
                'topic_id' => $this->topic->id,
                'question' => 'Explain photosynthesis.',
                'correct_answer' => 'Process by which plants convert sunlight into energy',
                'question_type' => 'written',
                'source' => 'Test Source 1',
                'explanation' => 'Biological process'
            ])
        ]);

        // Initialize services
        $this->gradingService = app(GradingService::class);
        $this->quizService = app(QuizService::class);
        $this->cacheService = app(UltraConsolidatedCacheService::class);
        $this->userStatsService = app(UserStatsService::class);
        $this->notificationService = app(NotificationService::class);
    }

    /**
     * Test Normal Quiz Flow
     */
    #[Test]
    public function test_normal_quiz_flow()
    {
        $this->actingAs($this->user);

        // Step 1: Initialize quiz
        $quizData = $this->quizService->initializeQuiz(
            $this->user,
            $this->course,
            $this->topic,
            ['all'],
            'quiz'
        );

        $this->assertTrue($quizData['success']);
        $this->assertCount(3, $quizData['questions']);
        $this->assertEquals('quiz', $quizData['mode']);

        // Step 2: Prepare answers
        $answers = [
            $this->questions[0]->id => 'B', // Correct
            $this->questions[1]->id => 'C', // Correct
            $this->questions[2]->id => 'Process by which plants convert sunlight into energy' // Correct
        ];

        // Debug: Check question data
        Log::info('Debug: Question data', [
            'question_1' => [
                'id' => $this->questions[0]->id,
                'correct_answer' => $this->questions[0]->correct_answer,
                'submitted_answer' => $answers[$this->questions[0]->id],
                'option_a' => $this->questions[0]->option_a,
                'option_b' => $this->questions[0]->option_b,
                'option_c' => $this->questions[0]->option_c,
                'option_d' => $this->questions[0]->option_d
            ],
            'question_2' => [
                'id' => $this->questions[1]->id,
                'correct_answer' => $this->questions[1]->correct_answer,
                'submitted_answer' => $answers[$this->questions[1]->id],
                'option_a' => $this->questions[1]->option_a,
                'option_b' => $this->questions[1]->option_b,
                'option_c' => $this->questions[1]->option_c,
                'option_d' => $this->questions[1]->option_d
            ],
            'question_3' => [
                'id' => $this->questions[2]->id,
                'correct_answer' => $this->questions[2]->correct_answer,
                'submitted_answer' => $answers[$this->questions[2]->id]
            ]
        ]);

        // Test individual question evaluation
        Log::info('Debug: Individual question evaluation', [
            'question_1_is_correct' => $this->questions[0]->isAnswerCorrect($answers[$this->questions[0]->id]),
            'question_2_is_correct' => $this->questions[1]->isAnswerCorrect($answers[$this->questions[1]->id]),
            'question_3_is_correct' => $this->questions[2]->isAnswerCorrect($answers[$this->questions[2]->id])
        ]);

        // Step 3: Grade quiz
        $gradingResult = $this->gradingService->gradeQuiz(
            $this->user,
            $answers,
            120, // 2 minutes
            'quiz',
            $this->topic,
            $this->course
        );

        // Debug: Check grading result
        Log::info('Debug: Grading result', $gradingResult);

        // Step 4: Verify results
        $this->assertTrue($gradingResult['success']);
        $this->assertEquals(3, $gradingResult['score']);
        $this->assertEquals(3, $gradingResult['total_questions']);
        $this->assertEquals(100.0, $gradingResult['percentage_grade']);

        // Step 5: Verify database state
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'quiz_type' => 'normal',
            'score' => 3,
            'total_questions' => 3,
            'percentage_grade' => 100.0
        ]);

        // Step 6: Verify user stats updated
        $this->user->refresh();
        $this->assertGreaterThan(0, $this->user->xp);
        $this->assertEquals(3, $this->user->correct_answers_count);
        $this->assertEquals(3, $this->user->total_questions_attempted);

        // Step 7: Verify no mistakes created (all correct)
        $this->assertDatabaseMissing('mistakes', [
            'user_id' => $this->user->id
        ]);

        Log::info('✅ Normal quiz flow test passed', [
            'user_id' => $this->user->id,
            'score' => $gradingResult['score'],
            'xp_earned' => $this->user->xp
        ]);
    }

    /**
     * Test Tutor Quiz Flow
     */
    #[Test]
    public function test_tutor_quiz_flow()
    {
        $this->actingAs($this->user);

        // Step 1: Initialize tutor quiz
        $quizData = $this->quizService->initializeQuiz(
            $this->user,
            $this->course,
            $this->topic,
            ['all'],
            'tutor'
        );

        $this->assertTrue($quizData['success']);
        $this->assertEquals('tutor', $quizData['mode']);

        // Step 2: Test tutor answer checking for multiple questions
        $correctAnswer = 'B';
        $incorrectAnswer = 'A';

        // Test correct answer for first question
        $result = $this->gradingService->gradeTutorAnswer(
            $this->user,
            $this->questions[0],
            $correctAnswer
        );

        $this->assertTrue($result['is_correct']);
        $this->assertEquals('Correct!', $result['message']);

        // Verify tutor attempt created with correct type and mode
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'tutor'
        ]);

        // Test incorrect answer for second question
        $result = $this->gradingService->gradeTutorAnswer(
            $this->user,
            $this->questions[1],
            $incorrectAnswer
        );

        $this->assertFalse($result['is_correct']);
        $this->assertStringContainsString('Incorrect', $result['message']);

        // Verify mistake created for incorrect answer
        $this->assertDatabaseHas('mistakes', [
            'user_id' => $this->user->id,
            'question_id' => $this->questions[1]->id
        ]);

        // Step 3: Verify user stats updated
        $this->user->refresh();
        $this->assertGreaterThan(0, $this->user->xp);
        $this->assertEquals(1, $this->user->correct_answers_count);
        $this->assertEquals(2, $this->user->total_questions_attempted);

        Log::info('✅ Tutor quiz flow test passed', [
            'user_id' => $this->user->id,
            'correct_answers' => $this->user->correct_answers_count,
            'total_attempted' => $this->user->total_questions_attempted
        ]);
    }

    /**
     * Test Cache Behavior
     */
    #[Test]
    public function test_cache_behavior()
    {
        $this->actingAs($this->user);

        // Step 1: Test cache service methods
        $testKey = 'test_cache_key';
        $testValue = ['test' => 'data'];

        // Test put and get
        $this->cacheService->put($testKey, $testValue, 60);
        $retrievedValue = $this->cacheService->get($testKey);
        
        $this->assertEquals($testValue, $retrievedValue);

        // Test remember
        $rememberedValue = $this->cacheService->remember('test_remember', 60, function() {
            return ['remembered' => 'data'];
        });

        $this->assertEquals(['remembered' => 'data'], $rememberedValue);

        // Test forget
        $this->cacheService->forget($testKey);
        $forgottenValue = $this->cacheService->get($testKey);
        
        $this->assertNull($forgottenValue);

        Log::info('✅ Cache behavior test passed');
    }

    /**
     * Test Performance Metrics
     */
    #[Test]
    public function test_performance_metrics()
    {
        $this->actingAs($this->user);

        $startTime = microtime(true);

        // Perform quiz operations
        $quizData = $this->quizService->initializeQuiz(
            $this->user,
            $this->course,
            $this->topic,
            ['all'],
            'quiz'
        );

        $answers = [
            $this->questions[0]->id => 'B',
            $this->questions[1]->id => 'C'
        ];

        $gradingResult = $this->gradingService->gradeQuiz(
            $this->user,
            $answers,
            120,
            'quiz',
            $this->topic,
            $this->course
        );

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Performance assertions
        $this->assertLessThan(5000, $executionTime); // Should complete in under 5 seconds
        $this->assertTrue($quizData['success']);
        $this->assertTrue($gradingResult['success']);

        Log::info('✅ Performance metrics test passed', [
            'execution_time_ms' => round($executionTime, 2),
            'questions_count' => count($answers),
            'score' => $gradingResult['score']
        ]);
    }
} 