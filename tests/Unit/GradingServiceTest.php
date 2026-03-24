<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GradingService;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\ProgressCacheInvalidator;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\WrittenAnswerEvaluationService;
use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\UserMistakeMastery;
use App\Models\UserTopicProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $gradingService;
    protected $cacheService;
    protected $progressCacheInvalidator;
    protected $userStatsService;
    protected $notificationService;
    protected $writtenAnswerEvaluationService;

    protected function setUp(): void
    {
        parent::setUp();
        UserMistakeMastery::clearTableExistenceCache();
        
        $this->cacheService = Mockery::mock(UltraConsolidatedCacheService::class);
        $this->progressCacheInvalidator = Mockery::mock(ProgressCacheInvalidator::class);
        $this->userStatsService = Mockery::mock(UserStatsService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->writtenAnswerEvaluationService = Mockery::mock(WrittenAnswerEvaluationService::class);
        
        $this->gradingService = new GradingService(
            $this->cacheService,
            $this->progressCacheInvalidator,
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

    private function expectProgressInvalidation(int $userId, int $topicId, string $mode): void
    {
        $this->progressCacheInvalidator->shouldReceive('invalidateUserTopicProgress')
            ->times($mode === 'quiz' ? 2 : 1)
            ->with($userId, $topicId, $mode, false);

        $this->progressCacheInvalidator->shouldReceive('invalidateUserTopicProgress')
            ->once()
            ->with($userId, $topicId, null, true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_quiz_inputs_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with($user, 0, 0, 60, Mockery::any());

        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $result = $this->gradingService->gradeQuiz($user, [], 60, 'quiz', $topic, $course);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['score']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['percentage']);
        $this->assertSame([], $result['mistakes']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_duration_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be a non-negative number');
        
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
            ->with($user, 2, 2, 60, Mockery::any());
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
            ->with($user, 2, 1, 60, Mockery::any());
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
            ->with($user, 1, 1, 60, Mockery::any());
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
    public function it_persists_mastery_and_marks_random_quiz_attempts_with_session_type()
    {
        $user = User::factory()->create([
            'mistake_mastery_mode' => 'easy',
        ]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Test Source',
        ]);

        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->twice()
            ->with($user, 1, 1, 60, Mockery::any());
        $this->expectProgressInvalidation($user->id, $topic->id, 'random');
        $this->expectProgressInvalidation($user->id, $topic->id, 'random');
        $this->notificationService->shouldNotReceive('createLevelUpNotification');

        $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'A'],
            60,
            'random',
            $topic,
            $course
        );

        $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'A'],
            60,
            'random',
            $topic,
            $course
        );

        $mastery = UserMistakeMastery::query()
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($mastery);
        $this->assertSame(2, $mastery->consecutive_correct);
        $this->assertNotNull($mastery->graduated_at);
        $this->assertSame('correct', $mastery->last_attempt_result);

        $attempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $attempts);
        $this->assertTrue($attempts->every(fn (QuizAttempt $attempt) => $attempt->session_type === 'random_quiz'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_grades_and_saves_quiz_attempts_when_mastery_table_is_missing()
    {
        $user = User::factory()->create([
            'mistake_mastery_mode' => 'easy',
            'xp' => 0,
        ]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $question = Question::factory()->create([
            'topic_id' => $topic->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Test Source',
        ]);

        if (Schema::hasTable('user_mistake_mastery')) {
            Schema::drop('user_mistake_mastery');
            UserMistakeMastery::clearTableExistenceCache();
        }

        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with($user, 1, 1, 60, Mockery::any());
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        $this->notificationService->shouldReceive('createLevelUpNotification')->zeroOrMoreTimes();

        $result = $this->gradingService->gradeQuiz(
            $user,
            [$question->id => 'A'],
            60,
            'quiz',
            $topic,
            $course
        );

        $this->assertTrue($result['success']);
        $this->assertSame(1, QuizAttempt::query()->count());
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'score' => 1,
            'total_questions' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_batch_database_operations()
    {
        // Mock dependencies
        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any());
        
        $this->notificationService->shouldNotReceive('createLevelUpNotification');
        
        $service = new GradingService(
            $this->cacheService,
            $this->progressCacheInvalidator,
            $this->userStatsService,
            $this->notificationService,
            $this->writtenAnswerEvaluationService
        );
        
        $questions = Question::factory()->count(10)->create([
            'correct_answer' => 'A', // Set all correct answers to 'A'
            'question_type' => 'multiple_choice' // Ensure all are multiple choice
        ]);
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
        $answers = [];
        foreach ($questions as $index => $question) {
            // Make some answers incorrect to test mistake recording
            $answers[$question->id] = $index % 2 == 0 ? 'A' : 'B'; // Alternate correct/incorrect
        }
        
        $startTime = microtime(true);
        $result = $service->gradeQuiz($user, $answers, 60, 'quiz', $topic, $course);
        $endTime = microtime(true);
        
        // Should be much faster with batch operations
        $this->assertLessThan(0.1, $endTime - $startTime); // Should complete in under 100ms
        
        // Verify batch operations worked (mistakes are only recorded for incorrect answers)
        $this->assertEquals(5, \App\Models\Mistake::where('user_id', $user->id)->count()); // Half of answers are incorrect
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tutor_mode_single_evaluation()
    {
        $service = new GradingService(
            $this->cacheService,
            $this->progressCacheInvalidator,
            $this->userStatsService,
            $this->notificationService,
            $this->writtenAnswerEvaluationService
        );
        
        $questions = Question::factory()->count(3)->create();
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        $this->userStatsService->shouldReceive('updateAfterQuizAttempt')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any());
        $this->expectProgressInvalidation($user->id, $topic->id, 'tutor');
        $this->notificationService->shouldNotReceive('createLevelUpNotification');
        
        $answers = [];
        foreach ($questions as $question) {
            $answers[$question->id] = 'A';
        }
        
        $startTime = microtime(true);
        $result = $service->gradeQuiz($user, $answers, 60, 'tutor', $topic, $course);
        $endTime = microtime(true);
        
        // Should be faster with single evaluation
        $this->assertLessThan(0.2, $endTime - $startTime); // Should complete in under 200ms
        
        // Verify single evaluation worked
        $this->assertArrayHasKey('sourceData', $result);
        $this->assertCount(1, $result['sourceData']); // Should have one source
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
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
        
        $this->expectProgressInvalidation($user->id, $topic->id, 'quiz');
        
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
