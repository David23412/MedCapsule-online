<?php

namespace Tests\Unit\Services\Planner;

use App\Services\Planner\PlannerSignalService;
use App\Models\Mistake;
use App\Models\UserMistakeMastery;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Mockery;
use ReflectionMethod;

/**
 * Tests for PlannerSignalService — backlog classification and signal building.
 *
 * Critical rule: null next_review_at = active_open_backlog, NOT due_spaced_reviews.
 * Classifying null as due inflates SR pressure for classic repair students.
 */
class PlannerSignalServiceTest extends TestCase
{
    private PlannerSignalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlannerSignalService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────
    // classifyBacklogState — Three-bucket backlog classification
    // ────────────────────────────────────────────────────────────

    public function test_null_next_review_classifies_as_active_open_backlog(): void
    {
        $mistake = $this->createMistakeMock(null, false, null);
        $method = new ReflectionMethod(PlannerSignalService::class, 'classifyBacklogState');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $mistake);

        $this->assertSame('active_open_backlog', $result,
            'null next_review_at must classify as active_open_backlog, NOT due_spaced_reviews. ' .
            'Classifying null as due inflates SR pressure for classic repair students.'
        );
    }

    public function test_past_due_classifies_as_due_spaced_reviews(): void
    {
        $mistake = $this->createMistakeMock(
            now()->subHours(2), // past due
            false,
            null
        );
        $method = new ReflectionMethod(PlannerSignalService::class, 'classifyBacklogState');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $mistake);

        $this->assertSame('due_spaced_reviews', $result);
    }

    public function test_future_review_classifies_as_active_open_backlog(): void
    {
        $mistake = $this->createMistakeMock(
            now()->addHours(4), // future review
            false,
            null
        );
        $method = new ReflectionMethod(PlannerSignalService::class, 'classifyBacklogState');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $mistake);

        $this->assertSame('active_open_backlog', $result);
    }

    public function test_skipped_with_future_skip_until_classifies_as_deferred(): void
    {
        $mistake = $this->createMistakeMock(
            null,
            true, // skipped
            now()->addDays(3) // future skip_until
        );
        $method = new ReflectionMethod(PlannerSignalService::class, 'classifyBacklogState');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $mistake);

        $this->assertSame('deferred_skipped_backlog', $result);
    }

    public function test_skipped_with_null_skip_until_classifies_as_deferred(): void
    {
        $mistake = $this->createMistakeMock(
            null,
            true, // skipped
            null  // null skip_until
        );
        $method = new ReflectionMethod(PlannerSignalService::class, 'classifyBacklogState');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $mistake);

        $this->assertSame('deferred_skipped_backlog', $result);
    }

    public function test_skipped_with_past_skip_until_and_due_review(): void
    {
        $mistake = $this->createMistakeMock(
            now()->subMinutes(30), // past due review
            true,                   // skipped
            now()->subDays(1)       // skip_until has passed
        );
        $method = new ReflectionMethod(PlannerSignalService::class, 'classifyBacklogState');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $mistake);

        // skip_until is past → skipped deferral has expired → falls through to review check
        // next_review_at is past → due for review
        $this->assertSame('due_spaced_reviews', $result);
    }

    // ────────────────────────────────────────────────────────────
    // isClassicRepairCandidate — mastery threshold checking
    // ────────────────────────────────────────────────────────────

    public function test_classic_repair_candidate_with_no_mastery_record(): void
    {
        $method = new ReflectionMethod(PlannerSignalService::class, 'isClassicRepairCandidate');
        $method->setAccessible(true);

        $this->assertTrue(
            $method->invoke($this->service, null, 2),
            'No mastery record means student has never practiced — should be repair candidate'
        );
    }

    public function test_classic_repair_candidate_below_threshold(): void
    {
        $method = new ReflectionMethod(PlannerSignalService::class, 'isClassicRepairCandidate');
        $method->setAccessible(true);

        $mastery = $this->createMasteryMock(1); // 1 consecutive correct, needs 2

        $this->assertTrue(
            $method->invoke($this->service, $mastery, 2),
            'Streak below threshold should be repair candidate'
        );
    }

    public function test_classic_repair_candidate_at_threshold_is_not_candidate(): void
    {
        $method = new ReflectionMethod(PlannerSignalService::class, 'isClassicRepairCandidate');
        $method->setAccessible(true);

        $mastery = $this->createMasteryMock(2); // 2 consecutive correct, threshold is 2

        $this->assertFalse(
            $method->invoke($this->service, $mastery, 2),
            'Streak at threshold should NOT be repair candidate (graduated)'
        );
    }

    public function test_hard_mode_requires_3_consecutive_correct(): void
    {
        $method = new ReflectionMethod(PlannerSignalService::class, 'isClassicRepairCandidate');
        $method->setAccessible(true);

        $mastery2 = $this->createMasteryMock(2);
        $mastery3 = $this->createMasteryMock(3);

        $this->assertTrue(
            $method->invoke($this->service, $mastery2, 3),
            'Easy-mode graduate (2 correct) should still be repair candidate in hard mode (needs 3)'
        );

        $this->assertFalse(
            $method->invoke($this->service, $mastery3, 3),
            'Hard-mode graduate (3 correct) should NOT be repair candidate'
        );
    }

    // ────────────────────────────────────────────────────────────
    // emptyBacklogSignals — correct shape
    // ────────────────────────────────────────────────────────────

    public function test_empty_backlog_signals_has_all_required_keys(): void
    {
        $method = new ReflectionMethod(PlannerSignalService::class, 'emptyBacklogSignals');
        $method->setAccessible(true);

        $signals = $method->invoke($this->service);

        $this->assertArrayHasKey('due_spaced_reviews', $signals);
        $this->assertArrayHasKey('active_open_backlog', $signals);
        $this->assertArrayHasKey('deferred_skipped_backlog', $signals);
        $this->assertArrayHasKey('reviewable_backlog', $signals);
        $this->assertArrayHasKey('classic_reviewable_backlog', $signals);
        $this->assertArrayHasKey('total_backlog', $signals);
        $this->assertArrayHasKey('by_topic', $signals);

        $this->assertSame(0, $signals['due_spaced_reviews']);
        $this->assertSame(0, $signals['active_open_backlog']);
        $this->assertSame(0, $signals['deferred_skipped_backlog']);
        $this->assertSame(0, $signals['reviewable_backlog']);
        $this->assertSame([], $signals['by_topic']);
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function createMistakeMock(?Carbon $nextReviewAt, bool $skipped, ?Carbon $skipUntil): Mistake
    {
        $mock = Mockery::mock(Mistake::class)->makePartial();
        $mock->shouldReceive('getAttribute')->with('next_review_at')->andReturn($nextReviewAt);
        $mock->shouldReceive('getAttribute')->with('skipped')->andReturn($skipped);
        $mock->shouldReceive('getAttribute')->with('skip_until')->andReturn($skipUntil);

        return $mock;
    }

    private function createMasteryMock(int $consecutiveCorrect): UserMistakeMastery
    {
        $mock = Mockery::mock(UserMistakeMastery::class)->makePartial();
        $mock->shouldReceive('getAttribute')->with('consecutive_correct')->andReturn($consecutiveCorrect);

        return $mock;
    }
}
