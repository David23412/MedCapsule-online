<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\SkippedQuestion;
use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SkippedQuestionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $question;
    private $topic;

    protected function setUp(): void
    {
        parent::setUp();
        
        $course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $course->id]);
        $this->user = User::factory()->create(['role' => 'student']);
        $this->question = Question::factory()->create(['topic_id' => $this->topic->id]);
    }

    /** @test */
    public function it_can_create_a_skipped_question()
    {
        $skippedQuestion = SkippedQuestion::create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'session_type' => SkippedQuestion::SESSION_LEARN_MISTAKES,
            'skipped_at' => now()
        ]);

        $this->assertInstanceOf(SkippedQuestion::class, $skippedQuestion);
        $this->assertEquals($this->user->id, $skippedQuestion->user_id);
        $this->assertEquals($this->question->id, $skippedQuestion->question_id);
    }

    /** @test */
    public function it_has_proper_relationships()
    {
        $skippedQuestion = SkippedQuestion::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id
        ]);

        $this->assertEquals($this->user->id, $skippedQuestion->user->id);
        $this->assertEquals($this->question->id, $skippedQuestion->question->id);
    }

    /** @test */
    public function it_can_mark_question_as_skipped()
    {
        $result = SkippedQuestion::markAsSkipped(
            $this->user->id,
            $this->question->id,
            SkippedQuestion::SESSION_QUIZ
        );

        $this->assertTrue($result);
        $this->assertTrue(
            SkippedQuestion::isSkipped(
                $this->user->id,
                $this->question->id,
                SkippedQuestion::SESSION_QUIZ
            )
        );
    }

    /** @test */
    public function it_can_check_if_question_is_skipped()
    {
        SkippedQuestion::markAsSkipped(
            $this->user->id,
            $this->question->id,
            SkippedQuestion::SESSION_LEARN_MISTAKES
        );

        $this->assertTrue(
            SkippedQuestion::isSkipped(
                $this->user->id,
                $this->question->id,
                SkippedQuestion::SESSION_LEARN_MISTAKES
            )
        );

        $this->assertFalse(
            SkippedQuestion::isSkipped(
                $this->user->id,
                $this->question->id,
                SkippedQuestion::SESSION_QUIZ
            )
        );
    }

    /** @test */
    public function it_can_get_skipped_questions_for_user()
    {
        $question2 = Question::factory()->create(['topic_id' => $this->topic->id]);
        
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id);
        SkippedQuestion::markAsSkipped($this->user->id, $question2->id);

        $skippedQuestions = SkippedQuestion::getSkippedQuestions($this->user->id);

        $this->assertEquals(2, $skippedQuestions->count());
    }

    /** @test */
    public function it_can_get_skipped_questions_for_topic()
    {
        $anotherTopic = Topic::factory()->create(['course_id' => $this->topic->course_id]);
        $questionInAnotherTopic = Question::factory()->create(['topic_id' => $anotherTopic->id]);
        
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id);
        SkippedQuestion::markAsSkipped($this->user->id, $questionInAnotherTopic->id);

        $skippedInTopic = SkippedQuestion::getSkippedQuestionsForTopic(
            $this->user->id,
            $this->topic->id
        );

        $this->assertEquals(1, $skippedInTopic->count());
        $this->assertEquals($this->question->id, $skippedInTopic->first()->question_id);
    }

    /** @test */
    public function it_can_remove_skip()
    {
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id);
        
        $this->assertTrue(
            SkippedQuestion::isSkipped($this->user->id, $this->question->id)
        );

        $result = SkippedQuestion::removeSkip($this->user->id, $this->question->id);
        
        $this->assertTrue($result);
        $this->assertFalse(
            SkippedQuestion::isSkipped($this->user->id, $this->question->id)
        );
    }

    /** @test */
    public function it_can_clear_all_skips_for_user()
    {
        $question2 = Question::factory()->create(['topic_id' => $this->topic->id]);
        
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id);
        SkippedQuestion::markAsSkipped($this->user->id, $question2->id);

        $cleared = SkippedQuestion::clearAllSkips($this->user->id);

        $this->assertEquals(2, $cleared);
        $this->assertEquals(0, SkippedQuestion::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_can_clear_topic_skips()
    {
        $anotherTopic = Topic::factory()->create(['course_id' => $this->topic->course_id]);
        $questionInAnotherTopic = Question::factory()->create(['topic_id' => $anotherTopic->id]);
        
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id);
        SkippedQuestion::markAsSkipped($this->user->id, $questionInAnotherTopic->id);

        $cleared = SkippedQuestion::clearTopicSkips($this->user->id, $this->topic->id);

        $this->assertEquals(1, $cleared);
        $this->assertFalse(
            SkippedQuestion::isSkipped($this->user->id, $this->question->id)
        );
        $this->assertTrue(
            SkippedQuestion::isSkipped($this->user->id, $questionInAnotherTopic->id)
        );
    }

    /** @test */
    public function it_has_proper_query_scopes()
    {
        $user2 = User::factory()->create(['role' => 'student']);
        
        SkippedQuestion::factory()->create([
            'user_id' => $this->user->id,
            'session_type' => SkippedQuestion::SESSION_QUIZ
        ]);
        
        SkippedQuestion::factory()->create([
            'user_id' => $user2->id,
            'session_type' => SkippedQuestion::SESSION_LEARN_MISTAKES
        ]);

        $this->assertEquals(1, SkippedQuestion::forUser($this->user->id)->count());
        $this->assertEquals(1, SkippedQuestion::forSession(SkippedQuestion::SESSION_QUIZ)->count());
    }

    /** @test */
    public function it_can_get_skip_statistics()
    {
        $question2 = Question::factory()->create(['topic_id' => $this->topic->id]);
        
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id, SkippedQuestion::SESSION_QUIZ);
        SkippedQuestion::markAsSkipped($this->user->id, $question2->id, SkippedQuestion::SESSION_LEARN_MISTAKES);

        $stats = SkippedQuestion::getSkipStatistics($this->user->id);

        $this->assertEquals(2, $stats['total_skips']);
        $this->assertEquals(1, $stats['skips_by_session'][SkippedQuestion::SESSION_QUIZ]);
        $this->assertEquals(1, $stats['skips_by_session'][SkippedQuestion::SESSION_LEARN_MISTAKES]);
        $this->assertIsArray($stats['most_skipped_topics']);
        $this->assertIsArray($stats['skip_frequency']);
    }

    /** @test */
    public function it_can_get_most_skipped_topics()
    {
        $question2 = Question::factory()->create(['topic_id' => $this->topic->id]);
        
        SkippedQuestion::markAsSkipped($this->user->id, $this->question->id);
        SkippedQuestion::markAsSkipped($this->user->id, $question2->id);

        $mostSkipped = SkippedQuestion::getMostSkippedTopics($this->user->id, 5);

        $this->assertCount(1, $mostSkipped);
        $this->assertEquals($this->topic->id, $mostSkipped[0]['topic_id']);
        $this->assertEquals(2, $mostSkipped[0]['skip_count']);
    }

    /** @test */
    public function it_validates_session_types()
    {
        $validTypes = SkippedQuestion::getValidSessionTypes();
        
        $this->assertContains(SkippedQuestion::SESSION_LEARN_MISTAKES, $validTypes);
        $this->assertContains(SkippedQuestion::SESSION_QUIZ, $validTypes);
        $this->assertContains(SkippedQuestion::SESSION_REVIEW, $validTypes);

        $this->assertTrue(SkippedQuestion::isValidSessionType(SkippedQuestion::SESSION_QUIZ));
        $this->assertFalse(SkippedQuestion::isValidSessionType('invalid_type'));
    }

    /** @test */
    public function it_can_cleanup_old_skips()
    {
        // Create old skip
        SkippedQuestion::factory()->create([
            'user_id' => $this->user->id,
            'skipped_at' => now()->subDays(35)
        ]);

        // Create recent skip
        SkippedQuestion::factory()->create([
            'user_id' => $this->user->id,
            'skipped_at' => now()->subDays(5)
        ]);

        $cleaned = SkippedQuestion::cleanupOldSkips(30);

        $this->assertEquals(1, $cleaned);
        $this->assertEquals(1, SkippedQuestion::count());
    }
}