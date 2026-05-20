<?php

namespace Tests\Unit\Services\Learning;

use App\Models\Mistake;
use App\Services\Learning\FsrsScheduler;
use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\SchedulerCalibrationService;
use App\Services\Learning\UserMistakeMasteryService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MistakeManagementServiceTest extends TestCase
{
    public function test_due_relearn_cards_rank_ahead_of_due_reviews(): void
    {
        $service = new MistakeManagementService(
            $this->createMock(UserMistakeMasteryService::class),
            new FsrsScheduler($this->createMock(SchedulerCalibrationService::class))
        );
        $now = Carbon::parse('2026-04-27 12:00:00');

        $relearn = $this->makeMistake([
            'next_review_at' => $now->copy()->subMinutes(5),
            'learning_state' => 'relearn',
            'same_day_relearn_step' => 1,
            'interval_days' => 0,
        ]);

        $review = $this->makeMistake([
            'next_review_at' => $now->copy()->subDay(),
            'learning_state' => null,
            'same_day_relearn_step' => 0,
            'interval_days' => 3,
        ]);

        $this->assertLessThan(0, $this->compare($service, $relearn, $review, $now));
    }

    public function test_more_overdue_due_reviews_rank_ahead_of_less_overdue_reviews(): void
    {
        $service = new MistakeManagementService(
            $this->createMock(UserMistakeMasteryService::class),
            new FsrsScheduler($this->createMock(SchedulerCalibrationService::class))
        );
        $now = Carbon::parse('2026-04-27 12:00:00');

        $moreOverdue = $this->makeMistake([
            'next_review_at' => $now->copy()->subDays(4),
            'interval_days' => 2,
            'retrievability' => 0.55,
            'difficulty' => 0.60,
        ]);

        $lessOverdue = $this->makeMistake([
            'next_review_at' => $now->copy()->subDays(1),
            'interval_days' => 4,
            'retrievability' => 0.35,
            'difficulty' => 0.80,
        ]);

        $this->assertLessThan(0, $this->compare($service, $moreOverdue, $lessOverdue, $now));
    }

    public function test_unscheduled_backlog_ranks_ahead_of_future_scheduled_reviews(): void
    {
        $service = new MistakeManagementService(
            $this->createMock(UserMistakeMasteryService::class),
            new FsrsScheduler($this->createMock(SchedulerCalibrationService::class))
        );
        $now = Carbon::parse('2026-04-27 12:00:00');

        $unscheduled = $this->makeMistake([
            'next_review_at' => null,
            'interval_days' => 0,
        ]);

        $future = $this->makeMistake([
            'next_review_at' => $now->copy()->addHours(8),
            'interval_days' => 2,
        ]);

        $this->assertLessThan(0, $this->compare($service, $unscheduled, $future, $now));
    }

    public function test_spaced_finalization_grade_uses_clean_confidence_mapping(): void
    {
        $service = new MistakeManagementService(
            $this->createMock(UserMistakeMasteryService::class),
            new FsrsScheduler($this->createMock(SchedulerCalibrationService::class))
        );

        $this->assertSame(FsrsScheduler::GRADE_EASY, $this->finalizationRating($service, 0, 'knew'));
        $this->assertSame(FsrsScheduler::GRADE_GOOD, $this->finalizationRating($service, 0, 'unsure'));
        $this->assertSame(FsrsScheduler::GRADE_HARD, $this->finalizationRating($service, 0, 'guessed'));
    }

    public function test_spaced_finalization_with_failures_uses_hard_grade(): void
    {
        $service = new MistakeManagementService(
            $this->createMock(UserMistakeMasteryService::class),
            new FsrsScheduler($this->createMock(SchedulerCalibrationService::class))
        );

        $this->assertSame(FsrsScheduler::GRADE_HARD, $this->finalizationRating($service, 1, 'knew'));
        $this->assertSame(FsrsScheduler::GRADE_HARD, $this->finalizationRating($service, 2, 'knew'));
    }

    private function compare(MistakeManagementService $service, Mistake $left, Mistake $right, Carbon $now): int
    {
        $method = new \ReflectionMethod($service, 'compareSpacedQueuePriority');
        $method->setAccessible(true);

        return (int) $method->invoke($service, $left, $right, $now);
    }

    private function finalizationRating(MistakeManagementService $service, int $sessionFailures, string $bestConfidence): int
    {
        $method = new \ReflectionMethod($service, 'resolveSpacedFinalizationRating');
        $method->setAccessible(true);

        return (int) $method->invoke($service, $sessionFailures, $bestConfidence);
    }

    private function makeMistake(array $attributes): Mistake
    {
        $mistake = new Mistake();
        $mistake->setRawAttributes(array_merge([
            'retrievability' => 0.5,
            'difficulty' => 0.5,
            'lapse_count' => 0,
            'same_day_relearn_step' => 0,
            'learning_state' => null,
            'last_attempt_date' => Carbon::parse('2026-04-20 12:00:00'),
        ], $attributes));
        $mistake->syncOriginal();
        $mistake->setAttribute('current_retrievability', (float) ($attributes['retrievability'] ?? 0.5));
        $mistake->setAttribute('concept_risk_score', (float) ($attributes['concept_risk_score'] ?? 0.0));

        return $mistake;
    }
}
