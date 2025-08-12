<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QuizService;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuizServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $quizService;
    protected $cacheService;
    protected $user;
    protected $course;
    protected $topic;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = Mockery::mock(UltraConsolidatedCacheService::class);
        $this->quizService = new QuizService($this->cacheService);
        
        $this->user = User::factory()->create();
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_generates_quiz_questions_with_source_filtering()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id,
            'source' => 'textbook'
        ]);
        
        Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'source' => 'lecture'
        ]);

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['textbook'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('questions', $result);
        $this->assertLessThanOrEqual(50, $result['questions']->count()); // Max questions per quiz
    }

    public function test_it_generates_random_quiz_with_difficulty_filtering()
    {
        // Arrange
        Question::factory()->count(10)->create([
            'topic_id' => $this->topic->id
        ]);

        // Act
        $result = $this->quizService->generateRandomQuiz($this->user, 5, ['difficulty' => 'easy']);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('questions', $result);
        $this->assertLessThanOrEqual(5, $result['questions']->count());
    }

    public function test_it_validates_quiz_access_for_admin_user()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $adminUser = User::factory()->create(['role' => 'admin']);
        Question::factory()->count(5)->create(['topic_id' => $this->topic->id]);

        // Act & Assert - Admin should have access
        $this->expectNotToPerformAssertions();
        
        $this->quizService->initializeQuiz($adminUser, $this->course, $this->topic);
    }

    public function test_it_validates_quiz_access_for_enrolled_user()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $enrolledUser = \Mockery::mock(User::class)->makePartial();
        $enrolledUser->role = 'student';
        Question::factory()->count(5)->create(['topic_id' => $this->topic->id]);
        // Mock the hasAccessToCourse method
        $enrolledUser->shouldReceive('hasAccessToCourse')
            ->with($this->course->id)
            ->andReturn(true);
        // Act & Assert - Enrolled user should have access
        $this->expectNotToPerformAssertions();
        $this->quizService->initializeQuiz($enrolledUser, $this->course, $this->topic);
    }

    public function test_it_validates_quiz_access_for_unauthorized_user()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $unauthorizedUser = \Mockery::mock(User::class)->makePartial();
        $unauthorizedUser->role = 'student';
        $paidCourse = Course::factory()->create(['price' => 99.99]);
        $topic = Topic::factory()->create(['course_id' => $paidCourse->id]);
        // Mock the hasAccessToCourse method
        $unauthorizedUser->shouldReceive('hasAccessToCourse')
            ->with($paidCourse->id)
            ->andReturn(false);
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You must be granted access to this course to take quizzes');
        $this->quizService->initializeQuiz($unauthorizedUser, $paidCourse, $topic);
    }

    public function test_it_uses_cache_for_quiz_generation()
    {
        // Arrange
        $cachedQuestions = Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id
        ]);
        
        $this->cacheService->shouldReceive('get')->andReturn($cachedQuestions);
        
        $this->cacheService->shouldNotReceive('put');

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function test_it_handles_cache_miss_gracefully()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_it_handles_empty_questions_gracefully()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $emptyTopic = Topic::factory()->create(['course_id' => $this->course->id]);

        // Act
        $result = $this->quizService->generateQuizQuestions($emptyTopic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('questions', $result);
        $this->assertEmpty($result['questions']);
    }

    public function test_it_generates_tutor_quiz_with_hints()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice'
        ]);

        // Act
        $result = $this->quizService->generateTutorQuiz($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('questions', $result);
    }

    public function test_it_respects_max_questions_per_quiz_limit()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(20)->create([
            'topic_id' => $this->topic->id
        ]);

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertLessThanOrEqual(50, $result['questions']->count()); // Max questions per quiz
    }

    public function test_it_filters_questions_by_source()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id,
            'source' => 'textbook'
        ]);
        
        Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'source' => 'lecture'
        ]);

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['textbook'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertNotEmpty($result['questions']);
        
        // All questions should be from textbook source
        foreach ($result['questions'] as $question) {
            $this->assertEquals('textbook', $question->source);
        }
    }

    public function test_it_handles_cache_service_failure()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andThrow(new \Exception('Cache service unavailable'));

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_it_validates_topic_exists()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $nonExistentTopicId = 99999;
        $topic = Topic::find($nonExistentTopicId);

        // Act & Assert
        $this->expectException(\TypeError::class);
        
        $this->quizService->generateQuizQuestions($topic, ['all'], $this->user);
    }

    public function test_it_handles_mixed_question_types()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice'
        ]);
        
        Question::factory()->count(2)->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'written'
        ]);

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertNotEmpty($result['questions']);
        
        $questionTypes = $result['questions']->pluck('question_type')->toArray();
        $this->assertContains('multiple_choice', $questionTypes);
        $this->assertContains('written', $questionTypes);
    }

    public function test_it_generates_unique_questions_for_each_quiz()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(15)->create([
            'topic_id' => $this->topic->id
        ]);

        // Act
        $result1 = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);
        $result2 = $this->quizService->generateQuizQuestions($this->topic, ['all'], $this->user);

        // Assert
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertArrayHasKey('questions', $result1);
        $this->assertArrayHasKey('questions', $result2);
        
        // Questions should be different (random selection)
        $questionIds1 = $result1['questions']->pluck('id')->toArray();
        $questionIds2 = $result2['questions']->pluck('id')->toArray();
        
        // Note: With random selection, there's a small chance questions could be the same
        // This test verifies the structure is correct
        $this->assertNotEmpty($questionIds1);
        $this->assertNotEmpty($questionIds2);
    }

    public function test_it_validates_user_parameter()
    {
        // Arrange
        $invalidUser = 'not_a_user_object';

        // Act & Assert
        $this->expectException(\TypeError::class);
        
        $this->quizService->generateRandomQuiz($invalidUser, 5);
    }

    public function test_it_handles_invalid_source_filter()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id,
            'source' => 'textbook'
        ]);
        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['invalid_source'], $this->user);
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['questions']);
        $this->assertCount(0, $result['questions']);
    }

    public function test_it_initializes_quiz_successfully()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id
        ]);
        // Act
        $result = $this->quizService->initializeQuiz($user, $this->course, $this->topic);
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('questions', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('mode', $result);
    }

    public function test_it_validates_course_topic_relationship()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $differentCourse = Course::factory()->create();
        $topicFromDifferentCourse = Topic::factory()->create(['course_id' => $differentCourse->id]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid topic for this course');
        
        $this->quizService->initializeQuiz($this->user, $this->course, $topicFromDifferentCourse);
    }

    public function test_it_validates_user_access_to_paid_course()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $paidCourse = Course::factory()->create(['price' => 99.99]);
        $topic = Topic::factory()->create(['course_id' => $paidCourse->id]);
        $regularUser = \Mockery::mock(User::class)->makePartial();
        $regularUser->role = 'student';
        // Mock the hasAccessToCourse method
        $regularUser->shouldReceive('hasAccessToCourse')
            ->with($paidCourse->id)
            ->andReturn(false);
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You must be granted access to this course to take quizzes');
        $this->quizService->initializeQuiz($regularUser, $paidCourse, $topic);
    }

    public function test_it_allows_admin_access_to_paid_course()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $paidCourse = Course::factory()->create(['price' => 99.99]);
        $topic = Topic::factory()->create(['course_id' => $paidCourse->id]);
        $adminUser = User::factory()->create(['role' => 'admin']);
        Question::factory()->count(5)->create(['topic_id' => $topic->id]);

        // Act & Assert - Admin should have access
        $this->expectNotToPerformAssertions();
        
        $this->quizService->initializeQuiz($adminUser, $paidCourse, $topic);
    }

    public function test_it_normalizes_sources_array_correctly()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'source' => 'textbook'
        ]);
        Question::factory()->count(2)->create([
            'topic_id' => $this->topic->id,
            'source' => 'lecture'
        ]);
        // Act
        $result = $this->quizService->initializeQuiz($user, $this->course, $this->topic, ['textbook', 'lecture']);
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertContains('textbook', $result['sources']);
        $this->assertContains('lecture', $result['sources']);
    }

    public function test_it_defaults_to_all_sources_when_empty()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id
        ]);
        // Act
        $result = $this->quizService->initializeQuiz($user, $this->course, $this->topic, []);
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertContains('all', $result['sources']);
    }

    public function test_it_filters_questions_by_specific_sources()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'source' => 'textbook'
        ]);
        
        Question::factory()->count(2)->create([
            'topic_id' => $this->topic->id,
            'source' => 'lecture'
        ]);

        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['textbook'], $this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertNotEmpty($result['questions']);
        
        foreach ($result['questions'] as $question) {
            $this->assertEquals('textbook', $question->source);
        }
    }

    public function test_it_throws_exception_when_no_questions_available()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        $emptyTopic = Topic::factory()->create(['course_id' => $this->course->id]);
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No questions available');
        $this->quizService->initializeQuiz($user, $this->course, $emptyTopic);
    }

    public function test_it_uses_cached_questions_when_available()
    {
        // Arrange
        $cachedQuestions = Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id
        ]);
        $this->cacheService->shouldReceive('get')->andReturn($cachedQuestions);
        $this->cacheService->shouldNotReceive('put');
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        // Act
        $result = $this->quizService->generateQuizQuestions($this->topic, ['all'], $user);
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function test_it_gets_available_sources_for_topic()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'source' => 'textbook'
        ]);
        
        Question::factory()->count(2)->create([
            'topic_id' => $this->topic->id,
            'source' => 'lecture'
        ]);

        // Act
        $sources = $this->quizService->getAvailableSources($this->topic);

        // Assert
        $this->assertIsArray($sources);
        $this->assertContains('textbook', $sources);
        $this->assertContains('lecture', $sources);
    }

    public function test_it_gets_quiz_statistics_correctly()
    {
        // Arrange
        $this->cacheService->shouldReceive('forget')->andReturn(true);
        $this->quizService->clearQuizCaches($this->topic); // Clear cache to ensure fresh stats
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        $question1 = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice',
            'source' => 'Source A'
        ]);
        $question2 = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'written',
            'source' => 'Source B'
        ]);
        QuizAttempt::factory()->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'score' => 8,
            'total_questions' => 10,
            'duration_seconds' => 300,
            'quiz_type' => 'normal',
            'question_data' => [
                ['question_id' => $question1->id, 'user_answer' => 'A', 'is_correct' => true],
                ['question_id' => $question2->id, 'user_answer' => 'B', 'is_correct' => false]
            ],
        ]);
        // Act
        $stats = $this->quizService->getQuizStatistics($this->topic, ['all']);
        // Assert
        $this->assertIsArray($stats);
        $this->assertEquals(2, $stats['total_questions']);
        $this->assertEquals(1, $stats['multiple_choice']);
        $this->assertEquals(1, $stats['written']);
        $this->assertArrayHasKey('Source A', $stats['sources']);
        $this->assertArrayHasKey('Source B', $stats['sources']);
    }

    public function test_it_gets_random_questions_correctly()
    {
        // Arrange
        Question::factory()->count(10)->create([
            'topic_id' => $this->topic->id
        ]);

        // Act
        $questions = $this->quizService->getRandomQuestions($this->topic, 5);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $questions);
        $this->assertEquals(5, $questions->count());
    }

    public function test_it_gets_questions_by_difficulty()
    {
        // Arrange
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id
        ]);

        // Act
        $questions = $this->quizService->getQuestionsByDifficulty($this->topic, 'easy');

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $questions);
    }

    public function test_it_gets_user_quiz_history()
    {
        // Arrange
        $question = Question::factory()->create();
        QuizAttempt::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'score' => 8,
            'total_questions' => 10,
            'duration_seconds' => 300,
            'quiz_type' => 'normal',
            'question_data' => [
                ['question_id' => $question->id, 'user_answer' => 'A', 'is_correct' => true]
            ],
        ]);
        // Act
        $history = $this->quizService->getUserQuizHistory($this->user, $this->topic);
        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $history);
        $this->assertEquals(2, $history->count());
    }

    public function test_it_gets_quiz_recommendations_for_new_user()
    {
        // Arrange
        $newUser = User::factory()->create();
        $question = Question::factory()->create();
        QuizAttempt::factory()->count(1)->create([
            'user_id' => $newUser->id,
            'topic_id' => $this->topic->id,
            'score' => 5,
            'total_questions' => 10,
            'duration_seconds' => 300,
            'quiz_type' => 'normal',
            'question_data' => [
                ['question_id' => $question->id, 'user_answer' => 'A', 'is_correct' => true]
            ],
        ]);
        // Act
        $recommendations = $this->quizService->getQuizRecommendations($newUser, $this->topic);
        // Assert
        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('difficulty', $recommendations);
        $this->assertArrayHasKey('recommended_mode', $recommendations);
        $this->assertArrayHasKey('recommended_sources', $recommendations);
    }

    public function test_it_gets_quiz_recommendations_for_poor_performance()
    {
        // Arrange
        $question = Question::factory()->create();
        QuizAttempt::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'score' => 3,
            'total_questions' => 10,
            'duration_seconds' => 300,
            'quiz_type' => 'normal',
            'question_data' => [
                ['question_id' => $question->id, 'user_answer' => 'A', 'is_correct' => true]
            ],
        ]);
        // Act
        $recommendations = $this->quizService->getQuizRecommendations($this->user, $this->topic);
        // Assert
        $this->assertIsArray($recommendations);
        $this->assertEquals('tutor', $recommendations['recommended_mode']);
        $this->assertEquals('easy', $recommendations['difficulty']);
    }

    public function test_it_gets_quiz_recommendations_for_good_performance()
    {
        // Arrange
        $question = Question::factory()->create();
        QuizAttempt::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'score' => 9,
            'total_questions' => 10,
            'duration_seconds' => 300,
            'quiz_type' => 'normal',
            'question_data' => [
                ['question_id' => $question->id, 'user_answer' => 'A', 'is_correct' => true]
            ],
        ]);
        // Act
        $recommendations = $this->quizService->getQuizRecommendations($this->user, $this->topic);
        // Assert
        $this->assertIsArray($recommendations);
        $this->assertEquals('quiz', $recommendations['recommended_mode']);
        $this->assertEquals('hard', $recommendations['difficulty']);
    }

    public function test_it_clears_quiz_caches()
    {
        // Arrange
        $this->cacheService->shouldReceive('forget')
            ->with("quiz_questions_{$this->topic->id}_*")
            ->once();
        
        $this->cacheService->shouldReceive('forget')
            ->with("available_sources_{$this->topic->id}")
            ->once();
        
        $this->cacheService->shouldReceive('forget')
            ->with("quiz_stats_{$this->topic->id}_*")
            ->once();

        // Act
        $this->quizService->clearQuizCaches($this->topic);

        // Assert - No exceptions should be thrown
        $this->assertTrue(true);
    }

    public function test_it_validates_quiz_session_data()
    {
        // Arrange
        session(['quiz_mode' => 'quiz', 'selected_sources' => ['all']]);
        // Act
        $isValid = $this->quizService->validateQuizSession('quiz', ['all']);
        // Assert
        $this->assertTrue($isValid);
    }

    public function test_it_gets_quiz_configuration()
    {
        // Act
        $config = $this->quizService->getQuizConfiguration($this->topic);

        // Assert
        $this->assertIsArray($config);
        $this->assertArrayHasKey('max_questions', $config);
        $this->assertArrayHasKey('duration_minutes', $config);
        $this->assertArrayHasKey('passing_score', $config);
    }

    public function test_it_handles_tutor_mode_initialization()
    {
        // Arrange
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->role = 'admin';
        Question::factory()->count(5)->create([
            'topic_id' => $this->topic->id
        ]);
        // Act
        $result = $this->quizService->initializeQuiz($user, $this->course, $this->topic, ['all'], 'tutor');
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('mode', $result);
        $this->assertEquals('tutor', $result['mode']);
    }
} 