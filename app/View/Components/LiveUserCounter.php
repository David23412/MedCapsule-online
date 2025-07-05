<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LiveUserCounter extends Component
{
    public function render()
    {
        // Get the count from cache, with a 1-minute expiration
        $count = Cache::remember('live_users_count', 60, function () {
            try {
                return DB::table('sessions')
                    ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())
                    ->count();
            } catch (\Exception $e) {
                // If database is not available, return 0
                return 0;
            }
        });

        $lastUpdated = now()->diffForHumans();

        return view('components.live-user-counter', [
            'count' => $count,
            'lastUpdated' => $lastUpdated
        ]);
    }
} 