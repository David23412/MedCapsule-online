<?php

namespace App\Services\Planner;

use Carbon\Carbon;

class PlannerContext
{
    public function __construct(
        public ?Carbon $examDate,
        public ?int $daysToExam,
        public int $dailyStudyMinutes,
        public int $totalDailyStudyMinutes,
        public string $plannerFocus,
        public int $tasksLimit,
        public int $mixedQuestionCount,
        public int $mixedDurationMinutes,
        public array $carryOverTaskIds,
        public array $courseTopicIds,
        public int $openMistakesCount,
        public float $overallAccuracy,
        public array $topicSignals,
        public array $attemptSignals,
        public array $dueMistakeSignals,
        public array $backlogSignals,
        public array $assignmentSignals,
        public array $conceptSignals,
        public array $behaviorSignals,
        public array $globalAllocation,
        public float $coursePriorityScore,
        public ?string $preferredSource,
        public string $repairMode,
        public string $classicRepairDifficulty,
        public array $resolvedSettings,
    ) {
    }
}
