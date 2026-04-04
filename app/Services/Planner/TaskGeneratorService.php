<?php

namespace App\Services\Planner;

use App\Services\Planner\Strategies\BalancedPlannerStrategy;
use App\Services\Planner\Strategies\ExamPrepPlannerStrategy;
use App\Services\Planner\Strategies\PlannerModeStrategy;
use App\Services\Planner\Strategies\RevisionPlannerStrategy;
use Illuminate\Support\Collection;

class TaskGeneratorService
{
    public function __construct(
        private BalancedPlannerStrategy $balancedPlannerStrategy,
        private RevisionPlannerStrategy $revisionPlannerStrategy,
        private ExamPrepPlannerStrategy $examPrepPlannerStrategy,
    ) {
    }

    public function buildCandidates(Collection $topics, PlannerContext $context): Collection
    {
        return $this->resolveStrategy($context->plannerFocus)
            ->buildCandidates($topics, $context);
    }

    private function resolveStrategy(string $plannerFocus): PlannerModeStrategy
    {
        foreach ([
            $this->revisionPlannerStrategy,
            $this->examPrepPlannerStrategy,
            $this->balancedPlannerStrategy,
        ] as $strategy) {
            if ($strategy->supports($plannerFocus)) {
                return $strategy;
            }
        }

        return $this->balancedPlannerStrategy;
    }
}
