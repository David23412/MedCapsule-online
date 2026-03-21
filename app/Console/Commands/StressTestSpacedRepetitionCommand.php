<?php

namespace App\Console\Commands;

use App\Models\Mistake;
use App\Services\Learning\AdaptiveMistakeScheduler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StressTestSpacedRepetitionCommand extends Command
{
    protected $signature   = 'mistakes:stress-test-spaced-repetition
                                {--days=365 : Number of days to simulate}
                                {--persona= : Run only a specific persona by name}';
    protected $description = 'Runs rigorous stress tests against the AdaptiveMistakeScheduler to find mathematical limits and shortcomings.';

    private AdaptiveMistakeScheduler $scheduler;
    private array $results = [];

    // ──────────────────────────────────────────────────────────────────────────
    // Personas
    // Each entry: [behavior callable, mode, description]
    //   behavior signature: fn(int $day, int $currentIntervalDays): array
    //   return keys: rating, confidence, response_seconds
    // ──────────────────────────────────────────────────────────────────────────
    private function buildPersonas(): array
    {
        return [
            // ── Spaced-mode personas ──────────────────────────────────────────
            'The Perfectionist' => [
                'mode'        => 'spaced',
                'description' => 'Always rating 5, fast answers, high confidence.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 5,
                    'confidence'       => 'knew',
                    'response_seconds' => 12,
                ],
            ],
            'The Steady Learner' => [
                'mode'        => 'spaced',
                'description' => 'Rating 4, unsure confidence, moderate speed.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 4,
                    'confidence'       => 'unsure',
                    'response_seconds' => 30,
                ],
            ],
            'The Guesser' => [
                'mode'        => 'spaced',
                'description' => 'Random rating 2–3, guessed confidence.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => random_int(1, 5) >= 3 ? 3 : 2,
                    'confidence'       => 'guessed',
                    'response_seconds' => 10,
                ],
            ],
            'The Hesitator' => [
                'mode'        => 'spaced',
                'description' => 'Rating 4 but very slow despite eventually recalling the answer.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 4,
                    'confidence'       => 'unsure',
                    'response_seconds' => 120,
                ],
            ],
            'The Volatile' => [
                'mode'        => 'spaced',
                'description' => 'Fails once a week, perfect otherwise.',
                'behavior'    => fn(int $day, int $interval) => $day % 7 === 0
                    ? ['rating' => 1, 'confidence' => 'guessed', 'response_seconds' => 20]
                    : ['rating' => 5, 'confidence' => 'knew',    'response_seconds' => 15],
            ],
            'Ease Hell Victim (classic)' => [
                'mode'        => 'spaced',
                'description' => 'Struggles early then recovers — original ease hell scenario.',
                'behavior'    => fn(int $day, int $interval) => $day < 30
                    ? ['rating' => 2, 'confidence' => 'unsure', 'response_seconds' => 45]
                    : ['rating' => 4, 'confidence' => 'unsure', 'response_seconds' => 30],
            ],
            'True Ease Hell' => [
                'mode'        => 'spaced',
                'description' => 'Always rating 3 with guessed confidence, designed to keep progress conservative despite passing.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 3,
                    'confidence'       => 'guessed',
                    'response_seconds' => 60,
                ],
            ],
            'The Overconfident' => [
                'mode'        => 'spaced',
                'description' => 'Rating 2 but always says "knew" — overconfidence trap.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 2,
                    'confidence'       => 'knew',
                    'response_seconds' => 10,
                ],
            ],
            'Long Interval Forgetter' => [
                'mode'        => 'spaced',
                'description' => 'Perfect on short intervals; fails when interval > 30d.',
                'behavior'    => fn(int $day, int $interval) => $interval > 30
                    ? ['rating' => 1, 'confidence' => 'guessed', 'response_seconds' => 60]
                    : ['rating' => 5, 'confidence' => 'knew',    'response_seconds' => 15],
            ],
            'Mass Lapse' => [
                'mode'        => 'spaced',
                'description' => 'Fails every single review — stress test of lapse accumulation and stability floor.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 1,
                    'confidence'       => 'guessed',
                    'response_seconds' => 45,
                ],
            ],
            // ── Non-spaced personas (quiz/quick modes) ────────────────────────
            'Quiz Mode Learner' => [
                'mode'        => 'quiz',
                'description' => 'Standard quiz mode — rating 4, unsure. Tests non-spaced failure path.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 4,
                    'confidence'       => 'unsure',
                    'response_seconds' => 30,
                ],
            ],
            'Quiz Mode Failer' => [
                'mode'        => 'quiz',
                'description' => 'Always fails in quiz mode — verifies non-spaced applyFailure path is sane.',
                'behavior'    => fn(int $day, int $interval) => [
                    'rating'           => 1,
                    'confidence'       => 'guessed',
                    'response_seconds' => 30,
                ],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Entry point
    // ──────────────────────────────────────────────────────────────────────────
    public function handle(AdaptiveMistakeScheduler $scheduler): int
    {
        $this->scheduler = $scheduler;
        $days            = (int) $this->option('days');
        $filterPersona   = $this->option('persona');

        $user = \App\Models\User::first();
        if (! $user) {
            $this->error('No users found in the database. Please seed the database first.');
            return self::FAILURE;
        }

        $masterMistake = \App\Models\Mistake::first();
        if (! $masterMistake) {
            $this->error('No mistakes found in the database. Please seed the database first.');
            return self::FAILURE;
        }

        $this->info("Starting Spaced Repetition Stress Test for {$days} simulated days...\n");

        $personas = $this->buildPersonas();

        foreach ($personas as $name => $config) {
            if ($filterPersona && strtolower($filterPersona) !== strtolower($name)) {
                continue;
            }

            DB::beginTransaction();

            try {
                // Each persona gets a fresh persisted row so the scheduler can write
                // review events normally, then the whole simulation is rolled back.
                $mistake = $this->cloneMistakeFresh($masterMistake, $user->id);
                $this->simulatePersona($name, $config, $mistake, $days);
            } finally {
                DB::rollBack();
                Carbon::setTestNow();
            }
        }

        $this->analyzeResults();
        Carbon::setTestNow(); // always reset wall clock

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Clone the master mistake into an isolated transactional row.
    // The scheduler can write its normal state/events, then the outer transaction
    // rolls the whole simulation back so there is no persistent contamination.
    // ──────────────────────────────────────────────────────────────────────────
    private function cloneMistakeFresh(Mistake $source, int $userId): Mistake
    {
        $clone = $source->replicate();
        $clone->user_id = $userId;

        // Reset all scheduler state so every persona starts from a clean slate
        $clone->difficulty             = null;
        $clone->stability              = null;
        $clone->retrievability         = null;
        $clone->interval_days          = 0;
        $clone->ease_factor            = 2.3;
        $clone->lapse_count            = 0;
        $clone->review_count           = 0;
        $clone->correct_streak         = 0;
        $clone->same_day_relearn_count = 0;
        $clone->same_day_relearn_step  = 0;
        $clone->learning_state         = null;
        $clone->next_review_at         = now();
        $clone->last_reviewed_at       = null;
        $clone->last_attempt_date      = null;
        $clone->hesitation_score       = 0;
        $clone->skip_count             = 0;
        $clone->consecutive_skip_count = 0;
        $clone->skip_until             = null;
        $clone->mastered               = false;
        $clone->skipped                = false;
        $clone->save();
        $clone->setRelation('question', $source->question);

        return $clone;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Simulate one persona across N days
    // ──────────────────────────────────────────────────────────────────────────
    private function simulatePersona(string $name, array $config, Mistake $mistake, int $days): void
    {
        $behavior = $config['behavior'];
        $mode     = $config['mode'];

        $start       = now()->startOfDay();
        $currentDate = $start->copy();

        Carbon::setTestNow($start);

        // Boot the card from a fresh failure
        $this->scheduler->initializeFromFailure($mistake);

        $history = [];

        for ($day = 0; $day <= $days; $day++) {
            Carbon::setTestNow($currentDate);

            $nextReviewAt = $this->parseCarbon($mistake->next_review_at);

            if ($currentDate->gte($nextReviewAt)) {
                $interval = (int) ($mistake->interval_days ?? 0);
                $action   = $behavior($day, $interval);

                $this->scheduler->applyReview(
                    mistake:            $mistake,
                    rating:             $action['rating'],
                    mode:               $mode,
                    responseSeconds:    $action['response_seconds'],
                    confidence:         $action['confidence'],
                    interactionSignals: $this->buildInteractionSignals(),
                );

                $history[] = $this->snapshot($day, $action['rating'], $mistake);

                // ── Drive through ALL relearn steps until the card exits relearn.
                //    BUGFIX: previously only one relearn step was simulated.
                if ($mode === 'spaced') {
                    $relearnIterations = 0;
                    while ($mistake->learning_state === 'relearn' && $relearnIterations < 10) {
                        $relearnIterations++;
                        $relearnDue = $this->parseCarbon($mistake->next_review_at);
                        Carbon::setTestNow($relearnDue);

                        // Force a passing action during relearn recovery
                        $relearnRating  = max(3, $action['rating']);
                        $relearnAction  = [
                            'rating'           => $relearnRating,
                            'confidence'       => $action['confidence'],
                            'response_seconds' => $action['response_seconds'],
                        ];

                        $this->scheduler->applyReview(
                            mistake:            $mistake,
                            rating:             $relearnAction['rating'],
                            mode:               $mode,
                            responseSeconds:    $relearnAction['response_seconds'],
                            confidence:         $relearnAction['confidence'],
                            interactionSignals: $this->buildInteractionSignals(),
                        );

                        $history[] = $this->snapshot(
                            "{$day}(relearn-step-{$relearnIterations})",
                            $relearnAction['rating'],
                            $mistake
                        );
                    }
                }
            }

            $currentDate->addDay();
        }

        $this->results[$name] = [
            'mode'          => $mode,
            'description'   => $config['description'],
            'final_state'   => $mistake->toArray(),
            'history'       => $history,
            'total_reviews' => count($history),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Skip-behavior utility (called separately if needed for skip persona)
    // ──────────────────────────────────────────────────────────────────────────
    private function buildInteractionSignals(): array
    {
        return [];
    }

    private function snapshot(string|int $day, int $rating, Mistake $mistake): array
    {
        return [
            'day'                   => $day,
            'rating'                => $rating,
            'difficulty'            => $mistake->difficulty,
            'stability'             => $mistake->stability,
            'retrievability'        => $mistake->retrievability,
            'interval_days'         => $mistake->interval_days,
            'learning_state'        => $mistake->learning_state,
            'same_day_relearn_step' => $mistake->same_day_relearn_step,
            'lapse_count'           => $mistake->lapse_count,
        ];
    }

    private function parseCarbon(mixed $value): Carbon
    {
        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Analysis
    // ──────────────────────────────────────────────────────────────────────────
    private function analyzeResults(): void
    {
        $this->info("\n══════════════════════════════════════════");
        $this->info('       STRESS TEST ANALYSIS REPORT');
        $this->info("══════════════════════════════════════════\n");

        $shortcomings = [];
        $maxCap       = AdaptiveMistakeScheduler::MAX_INTERVAL_DAYS;

        foreach ($this->results as $name => $data) {
            $history = $data['history'];
            $mode    = $data['mode'];

            $this->line("<fg=cyan>▶ [{$mode}] {$name}</>");
            $this->line("  {$data['description']}");
            $this->line("  Reviews: {$data['total_reviews']}");

            if (empty($history)) {
                $this->warn("  ⚠ No reviews simulated.");
                $this->newLine();
                continue;
            }

            $last = end($history);
            $this->line(sprintf(
                "  Final → interval: %s d | difficulty: %s | stability: %s | lapses: %s",
                $last['interval_days'] ?? '—',
                $last['difficulty']    ?? '—',
                $last['stability']     ?? '—',
                $last['lapse_count']   ?? '—',
            ));

            // Print a compact interval-growth table (first/mid/last 3 non-relearn reviews)
            $milestones = $this->extractMilestones($history);
            if (count($milestones) > 0) {
                $this->line('  Interval milestones (day → interval_days):');
                foreach ($milestones as $m) {
                    $bar = str_repeat('█', min(40, (int)($m['interval_days'] / max(1, $maxCap / 40))));
                    $this->line(sprintf("    day %-6s | %4s d | %s", $m['day'], $m['interval_days'], $bar));
                }
            }

            // ── Algorithmic checks ──────────────────────────────────────────

            // 1. Interval Explosion — use the actual scheduler cap
            if (($last['interval_days'] ?? 0) >= $maxCap) {
                $shortcomings[] = "[{$name}] Interval Cap Hit: Interval hit the {$maxCap}-day ceiling. Consider a lower practical max (e.g. 180 d).";
            }

            // 2. True Ease Hell — correct answers but difficulty stuck high
            $recentHistory       = array_slice($history, -10);
            $allCorrectRecently  = count(array_filter($recentHistory, fn($r) => $r['rating'] >= 3)) === count($recentHistory);
            $lastDifficulty      = (float)($last['difficulty'] ?? 0);
            if ($allCorrectRecently && $lastDifficulty > 0.80) {
                $shortcomings[] = "[{$name}] Ease Hell: Passing all recent reviews but difficulty is still {$lastDifficulty}. Recovery is too shallow.";
            }

            // 3. Hesitation vs Perfectionist comparison — done after full loop (order-safe)
            if ($name === 'The Hesitator' && isset($this->results['The Perfectionist'])) {
                $perfHistory  = $this->results['The Perfectionist']['history'];
                $perfLast     = end($perfHistory);
                $hesiStab     = (float)($last['stability'] ?? 0);
                $perfStab     = (float)($perfLast['stability'] ?? 1);
                if ($perfStab > 0 && $hesiStab < ($perfStab * 0.2)) {
                    $shortcomings[] = "[{$name}] Excessive Hesitation Penalty: Stability {$hesiStab} vs Perfectionist's {$perfStab}. Hesitation is overwhelming the rating boost.";
                }
            }

            // 4. Overconfidence death
            if ($name === 'The Overconfident' && (float)($last['stability'] ?? 1) < 0.2) {
                $shortcomings[] = "[{$name}] Overconfidence Death: Stability permanently tanked at {$last['stability']}. Overconfidence penalty may be too rigid.";
            }

            // 5. Mass lapse — make sure stability floor is holding
            if ($name === 'Mass Lapse' && (float)($last['stability'] ?? 1) < 0.10) {
                $shortcomings[] = "[{$name}] Stability Floor Breach: Stability dropped below 0.10 ({$last['stability']}). Minimum floor not holding.";
            }

            // 6. Non-spaced failure guard — interval should never be negative
            if (($last['interval_days'] ?? 0) < 0) {
                $shortcomings[] = "[{$name}] Negative Interval: interval_days is {$last['interval_days']}. Bug in non-spaced scheduling path.";
            }

            // 7. Verdict
            $verdict = $this->buildVerdict($last, $mode);
            $this->line("  Verdict: {$verdict}");
            $this->newLine();
        }

        // ── Summary ──────────────────────────────────────────────────────────
        $this->info('══════════════════════════════════════════');
        $this->info('      IDENTIFIED ALGORITHMIC ISSUES');
        $this->info('══════════════════════════════════════════');
        if (empty($shortcomings)) {
            $this->info('✅ No major breaking points identified. Algorithm looks healthy.');
        } else {
            foreach ($shortcomings as $issue) {
                $this->warn("  ⚠ {$issue}");
            }
        }
    }

    private function extractMilestones(array $history): array
    {
        // Filter to real-day reviews only (not relearn sub-steps)
        $real = array_values(array_filter($history, fn($r) => is_int($r['day'])));
        $count = count($real);
        if ($count === 0) return [];

        $indices = array_unique([
            0,
            (int)floor($count * 0.25),
            (int)floor($count * 0.5),
            (int)floor($count * 0.75),
            $count - 1,
        ]);
        sort($indices);

        return array_map(fn($i) => $real[$i], $indices);
    }

    private function buildVerdict(array $last, string $mode): string
    {
        $interval   = (int)($last['interval_days']  ?? 0);
        $difficulty = (float)($last['difficulty']   ?? 0.5);
        $stability  = (float)($last['stability']    ?? 0);
        $lapses     = (int)($last['lapse_count']    ?? 0);

        if ($mode === 'spaced') {
            if ($lapses === 0 && $interval > 30 && $difficulty < 0.40) {
                return '✅ Healthy — good long-term retention trajectory.';
            }
            if ($lapses > 10 || $stability < 0.2) {
                return '🔴 Struggling — card is stuck near the bottom.';
            }
            if ($difficulty > 0.80 && $interval < 5) {
                return '🟡 At Risk — very high difficulty, short intervals.';
            }
            return '🟢 Acceptable — progressing, monitor difficulty.';
        }

        // Non-spaced: just interval sanity
        if ($interval < 0) {
            return '🔴 Bug — negative interval detected.';
        }
        return '🟢 Non-spaced path OK.';
    }
}
