<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LiveUserCounter extends Component
{
    public $count;
    public $lastUpdated;

    public function __construct()
    {
        // Get the count from cache, with a 1-minute expiration
        $this->count = Cache::remember('live_users_count', 60, function () {
            return DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())
                ->count();
        });

        $this->lastUpdated = now()->diffForHumans();
    }

    public function render()
    {
        return view('components.live-user-counter');
    }
} 