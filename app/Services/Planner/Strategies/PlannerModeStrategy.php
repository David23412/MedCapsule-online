<?php

namespace App\Services\Planner\Strategies;

use App\Services\Planner\PlannerContext;
use Illuminate\Support\Collection;

interface PlannerModeStrategy
{
    public function supports(string $plannerFocus): bool;

    public function buildCandidates(Collection $topics, PlannerContext $context): Collection;
}
