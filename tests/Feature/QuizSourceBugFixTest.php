<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Services\Quiz\QuizExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class QuizSourceBugFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
        
        // Create questions for all 5 sources
        $sources = [
            'Ain Shams University' => 4,
            'USMLE Step 1' => 3,
            'Alexandria University' => 2,
            'Cairo University' => 4,
            'Assiut University' => 2
        ];
        
        foreach ($sources as $source => $count) {
            for ($i = 0; $i < $count; $i++) {
                Question::factory()->create([
                    'topic_id' => $this->topic->id,
                    'source' => $source,
                    'correct_answer' => 'A'
                ]);
            }
        }
    }

    /** @test */
    public function it_creates_quiz_attempts_for_all_sources()
    {
        // Get questions from all sources
        $questions = Question::where('topic_id', $this->topic->id)->get();
        $this->assertEquals(15, $questions->count());
        
        // Verify we have all 5 sources
        $sources = $questions->pluck('source')->unique();
        $this->assertEquals(5, $sources->count());
        
        // Create test answers for questions from each source
        $testAnswers = [];
        $sources = $questions->groupBy('source');
        
        foreach ($sources as $source => $sourceQuestions) {
            // Take 2 questions from each source
            $testQuestions = $sourceQuestions->take(2);
            foreach ($testQuestions as $question) {
                $testAnswers[$question->id] = $question->correct_answer;
            }
        }
        
        $this->assertEquals(10, count($testAnswers));
        
        // Create mock request
        $request = new Request();
        $request->merge([
            'answers' => $testAnswers,
            'start_time' => now()->subMinutes(5)->toISOString()
        ]);
        
        // Run quiz grading
        $quizService = new QuizExecutionService();
        $results = $quizService->gradeQuiz($request, $this->course, $this->topic, $this->user);
        
        // Verify all 5 sources have quiz attempts
        $this->assertEquals(5, count($results['quiz_attempts']));
        
        // Verify each source has a quiz attempt
        $attemptSources = collect($results['quiz_attempts'])->pluck('source')->toArray();
        $expectedSources = ['Ain Shams University', 'USMLE Step 1', 'Alexandria University', 'Cairo University', 'Assiut University'];
        
        sort($attemptSources);
        sort($expectedSources);
        
        $this->assertEquals($expectedSources, $attemptSources);
        
        // Verify database has all 5 attempts
        $dbAttempts = QuizAttempt::where('user_id', $this->user->id)
            ->where('topic_id', $this->topic->id)
            ->where('study_mode', 'quiz')
            ->get();
            
        $this->assertEquals(5, $dbAttempts->count());
        
        // Verify each attempt has correct data
        foreach ($dbAttempts as $attempt) {
            $this->assertNotNull($attempt->source);
            $this->assertGreaterThan(0, $attempt->total_questions);
            $this->assertGreaterThanOrEqual(0, $attempt->score);
            $this->assertGreaterThanOrEqual(0, $attempt->correct_answers);
            $this->assertIsArray($attempt->question_data);
        }
    }

    /** @test */
    public function it_handles_mixed_correct_and_incorrect_answers()
    {
        // Create test answers with some incorrect answers
        $questions = Question::where('topic_id', $this->topic->id)->get();
        $testAnswers = [];
        
        $sources = $questions->groupBy('source');
        foreach ($sources as $source => $sourceQuestions) {
            $testQuestions = $sourceQuestions->take(2);
            foreach ($testQuestions as $index => $question) {
                // Make first question correct, second incorrect
                $testAnswers[$question->id] = $index === 0 ? $question->correct_answer : 'B';
            }
        }
        
        // Create mock request
        $request = new Request();
        $request->merge([
            'answers' => $testAnswers,
            'start_time' => now()->subMinutes(5)->toISOString()
        ]);
        
        // Run quiz grading
        $quizService = new QuizExecutionService();
        $results = $quizService->gradeQuiz($request, $this->course, $this->topic, $this->user);
        
        // Verify all 5 sources have quiz attempts
        $this->assertEquals(5, count($results['quiz_attempts']));
        
        // Verify each source has 1 correct answer (first question) and 1 incorrect (second question)
        foreach ($results['quiz_attempts'] as $attempt) {
            $this->assertEquals(1, $attempt->score);
            $this->assertEquals(1, $attempt->correct_answers);
            $this->assertEquals(2, $attempt->total_questions);
            $this->assertEquals(50.0, $attempt->percentage_grade);
        }
    }

    /** @test */
    public function it_handles_questions_with_null_source()
    {
        // Create a question with null source
        $nullSourceQuestion = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'source' => null,
            'correct_answer' => 'A'
        ]);
        
        // Create test answers including the null source question
        $questions = Question::where('topic_id', $this->topic->id)->get();
        $testAnswers = [];
        
        // Take 1 question from each source plus the null source question
        $sources = $questions->groupBy('source');
        foreach ($sources as $source => $sourceQuestions) {
            $question = $sourceQuestions->first();
            $testAnswers[$question->id] = $question->correct_answer;
        }
        
        // Add the null source question
        $testAnswers[$nullSourceQuestion->id] = $nullSourceQuestion->correct_answer;
        
        // Create mock request
        $request = new Request();
        $request->merge([
            'answers' => $testAnswers,
            'start_time' => now()->subMinutes(5)->toISOString()
        ]);
        
        // Run quiz grading
        $quizService = new QuizExecutionService();
        $results = $quizService->gradeQuiz($request, $this->course, $this->topic, $this->user);
        
        // Verify we have attempts for all sources including "Unknown"
        $attemptSources = collect($results['quiz_attempts'])->pluck('source')->toArray();
        $this->assertContains('Unknown', $attemptSources);
        
        // Verify database has the correct number of attempts
        $dbAttempts = QuizAttempt::where('user_id', $this->user->id)
            ->where('topic_id', $this->topic->id)
            ->where('study_mode', 'quiz')
            ->get();
            
        $this->assertEquals(6, $dbAttempts->count()); // 5 sources + 1 Unknown
    }

    /** @test */
    public function it_calculates_source_progress_correctly()
    {
        // Create test answers
        $questions = Question::where('topic_id', $this->topic->id)->get();
        $testAnswers = [];
        
        $sources = $questions->groupBy('source');
        foreach ($sources as $source => $sourceQuestions) {
            $testQuestions = $sourceQuestions->take(2);
            foreach ($testQuestions as $question) {
                $testAnswers[$question->id] = $question->correct_answer;
            }
        }
        
        // Create mock request
        $request = new Request();
        $request->merge([
            'answers' => $testAnswers,
            'start_time' => now()->subMinutes(5)->toISOString()
        ]);
        
        // Run quiz grading
        $quizService = new QuizExecutionService();
        $results = $quizService->gradeQuiz($request, $this->course, $this->topic, $this->user);
        
        // Verify source progress is calculated correctly
        $sourceProgress = $results['source_progress'];
        
        $this->assertCount(5, $sourceProgress);
        
        foreach ($sourceProgress as $source => $progress) {
            $this->assertArrayHasKey('completed', $progress);
            $this->assertArrayHasKey('total', $progress);
            $this->assertArrayHasKey('percentage', $progress);
            $this->assertArrayHasKey('has_attempts', $progress);
            
            // Each source should have 2 completed questions
            $this->assertEquals(2, $progress['completed']);
            $this->assertEquals(2, $progress['total']);
            $this->assertEquals(100.0, $progress['percentage']);
            $this->assertTrue($progress['has_attempts']);
        }
    }
}
