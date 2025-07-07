<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GradingService;
use App\Services\CacheService;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\WrittenAnswerEvaluationService;
use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\UserTopicProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $gradingService;
    protected $cacheService;
    protected $userStatsService;
    protected $notificationService;
    protected $writtenAnswerEvaluationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->userStatsService = Mockery::mock(UserStatsService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->writtenAnswerEvaluationService = Mockery::mock(WrittenAnswerEvaluationService::class);
        
        $this->gradingService = new GradingService(
            $this->cacheService,
            $this->userStatsService,
            $this->notificationService,
            $this->writtenAnswerEvaluationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_quiz_inputs_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        // Test empty answers
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No answers provided');
        
        $this->gradingService->gradeQuiz($user, [], 60, 'quiz', $topic, $course);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_duration_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid duration');
        
        $this->gradingService->gradeQuiz($user, ['1' => 'A'], -1, 'quiz', $topic, $course);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_answer_length_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $longAnswer = str_repeat('A', 2001);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Answer for question 1 exceeds maximum allowed length of 2000 characters.');
        
        $this->gradingService->gradeQuiz($user, ['1' => $longAnswer], 60, 'quiz', $topic, $course);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_grades_quiz_correctly_with_all_correct_answers()
    {
        $user = User::factory()->create(['xp' => 0]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question1 = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question 1',
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);
        
        $question2 = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question 2',
            'correct_answer' => 'B',
            'source' => 'Test Source'
        ]);

        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with($user, 2, 2, 60);
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once()
            ->with($user->id, $topic->id);
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once()
            ->with($user->id, $topic->id);
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question1->id => 'A', $question2->id => 'B'],
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['score']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(100, $result['percentage']);
        $this->assertArrayHasKey('Test Source', $result['source_progress']);
        $this->assertEquals(2, $result['source_progress']['Test Source']['score']);
        $this->assertEquals(2, $result['source_progress']['Test Source']['total']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_grades_quiz_correctly_with_mixed_answers()
    {
        $user = User::factory()->create(['xp' => 0]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question1 = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question 1',
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);
        
        $question2 = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question 2',
            'correct_answer' => 'B',
            'source' => 'Test Source'
        ]);

        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with($user, 2, 1, 60);
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once()
            ->with($user->id, $topic->id);
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once()
            ->with($user->id, $topic->id);
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question1->id => 'A', $question2->id => 'C'], // One correct, one wrong
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['score']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(50, $result['percentage']);
        $this->assertContains($question2->id, $result['mistakes']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_level_up_correctly()
    {
        $user = User::factory()->create(['xp' => 0]); // Will level up to 1
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question',
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);

        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with($user, 1, 1, 60);
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once()
            ->with($user->id, $topic->id);
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once()
            ->with($user->id, $topic->id);
        
        // No level up notification since user starts at level 1 and stays at level 1
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'A'],
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertFalse($result['level_up']);
        $this->assertEquals(1, $result['new_level']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_written_answers_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'What is the capital of France?',
            'correct_answer' => 'Paris',
            'question_type' => 'written',
            'source' => 'Test Source'
        ]);

        // Mock written answer evaluation
        $this->writtenAnswerEvaluationService->shouldReceive('evaluateAnswer')
            ->once()
            ->with('Paris', 'Paris', [], Mockery::any())
            ->andReturn(['confidence' => 0.9]);

        // Mock other dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once();
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'Paris'],
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['score']);
        $this->assertEquals(100, $result['percentage']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_written_answer_evaluation_failure()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'What is the capital of France?',
            'correct_answer' => 'Paris',
            'question_type' => 'written',
            'source' => 'Test Source'
        ]);

        // Mock written answer evaluation failure
        $this->writtenAnswerEvaluationService->shouldReceive('evaluateAnswer')
            ->once()
            ->andThrow(new \Exception('Evaluation service unavailable'));

        // Mock other dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once();
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'Paris'],
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['score']); // Should fallback to exact match
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_grades_tutor_answer_correctly()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question',
            'correct_answer' => 'A',
            'question_type' => 'multiple_choice',
            'source' => 'Test Source'
        ]);

        $result = $this->gradingService->gradeTutorAnswer($user, $question, 'A');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_correct']);
        $this->assertEquals('A', $result['correct_answer']);
        $this->assertArrayHasKey('source_progress', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_tutor_answer_with_incorrect_answer()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question',
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);

        $result = $this->gradingService->gradeTutorAnswer($user, $question, 'B');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['is_correct']);
        $this->assertEquals('A', $result['correct_answer']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_quiz_attempts_for_multiple_sources()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question1 = Question::factory()->create([
            'topic_id' => $topic->id,
            'correct_answer' => 'A',
            'source' => 'Source A'
        ]);
        
        $question2 = Question::factory()->create([
            'topic_id' => $topic->id,
            'correct_answer' => 'B',
            'source' => 'Source B'
        ]);

        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once();
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question1->id => 'A', $question2->id => 'B'],
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('Source A', $result['source_progress']);
        $this->assertArrayHasKey('Source B', $result['source_progress']);
        
        // Verify quiz attempts were created
        $attempts = QuizAttempt::where('user_id', $user->id)->get();
        $this->assertEquals(2, $attempts->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_topic_progress_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);

        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once();
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'A'],
            60,
            'quiz',
            $topic,
            $course
        );

        // Verify topic progress was updated
        $progress = UserTopicProgress::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();
        
        $this->assertNotNull($progress);
        $this->assertEquals(100, $progress->percentage_grade);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_records_mistakes_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question',
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);

        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserTopicProgressCache')
            ->once();
        
        $this->cacheService->shouldReceive('clearUserMistakesCache')
            ->once();
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'B'], // Wrong answer
            60,
            'quiz',
            $topic,
            $course
        );

        // Verify mistake was recorded
        $mistake = Mistake::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();
        
        $this->assertNotNull($mistake);
        $this->assertEquals('B', $mistake->submitted_answer);
        $this->assertEquals('A', $mistake->correct_answer);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_database_transaction_rollback_on_error()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question' => 'Test question',
            'correct_answer' => 'A',
            'source' => 'Test Source'
        ]);
        
        // Mock userStatsService to throw an exception
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'A'],
            60,
            'quiz',
            $topic,
            $course
        );

        // Verify no quiz attempts were created due to rollback
        $attempts = QuizAttempt::where('user_id', $user->id)->get();
        $this->assertEquals(0, $attempts->count());
    }
} 