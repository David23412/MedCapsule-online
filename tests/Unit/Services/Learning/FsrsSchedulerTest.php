<?php

namespace Tests\Unit\Services\Learning;

use App\Models\Mistake;
use App\Services\Learning\FsrsScheduler;
use Carbon\Carbon;
use Tests\TestCase;

class FsrsSchedulerTest extends TestCase
{
    public function test_initialize_from_failure_enters_learning_state(): void
    {
        $scheduler = app(FsrsScheduler::class);
        $mistake = $this->makeMistake();

        $scheduler->initializeFromFailure($mistake, 'A', Carbon::parse('2026-04-27 10:00:00'), false);

        $this->assertSame('learning', $mistake->learning_state);
        $this->assertSame(1, (int) $mistake->same_day_relearn_step);
        $this->assertSame(0, (int) $mistake->interval_days);
        $this->assertGreaterThan(0, (float) $mistake->stability);
        $this->assertGreaterThan(1, (float) $mistake->difficulty);
    }

    public function test_learning_good_advances_to_next_learning_step(): void
    {
        $scheduler = app(FsrsScheduler::class);
        $mistake = $this->makeMistake();

        $scheduler->initializeFromFailure($mistake, 'A', Carbon::parse('2026-04-27 10:00:00'), false);
        $scheduler->applyReview($mistake, FsrsScheduler::GRADE_GOOD, 'spaced', null, 'unsure', [], Carbon::parse('2026-04-27 10:00:00'), false);

        $this->assertSame('learning', $mistake->learning_state);
        $this->assertSame(2, (int) $mistake->same_day_relearn_step);
        $this->assertSame(0, (int) $mistake->interval_days);
        $this->assertNotNull($mistake->next_review_at);
    }

    public function test_learning_easy_graduates_to_review(): void
    {
        $scheduler = app(FsrsScheduler::class);
        $mistake = $this->makeMistake();

        $scheduler->initializeFromFailure($mistake, 'A', Carbon::parse('2026-04-27 10:00:00'), false);
        $scheduler->applyReview($mistake, FsrsScheduler::GRADE_EASY, 'spaced', null, 'knew', [], Carbon::parse('2026-04-27 10:00:00'), false);

        $this->assertSame('review', $mistake->learning_state);
        $this->assertSame(0, (int) $mistake->same_day_relearn_step);
        $this->assertGreaterThanOrEqual(1, (int) $mistake->interval_days);
    }

    public function test_review_again_enters_relearning(): void
    {
        $scheduler = app(FsrsScheduler::class);
        $mistake = $this->makeMistake([
            'learning_state' => 'review',
            'interval_days' => 6,
            'review_count' => 3,
            'stability' => 4.2,
            'difficulty' => 5.8,
            'retrievability' => 0.83,
            'last_reviewed_at' => Carbon::parse('2026-04-20 10:00:00'),
            'next_review_at' => Carbon::parse('2026-04-26 04:00:00'),
        ]);

        $scheduler->applyReview($mistake, FsrsScheduler::GRADE_AGAIN, 'spaced', null, 'unsure', [], Carbon::parse('2026-04-27 10:00:00'), false);

        $this->assertSame('relearning', $mistake->learning_state);
        $this->assertSame(1, (int) $mistake->same_day_relearn_step);
        $this->assertSame(0, (int) $mistake->interval_days);
        $this->assertSame(1, (int) $mistake->lapse_count);
    }

    public function test_same_grade_ignores_confidence_for_scheduling_math(): void
    {
        $scheduler = app(FsrsScheduler::class);
        $left = $this->makeMistake([
            'learning_state' => 'review',
            'interval_days' => 10,
            'review_count' => 4,
            'stability' => 5.5,
            'difficulty' => 5.0,
            'retrievability' => 0.82,
            'last_reviewed_at' => Carbon::parse('2026-04-20 10:00:00'),
            'next_review_at' => Carbon::parse('2026-04-26 04:00:00'),
        ]);
        $right = $this->makeMistake($left->getAttributes());

        $reviewedAt = Carbon::parse('2026-04-27 10:00:00');
        $scheduler->applyReview($left, FsrsScheduler::GRADE_GOOD, 'spaced', 12, 'guessed', [], $reviewedAt, false);
        $scheduler->applyReview($right, FsrsScheduler::GRADE_GOOD, 'spaced', 12, 'knew', [], $reviewedAt, false);

        $this->assertSame((int) $left->interval_days, (int) $right->interval_days);
        $this->assertSame((string) $left->next_review_at, (string) $right->next_review_at);
        $this->assertSame((string) $left->difficulty, (string) $right->difficulty);
        $this->assertSame((string) $left->stability, (string) $right->stability);
    }

    private function makeMistake(array $attributes = []): Mistake
    {
        $mistake = new class extends Mistake {
            public static function hasColumn(string $column): bool
            {
                return true;
            }
        };
        $mistake->setRawAttributes(array_merge([
            'id' => 1,
            'user_id' => 1,
            'question_id' => 1,
            'submitted_answer' => 'A',
            'correct_streak' => 0,
            'review_count' => 0,
            'lapse_count' => 0,
            'interval_days' => 0,
            'ease_factor' => 2.3,
            'difficulty' => 5.0,
            'difficulty_baseline' => 3.0,
            'stability' => 0.21,
            'retrievability' => 1.0,
            'skip_count' => 0,
            'consecutive_skip_count' => 0,
            'learning_state' => null,
            'same_day_relearn_step' => 0,
            'same_day_relearn_count' => 0,
            'mastered' => false,
            'skipped' => false,
            'last_reviewed_at' => null,
            'last_attempt_date' => null,
            'next_review_at' => null,
        ], $attributes), true);
        $mistake->syncOriginal();

        return $mistake;
    }
}
