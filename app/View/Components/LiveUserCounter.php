<?php

namespace App\View\Components;

use App\Services\LiveUserMetricsService;
use Illuminate\View\Component;

class LiveUserCounter extends Component
{
    public function __construct(
        private LiveUserMetricsService $liveUserMetricsService
    ) {
    }

    public function render()
    {
        $count = $this->liveUserMetricsService->getTotalCount();

        $lastUpdated = now()->diffForHumans();

        return view('components.live-user-counter', [
            'count' => $count,
            'lastUpdated' => $lastUpdated
        ]);
    }
} 
