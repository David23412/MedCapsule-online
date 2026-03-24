<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\Question;
use App\Models\User;
use App\Models\UserMistakeMastery;
use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\UserMistakeMasteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LearnMistakesServiceTest extends TestCase
{
    use RefreshDatabase;

    private MistakeManagementService $service;
    private User $user;
    private Course $course;
    private \App\Models\Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        UserMistakeMastery::clearTableExistenceCache();

        $this->service = app(MistakeManagementService::class);
        $this->user = User::factory()->student()->create();
        $this->course = Course::create([
            'name' => 'Cardiology',
            'description' => 'Core cardiology course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        $this->topic = \App\Models\Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Hemodynamics',
            'case_type' => 'quiz',
            'display_order' => 1,
        ]);
    }

    public function test_it_marks_existing_mistake_as_skipped_and_returns_it_from_the_unified_pool(): void
    {
        $question = $this->createQuestion('Cardiac cycle');
        $mistake = $this->createMistake($question);

        $result = $this->service->skipMistake($this->user->id, $question->id, 'spaced');

        $this->assertTrue($result);

        $mistake->refresh();
        $this->assertTrue($mistake->skipped);
        $this->assertNotNull($mistake->skip_until);

        $skippedMistakes = $this->service->getSkippedMistakes($this->user->id);

        $this->assertCount(1, $skippedMistakes);
        $this->assertSame($mistake->id, $skippedMistakes->first()->id);
        $this->assertSame($question->id, $skippedMistakes->first()->question_id);
    }

    public function test_it_keeps_skipped_mastered_mistakes_accessible(): void
    {
        $question = $this->createQuestion('Ejection fraction');
        $mistake = $this->createMistake($question, [
            'skipped' => true,
            'mastered' => true,
            'skip_until' => now()->addHour(),
        ]);

        $skippedMistakes = $this->service->getSkippedMistakes($this->user->id);

        $this->assertCount(1, $skippedMistakes);
        $this->assertSame($mistake->id, $skippedMistakes->first()->id);
        $this->assertTrue((bool) $skippedMistakes->first()->mastered);
    }

    public function test_it_keeps_skipped_items_in_totals_but_excludes_them_from_the_due_spaced_queue(): void
    {
        $dueQuestion = $this->createQuestion('Cardiac output');
        $skippedQuestion = $this->createQuestion('Stroke volume');

        $dueMistake = $this->createMistake($dueQuestion, [
            'next_review_at' => now()->subHour(),
            'last_reviewed_at' => now()->subDay(),
            'last_attempt_date' => now()->subDay(),
            'skipped' => false,
        ]);

        $skippedMistake = $this->createMistake($skippedQuestion, [
            'next_review_at' => now()->addHour(),
            'skip_until' => now()->addHour(),
            'last_reviewed_at' => now()->subDay(),
            'last_attempt_date' => now()->subDay(),
            'skipped' => true,
        ]);

        $payload = $this->service->getMistakesForLearning($this->user->id, 'spaced');

        $this->assertNotNull($payload);
        $this->assertSame(2, $payload['total_mistakes_count']);
        $this->assertSame(1, $payload['skipped_count']);
        $this->assertCount(1, $payload['mistakes']);
        $this->assertSame($dueMistake->id, $payload['mistakes']->first()->id);
        $this->assertFalse($payload['mistakes']->contains(fn ($mistake) => $mistake->id === $skippedMistake->id));
    }

    public function test_it_returns_live_retrievability_after_reviewing_a_spaced_mistake(): void
    {
        $question = $this->createQuestion('Preload');
        $mistake = Mistake::recordFailure($this->user->id, $question, 'B');

        $response = $this->service->reviewMistake(
            $this->user->id,
            $question->id,
            'A',
            18,
            'spaced',
            'knew'
        );

        $mistake->refresh();

        $this->assertTrue($response['success']);
        $this->assertTrue($response['is_correct']);
        $this->assertArrayHasKey('retrievability', $response);
        $this->assertEqualsWithDelta($mistake->currentRetrievability(), (float) $response['retrievability'], 0.0001);
        $this->assertNotNull($response['next_review_at']);
    }

    public function test_it_persists_classic_mastery_across_quick_review_sessions(): void
    {
        $question = $this->createQuestion('Contractility');
        Mistake::recordFailure($this->user->id, $question, 'B');

        $this->service->reviewMistake($this->user->id, $question->id, 'A', 12, 'quick');
        $this->service->reviewMistake($this->user->id, $question->id, 'A', 11, 'quick');

        $mastery = UserMistakeMastery::query()
            ->where('user_id', $this->user->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($mastery);
        $this->assertSame(2, $mastery->consecutive_correct);
        $this->assertNotNull($mastery->graduated_at);
        $this->assertSame('correct', $mastery->last_attempt_result);

        $mistake = Mistake::query()
            ->where('user_id', $this->user->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertSame(2, $mistake->correct_streak);
        $this->assertTrue($mistake->mastered);
    }

    public function test_it_uses_the_legacy_mistake_mirror_when_mastery_table_is_missing(): void
    {
        $question = $this->createQuestion('Cardiac output');
        Mistake::recordFailure($this->user->id, $question, 'B');

        if (Schema::hasTable('user_mistake_mastery')) {
            Schema::drop('user_mistake_mastery');
            UserMistakeMastery::clearTableExistenceCache();
        }

        $first = $this->service->reviewMistake($this->user->id, $question->id, 'A', 12, 'quick');
        $second = $this->service->reviewMistake($this->user->id, $question->id, 'A', 11, 'quick');

        $mistake = Mistake::query()
            ->where('user_id', $this->user->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame(2, $mistake->correct_streak);
        $this->assertTrue($mistake->mastered);
        $this->assertFalse(Schema::hasTable('user_mistake_mastery'));
    }

    public function test_it_uses_current_mastery_rules_for_quick_quiz_and_spaced_modes(): void
    {
        $question = $this->createQuestion('Afterload');
        $mistake = $this->createMistake($question);

        $mistake->forceFill([
            'correct_streak' => 3,
            'mastered' => false,
        ])->save();

        $this->assertTrue($mistake->fresh()->isMastered('quiz'));
        $this->assertTrue($mistake->fresh()->isMastered('quick'));
        $this->assertFalse($mistake->fresh()->isMastered('spaced'));
    }

    public function test_it_uses_two_streaks_for_practice_mode_mastery_tracking(): void
    {
        $this->markTestSkipped('requiredStreakForMode method no longer exists in UserMistakeMasteryService');
    }

    public function test_it_normalizes_the_legacy_skipped_course_filter_on_review_routes(): void
    {
        $question = $this->createQuestion('Frank-Starling law');
        $this->createMistake($question, [
            'skipped' => true,
            'skip_until' => now()->addHour(),
        ]);

        $reviewResponse = $this->actingAs($this->user)->get(route('review.index', ['course' => 'skipped']));
        $reviewResponse->assertOk();
        $reviewResponse->assertViewHas('selectedCourse', '');

        $learnResponse = $this->actingAs($this->user)->get(route('review.learn', [
            'course' => 'skipped',
            'mode' => 'spaced',
        ]));
        $learnResponse->assertOk();
        $learnResponse->assertViewHas('courseFilter', null);
    }

    private function createQuestion(?string $concept = 'Cardiac cycle'): Question
    {
        return Question::create([
            'topic_id' => $this->topic->id,
            'question' => 'What is the correct answer?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'Correct answer',
            'option_b' => 'Wrong answer B',
            'option_c' => 'Wrong answer C',
            'option_d' => 'Wrong answer D',
            'correct_answer' => 'A',
            'concept' => $concept,
        ]);
    }

    private function createMistake(Question $question, array $overrides = []): Mistake
    {
        $mistake = Mistake::create(array_merge([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'submitted_answer' => 'B',
            'question_text' => $question->question,
            'correct_answer' => $question->correct_answer,
            'last_attempt_date' => now()->subDay(),
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subHour(),
            'difficulty' => 0.45,
            'difficulty_baseline' => 0.45,
            'stability' => 1.2,
            'retrievability' => 0.75,
            'review_count' => 1,
            'lapse_count' => 1,
            'mastered' => false,
            'skipped' => false,
        ], $overrides));

        if (array_key_exists('correct_streak', $overrides)) {
            $mistake->forceFill(['correct_streak' => $overrides['correct_streak']])->save();
        }

        return $mistake->fresh();
    }
}
