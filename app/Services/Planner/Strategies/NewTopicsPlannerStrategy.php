<?php

namespace App\Services\Planner\Strategies;

use App\Services\Planner\PlannerContext;
use App\Services\Planner\PlannerTaskBuilderService;
use Illuminate\Support\Collection;

class NewTopicsPlannerStrategy implements PlannerModeStrategy
{
    public function __construct(
        private PlannerTaskBuilderService $plannerTaskBuilderService,
    ) {
    }

    public function supports(string $plannerFocus): bool
    {
        return $plannerFocus === 'new_topics';
    }

    public function buildCandidates(Collection $topics, PlannerContext $context): Collection
    {
        return $this->plannerTaskBuilderService->buildProtocolTasks($topics, $context);
    }
}
