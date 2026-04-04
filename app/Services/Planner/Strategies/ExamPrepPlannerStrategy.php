<?php

namespace App\Services\Planner\Strategies;

use App\Services\Planner\PlannerContext;
use App\Services\Planner\PlannerTaskBuilderService;
use Illuminate\Support\Collection;

class ExamPrepPlannerStrategy implements PlannerModeStrategy
{
    public function __construct(
        private PlannerTaskBuilderService $plannerTaskBuilderService,
    ) {
    }

    public function supports(string $plannerFocus): bool
    {
        return $plannerFocus === 'exam_prep';
    }

    /**
     * Exam Prep mode prioritises is_exam topics over lecture topics.
     *
     * The sort key in PlannerTaskBuilderService::topicSortKey() automatically
     * moves is_exam topics to the front of the progression frontier when
     * plannerFocus === 'exam_prep'. This strategy delegates to the standard
     * protocol builder which uses that sort key for frontier detection.
     */
    public function buildCandidates(Collection $topics, PlannerContext $context): Collection
    {
        return $this->plannerTaskBuilderService->buildProtocolTasks($topics, $context);
    }
}
