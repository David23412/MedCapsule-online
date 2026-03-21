<?php

namespace App\Services\Planner;

use Illuminate\Support\Collection;

class PlannerTopicBuckets
{
    public function __construct(
        public Collection $openTopics,
        public Collection $allOpenTopics,
        public Collection $untouchedTopics,
        public Collection $weakTopics,
        public Collection $nearMasteryTopics,
        public Collection $dueMistakeTopics,
        public int $masteredTopicsCount,
        public int $attemptedTopicsCount,
        public ?int $frontierTopicId = null,
        public bool $progressionLocked = false,
    ) {
    }
}
