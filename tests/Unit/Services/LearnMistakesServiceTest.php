<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Learning\MistakeManagementService;
use App\Models\SkippedQuestion;
use App\Models\Question;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LearnMistakesServiceTest extends TestCase
{
    use RefreshDatabase;

    private $service;
    private $user;
    private $topic;
    private $questions;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MistakeManagementService();
        $this->user = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $course->id]);
        $this->questions = Question::factory()->count(5)->create(['topic_id' => $this->topic->id]);
    }

    /** @test */
    public function it_can_skip_a_question()
    {
        $question = $this->questions->first();
        
        $result = $this->service->skipQuestion($this->user->id, $question->id);
        
        $this->assertTrue($result);
        $this->assertTrue(SkippedQuestion::isSkipped($this->user->id, $question->id));
    }

    /** @test */
    public function it_can_remove_skip_from_question()
    {
        $question = $this->questions->first();
        
        // First skip the question
        $this->service->skipQuestion($this->user->id, $question->id);
        $this->assertTrue(SkippedQuestion::isSkipped($this->user->id, $question->id));
        
        // Then remove the skip
        $result = $this->service->removeSkip($this->user->id, $question->id);
        
        $this->assertTrue($result);
        $this->assertFalse(SkippedQuestion::isSkipped($this->user->id, $question->id));
    }

    /** @test */
    public function it_can_get_skipped_questions()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $question2->id);
        
        $skippedQuestions = $this->service->getSkippedQuestions($this->user->id);
        
        $this->assertCount(2, $skippedQuestions);
        $this->assertTrue($skippedQuestions->pluck('question_id')->contains($question1->id));
        $this->assertTrue($skippedQuestions->pluck('question_id')->contains($question2->id));
    }

    /** @test */
    public function it_can_get_skipped_questions_for_topic()
    {
        $otherTopic = Topic::factory()->create(['course_id' => $this->topic->course_id]);
        $otherQuestion = Question::factory()->create(['topic_id' => $otherTopic->id]);
        
        $question1 = $this->questions[0];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $otherQuestion->id);
        
        $skippedQuestions = $this->service->getSkippedQuestionsForTopic($this->user->id, $this->topic->id);
        
        $this->assertCount(1, $skippedQuestions);
        $this->assertEquals($question1->id, $skippedQuestions->first()->question_id);
    }

    /** @test */
    public function it_can_get_questions_for_session_excluding_skipped()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        // Skip one question
        $this->service->skipQuestion($this->user->id, $question1->id);
        
        $sessionQuestions = $this->service->getQuestionsForSession($this->user->id, $this->topic->id, true);
        
        $this->assertCount(4, $sessionQuestions); // 5 total - 1 skipped = 4
        $this->assertFalse($sessionQuestions->pluck('id')->contains($question1->id));
        $this->assertTrue($sessionQuestions->pluck('id')->contains($question2->id));
    }

    /** @test */
    public function it_can_get_questions_for_session_including_skipped()
    {
        $question1 = $this->questions[0];
        
        // Skip one question
        $this->service->skipQuestion($this->user->id, $question1->id);
        
        $sessionQuestions = $this->service->getQuestionsForSession($this->user->id, $this->topic->id, false);
        
        $this->assertCount(5, $sessionQuestions); // All questions included
        $this->assertTrue($sessionQuestions->pluck('id')->contains($question1->id));
    }

    /** @test */
    public function it_can_get_all_questions_for_topic_with_skip_info()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        
        $result = $this->service->getAllQuestionsForTopic($this->user->id, $this->topic->id);
        
        $this->assertArrayHasKey('all_questions', $result);
        $this->assertArrayHasKey('skipped_question_ids', $result);
        $this->assertArrayHasKey('available_questions', $result);
        $this->assertArrayHasKey('skipped_questions', $result);
        
        $this->assertCount(5, $result['all_questions']);
        $this->assertCount(1, $result['skipped_question_ids']);
        $this->assertCount(4, $result['available_questions']);
        $this->assertCount(1, $result['skipped_questions']);
        
        $this->assertTrue(in_array($question1->id, $result['skipped_question_ids']));
        $this->assertFalse(in_array($question2->id, $result['skipped_question_ids']));
    }

    /** @test */
    public function it_can_check_if_question_is_skipped()
    {
        $question = $this->questions->first();
        
        $this->assertFalse($this->service->isQuestionSkipped($this->user->id, $question->id));
        
        $this->service->skipQuestion($this->user->id, $question->id);
        
        $this->assertTrue($this->service->isQuestionSkipped($this->user->id, $question->id));
    }

    /** @test */
    public function it_can_clear_all_skips()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $question2->id);
        
        $this->assertEquals(2, SkippedQuestion::where('user_id', $this->user->id)->count());
        
        $cleared = $this->service->clearAllSkips($this->user->id);
        
        $this->assertEquals(2, $cleared);
        $this->assertEquals(0, SkippedQuestion::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_can_clear_topic_skips()
    {
        $otherTopic = Topic::factory()->create(['course_id' => $this->topic->course_id]);
        $otherQuestion = Question::factory()->create(['topic_id' => $otherTopic->id]);
        
        $question1 = $this->questions[0];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $otherQuestion->id);
        
        $this->assertEquals(2, SkippedQuestion::where('user_id', $this->user->id)->count());
        
        $cleared = $this->service->clearTopicSkips($this->user->id, $this->topic->id);
        
        $this->assertEquals(1, $cleared);
        $this->assertEquals(1, SkippedQuestion::where('user_id', $this->user->id)->count());
        $this->assertFalse($this->service->isQuestionSkipped($this->user->id, $question1->id));
        $this->assertTrue($this->service->isQuestionSkipped($this->user->id, $otherQuestion->id));
    }

    /** @test */
    public function it_can_get_skip_statistics()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $question2->id);
        
        $stats = $this->service->getSkipStatistics($this->user->id);
        
        $this->assertArrayHasKey('total_skips', $stats);
        $this->assertArrayHasKey('recent_skips', $stats);
        $this->assertArrayHasKey('skips_by_session', $stats);
        $this->assertArrayHasKey('most_skipped_topics', $stats);
        $this->assertArrayHasKey('skip_frequency', $stats);
        
        $this->assertEquals(2, $stats['total_skips']);
        $this->assertEquals(2, $stats['recent_skips']);
    }

    /** @test */
    public function it_can_get_questions_with_skip_status()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        
        $questions = $this->service->getQuestionsWithSkipStatus($this->user->id, $this->topic->id);
        
        $this->assertCount(5, $questions);
        
        $skippedQuestion = $questions->where('id', $question1->id)->first();
        $normalQuestion = $questions->where('id', $question2->id)->first();
        
        $this->assertTrue($skippedQuestion->is_skipped);
        $this->assertFalse($normalQuestion->is_skipped);
    }

    /** @test */
    public function it_can_get_session_progress()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $question2->id);
        
        $progress = $this->service->getSessionProgress($this->user->id, $this->topic->id);
        
        $this->assertArrayHasKey('total_questions', $progress);
        $this->assertArrayHasKey('skipped_questions', $progress);
        $this->assertArrayHasKey('available_questions', $progress);
        $this->assertArrayHasKey('skip_percentage', $progress);
        
        $this->assertEquals(5, $progress['total_questions']);
        $this->assertEquals(2, $progress['skipped_questions']);
        $this->assertEquals(3, $progress['available_questions']);
        $this->assertEquals(40.0, $progress['skip_percentage']);
    }

    /** @test */
    public function it_can_reset_session()
    {
        $question1 = $this->questions[0];
        $question2 = $this->questions[1];
        
        $this->service->skipQuestion($this->user->id, $question1->id);
        $this->service->skipQuestion($this->user->id, $question2->id);
        
        $this->assertEquals(2, SkippedQuestion::where('user_id', $this->user->id)->count());
        
        $result = $this->service->resetSession($this->user->id, $this->topic->id);
        
        $this->assertTrue($result);
        $this->assertEquals(0, SkippedQuestion::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_can_get_recommended_questions()
    {
        $question1 = $this->questions[0];
        
        // Skip one question recently
        $this->service->skipQuestion($this->user->id, $question1->id);
        
        $recommended = $this->service->getRecommendedQuestions($this->user->id, $this->topic->id, 3);
        
        $this->assertCount(3, $recommended);
        $this->assertFalse($recommended->pluck('id')->contains($question1->id));
    }

    /** @test */
    public function it_handles_empty_topic_gracefully()
    {
        $emptyTopic = Topic::factory()->create(['course_id' => $this->topic->course_id]);
        
        $questions = $this->service->getQuestionsForSession($this->user->id, $emptyTopic->id);
        $progress = $this->service->getSessionProgress($this->user->id, $emptyTopic->id);
        
        $this->assertCount(0, $questions);
        $this->assertEquals(0, $progress['total_questions']);
        $this->assertEquals(0, $progress['skip_percentage']);
    }

    /** @test */
    public function it_handles_different_session_types()
    {
        $question = $this->questions->first();
        
        $this->service->skipQuestion($this->user->id, $question->id, SkippedQuestion::SESSION_LEARN_MISTAKES);
        $this->service->skipQuestion($this->user->id, $question->id, SkippedQuestion::SESSION_QUIZ);
        
        $learnMistakesSkips = $this->service->getSkippedQuestions($this->user->id, SkippedQuestion::SESSION_LEARN_MISTAKES);
        $quizSkips = $this->service->getSkippedQuestions($this->user->id, SkippedQuestion::SESSION_QUIZ);
        
        $this->assertCount(1, $learnMistakesSkips);
        $this->assertCount(1, $quizSkips);
        
        $this->assertTrue($this->service->isQuestionSkipped($this->user->id, $question->id, SkippedQuestion::SESSION_LEARN_MISTAKES));
        $this->assertTrue($this->service->isQuestionSkipped($this->user->id, $question->id, SkippedQuestion::SESSION_QUIZ));
    }
}