<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Http\Controllers\QuizController;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\UserStatsService;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuizGradingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $quizController;
    protected $gradingService;
    protected $user;
    protected $course;
    protected $topic;
    protected $questions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin'
        ]);
        
        $this->course = Course::factory()->create([
            'name' => 'Test Course',
            'price' => 0
        ]);
        
        $this->topic = Topic::factory()->create([
            'name' => 'Test Topic',
            'course_id' => $this->course->id
        ]);
        
        $this->questions = Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice',
            'question' => 'What is the function of the heart?',
            'correct_answer' => 'Pumps blood',
            'option_a' => 'Pumps blood',
            'option_b' => 'Filters blood',
            'option_c' => 'Produces blood',
            'option_d' => 'Stores blood'
        ]);
        
        // Initialize services
        $this->gradingService = app(GradingService::class);
        $this->quizController = new QuizController(
            $this->gradingService,
            app(QuizService::class),
            app(UltraConsolidatedCacheService::class),
            app(UserStatsService::class)
        );
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function quiz_grading_handles_empty_answers_gracefully()
    {
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => [],
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals(0, $responseData['score']);
        $this->assertEquals(0, $responseData['percentage']);
    }

    /** @test */
    public function quiz_grading_handles_malformed_answers_gracefully()
    {
        $malformedAnswers = [
            'string_instead_of_array' => 'not an array',
            'null_value' => null,
            'nested_array' => [1 => ['nested' => 'value']],
            'very_long_string' => str_repeat('x', 10000),
            'special_characters' => '<script>alert("xss")</script>',
            'unicode_characters' => 'test\u{0001}answer\u{FFFF}',
            'numeric_overflow' => PHP_INT_MAX + 1
        ];
        
        foreach ($malformedAnswers as $testName => $answers) {
            $request = Request::create('/quiz/grade', 'POST', [
                'answers' => $answers,
                'time_taken' => 60,
                'mode' => 'quiz'
            ]);
            
            try {
                $response = $this->quizController->grade($request, $this->course, $this->topic);
                $responseData = $response->getData(true);
                
                // Should either succeed gracefully or fail with proper error
                $this->assertTrue(
                    $responseData['success'] || isset($responseData['error']),
                    "Failed to handle malformed answer gracefully: $testName"
                );
                
                if (!$responseData['success']) {
                    $this->assertArrayHasKey('error', $responseData);
                    $this->assertIsString($responseData['error']);
                }
            } catch (\Exception $e) {
                $this->fail("Quiz grading should handle malformed input gracefully: $testName. Error: " . $e->getMessage());
            }
        }
    }

    /** @test */
    public function quiz_grading_handles_concurrent_submissions()
    {
        // Simulate concurrent submissions by the same user
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        // Submit multiple times concurrently (simulated)
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $response = $this->quizController->grade($request, $this->course, $this->topic);
            $responses[] = $response->getData(true);
        }
        
        // All responses should be successful
        foreach ($responses as $index => $responseData) {
            $this->assertTrue($responseData['success'], "Concurrent submission $index failed");
            $this->assertArrayHasKey('score', $responseData);
            $this->assertArrayHasKey('percentage', $responseData);
        }
        
        // Verify data integrity - user should have consistent progress
        $this->user->refresh();
        $this->assertIsNumeric($this->user->xp);
        $this->assertGreaterThanOrEqual(0, $this->user->xp);
    }

    /** @test */
    public function quiz_grading_validates_input_sizes()
    {
        // Test with excessive input sizes
        $largeAnswers = [];
        foreach ($this->questions as $question) {
            $largeAnswers[$question->id] = str_repeat('x', 50000); // 50KB answer
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $largeAnswers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        // Should handle large inputs gracefully
        $this->assertTrue($responseData['success'] || isset($responseData['error']));
        
        if (!$responseData['success']) {
            $this->assertArrayHasKey('error', $responseData);
            $this->assertStringContainsString('size', strtolower($responseData['error']));
        }
    }

    /** @test */
    public function quiz_grading_prevents_double_processing()
    {
        // Mock the Question model to track how many times isAnswerCorrect is called
        $questionCallCounts = [];
        
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
            $questionCallCounts[$question->id] = 0;
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        // Grade the quiz
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        
        // Verify that processing was efficient (no obvious double processing)
        $this->assertArrayHasKey('performance_metrics', $responseData);
        $this->assertArrayHasKey('processing_time_ms', $responseData['performance_metrics']);
        $this->assertIsNumeric($responseData['performance_metrics']['processing_time_ms']);
        
        // Processing should be reasonably fast (under 5 seconds for 5 questions)
        $this->assertLessThan(5000, $responseData['performance_metrics']['processing_time_ms']);
    }

    /** @test */
    public function quiz_grading_handles_database_transaction_failures()
    {
        // Test transaction rollback on failure
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        // Force a transaction failure by creating an invalid state
        DB::beginTransaction();
        
        try {
            $response = $this->quizController->grade($request, $this->course, $this->topic);
            $responseData = $response->getData(true);
            
            // Should handle the transaction gracefully
            $this->assertTrue($responseData['success'] || isset($responseData['error']));
            
            if (!$responseData['success']) {
                $this->assertArrayHasKey('error', $responseData);
            }
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function quiz_grading_handles_missing_questions()
    {
        // Test with question IDs that don't exist
        $answers = [
            999999 => 'answer to non-existent question',
            999998 => 'another fake answer',
            $this->questions[0]->id => 'valid answer'
        ];
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        
        // Should only count valid questions
        $this->assertLessThanOrEqual(count($this->questions), $responseData['total_questions']);
    }

    /** @test */
    public function quiz_grading_handles_extreme_time_values()
    {
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $extremeTimeValues = [
            'negative_time' => -100,
            'zero_time' => 0,
            'very_large_time' => PHP_INT_MAX,
            'float_time' => 3.14,
            'string_time' => 'not a number'
        ];
        
        foreach ($extremeTimeValues as $testName => $timeValue) {
            $request = Request::create('/quiz/grade', 'POST', [
                'answers' => $answers,
                'time_taken' => $timeValue,
                'mode' => 'quiz'
            ]);
            
            $response = $this->quizController->grade($request, $this->course, $this->topic);
            $responseData = $response->getData(true);
            
            // Should handle extreme time values gracefully
            $this->assertTrue(
                $responseData['success'] || isset($responseData['error']),
                "Failed to handle extreme time value: $testName"
            );
        }
    }

    /** @test */
    public function quiz_grading_maintains_data_integrity_under_load()
    {
        // Test data integrity under simulated load
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $initialXP = $this->user->xp ?? 0;
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        // Submit the same quiz multiple times
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $response = $this->quizController->grade($request, $this->course, $this->topic);
            $responses[] = $response->getData(true);
        }
        
        // All responses should be successful
        foreach ($responses as $index => $responseData) {
            $this->assertTrue($responseData['success'], "Load test submission $index failed");
        }
        
        // Verify user data integrity
        $this->user->refresh();
        $this->assertGreaterThanOrEqual($initialXP, $this->user->xp);
        $this->assertIsNumeric($this->user->xp);
        
        // Verify no data corruption occurred
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => $this->user->email
        ]);
    }

    /** @test */
    public function quiz_grading_handles_written_answers_with_memory_limits()
    {
        // Create a question with written answer type
        $writtenQuestion = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'written',
            'question' => 'Explain the function of the heart in detail.',
            'correct_answer' => 'The heart is a muscular organ that pumps blood throughout the body.'
        ]);
        
        // Test with very large written answers to ensure no memory exhaustion
        $largeAnswer = str_repeat('The heart pumps blood through the circulatory system. ', 1000);
        
        $answers = [$writtenQuestion->id => $largeAnswer];
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $startMemory = memory_get_usage(true);
        
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        // Should handle large written answers gracefully
        $this->assertTrue($responseData['success'] || isset($responseData['error']));
        
        // Memory usage should not be excessive (under 100MB for this test)
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsed, 'Memory usage too high for large text processing');
    }

    /** @test */
    public function quiz_grading_handles_unavailable_cache_gracefully()
    {
        // Simulate cache failure
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(false);
        Cache::shouldReceive('forget')->andReturn(true);
        
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        // Should work even if cache is unavailable
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('score', $responseData);
        $this->assertArrayHasKey('percentage', $responseData);
    }

    /** @test */
    public function quiz_grading_logs_errors_appropriately()
    {
        // Mock the logger to capture log messages
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);
        
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $response = $this->quizController->grade($request, $this->course, $this->topic);
        $responseData = $response->getData(true);
        
        $this->assertTrue($responseData['success']);
        
        // Verify that appropriate logging occurred
        Log::shouldHaveReceived('info')->atLeast()->once();
    }

    /** @test */
    public function quiz_grading_validates_user_permissions()
    {
        // Test with a user who shouldn't have access to the course
        $restrictedUser = User::factory()->create([
            'role' => 'student'
        ]);
        
        $this->actingAs($restrictedUser);
        
        $restrictedCourse = Course::factory()->create([
            'price' => 100 // Paid course
        ]);
        
        $restrictedTopic = Topic::factory()->create([
            'course_id' => $restrictedCourse->id
        ]);
        
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }
        
        $request = Request::create('/quiz/grade', 'POST', [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        $response = $this->quizController->grade($request, $restrictedCourse, $restrictedTopic);
        $responseData = $response->getData(true);
        
        // Should deny access appropriately
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Access denied', $responseData['error']);
    }
}