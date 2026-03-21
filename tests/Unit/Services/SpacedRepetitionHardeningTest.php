<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use App\Models\Question;
use App\Models\User;
use App\Services\Learning\ConceptAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpacedRepetitionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private \App\Models\Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->student()->create();
        $course = Course::create([
            'name' => 'Physiology',
            'description' => 'Core physiology course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        $this->topic = \App\Models\Topic::create([
            'course_id' => $course->id,
            'name' => 'Hemodynamics',
            'case_type' => 'quiz',
            'display_order' => 1,
        ]);
    }

    public function test_spaced_first_failure_records_failure_init_event_and_seeded_state(): void
    {
        $question = $this->createQuestion('Cardiac cycle');

        $mistake = Mistake::recordFailure($this->user->id, $question, 'B')->fresh();

        $this->assertSame('learning', $mistake->learning_state);
        $this->assertSame(1, (int) $mistake->same_day_relearn_step);
        $this->assertFalse($mistake->skipped);
        $this->assertSame(0, $mistake->correct_streak);

        $event = MistakeReviewEvent::query()->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame(MistakeReviewEvent::EVENT_TYPE_FAILURE_INIT, $event->event_type);
        $this->assertSame($mistake->id, $event->mistake_id);
        $this->assertSame('quiz', $event->review_mode);
    }

    public function test_spaced_skip_sets_skip_until_and_logs_skip_event(): void
    {
        $question = $this->createQuestion('Stroke volume');
        $mistake = Mistake::recordFailure($this->user->id, $question, 'B');

        $mistake->applySkip('spaced');
        $mistake->save();
        $mistake->refresh();

        $this->assertTrue($mistake->skipped);
        $this->assertNotNull($mistake->skip_until);

        $event = MistakeReviewEvent::query()
            ->where('mistake_id', $mistake->id)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_SKIP)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('spaced', $event->review_mode);
    }

    public function test_spaced_relearn_success_keeps_stored_and_live_retrievability_aligned(): void
    {
        $question = $this->createQuestion('Preload');
        $mistake = Mistake::recordFailure($this->user->id, $question, 'B');

        $mistake->applyRatedReview(4, 'spaced', 19, 'unsure');
        $mistake->save();
        $mistake->refresh();

        $this->assertSame('relearn', $mistake->learning_state);
        $this->assertSame(2, (int) $mistake->same_day_relearn_step);
        $this->assertEqualsWithDelta((float) $mistake->retrievability, $mistake->currentRetrievability(), 0.0001);

        $reviewEvent = MistakeReviewEvent::query()
            ->where('mistake_id', $mistake->id)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->latest('id')
            ->first();

        $this->assertNotNull($reviewEvent);
        $this->assertSame('spaced', $reviewEvent->review_mode);
    }

    public function test_spaced_mature_success_schedules_future_review_and_clears_relearn_state(): void
    {
        $question = $this->createQuestion('Afterload');
        $mistake = $this->createMatureSpacedMistake($question);

        $mistake->applyRatedReview(5, 'spaced', 8, 'knew');
        $mistake->save();
        $mistake->refresh();

        $this->assertNull($mistake->learning_state);
        $this->assertSame(0, (int) $mistake->same_day_relearn_step);
        $this->assertFalse($mistake->skipped);
        $this->assertNotNull($mistake->next_review_at);
        $this->assertTrue($mistake->next_review_at->isFuture());
        $this->assertEqualsWithDelta((float) $mistake->retrievability, $mistake->currentRetrievability(), 0.0001);
    }

    public function test_spaced_concept_analytics_uses_question_concept_and_topic_fallback_without_focus_concept_column(): void
    {
        $explicitConceptQuestion = $this->createQuestion('Cardiac cycle');
        $fallbackTopicQuestion = $this->createQuestion(null, 'Aortic pressure');

        Mistake::recordFailure($this->user->id, $explicitConceptQuestion, 'B');
        Mistake::recordFailure($this->user->id, $fallbackTopicQuestion, 'B');

        $signals = app(ConceptAnalyticsService::class)->buildSignals($this->user, [$this->topic->id]);
        $conceptNames = collect($signals['by_topic'][$this->topic->id] ?? [])->pluck('concept_name');

        $this->assertFalse(Schema::hasColumn('mistakes', 'focus_concept'));
        $this->assertTrue($conceptNames->contains('Cardiac cycle'));
        $this->assertTrue($conceptNames->contains($this->topic->name));
    }

    private function createQuestion(?string $concept = 'Cardiac cycle', string $questionText = 'What is the correct answer?'): Question
    {
        return Question::create([
            'topic_id' => $this->topic->id,
            'question' => $questionText,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'Correct answer',
            'option_b' => 'Wrong answer B',
            'option_c' => 'Wrong answer C',
            'option_d' => 'Wrong answer D',
            'correct_answer' => 'A',
            'concept' => $concept,
        ]);
    }

    private function createMatureSpacedMistake(Question $question): Mistake
    {
        $mistake = Mistake::create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'submitted_answer' => 'B',
            'question_text' => $question->question,
            'correct_answer' => $question->correct_answer,
            'last_attempt_date' => now()->subDays(6),
            'last_reviewed_at' => now()->subDays(6),
            'next_review_at' => now()->subDay(),
            'difficulty' => 0.44,
            'difficulty_baseline' => 0.48,
            'stability' => 2.5,
            'retrievability' => 0.90,
            'review_count' => 3,
            'lapse_count' => 1,
            'correct_streak' => 2,
            'mastered' => false,
            'skipped' => false,
            'learning_state' => null,
            'same_day_relearn_step' => 0,
            'same_day_relearn_count' => 0,
        ]);

        $mistake->forceFill([
            'correct_streak' => 2,
            'last_attempt_date' => now()->subDays(6),
            'last_reviewed_at' => now()->subDays(6),
            'next_review_at' => now()->subDay(),
            'review_count' => 3,
            'lapse_count' => 1,
            'learning_state' => null,
            'same_day_relearn_step' => 0,
            'same_day_relearn_count' => 0,
        ])->save();

        return $mistake->fresh();
    }
}
