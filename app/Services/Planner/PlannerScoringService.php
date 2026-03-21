<?php

namespace App\Services\Planner;

class PlannerScoringService
{
    public function focusBonus(string $plannerFocus, string $bucket): int
    {
        return match ($plannerFocus) {
            'new_topics' => $bucket === 'new_topics' ? 18 : -4,
            'revision' => $bucket === 'revision' ? 18 : -4,
            'exam_prep' => $bucket === 'exam_prep' ? 18 : ($bucket === 'new_topics' ? -8 : 4),
            default => 0,
        };
    }

    public function sourcePriorityBonus(?string $source, ?string $preferredSource): int
    {
        return $source !== null && $preferredSource !== null && $source === $preferredSource ? 20 : 0;
    }

    public function carryOverBonus(array $carryOverTaskIds, string $taskId): int
    {
        return in_array($taskId, $carryOverTaskIds, true) ? 18 : 0;
    }
}
