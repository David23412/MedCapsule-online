<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LiveUserMetricsService
{
    private const CACHE_KEY = 'live_user_metrics_snapshot';
    private const CACHE_TTL_SECONDS = 30;

    public function getSnapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            if (AdminSetting::get('fake_live_users_enabled', false)) {
                return $this->buildFakeSnapshot();
            }

            return $this->buildRealSnapshot();
        });
    }

    public function getTotalCount(): int
    {
        return $this->getSnapshot()['count'] ?? 0;
    }

    private function buildFakeSnapshot(): array
    {
        $min = (int) AdminSetting::get('fake_live_users_min', 87);
        $max = (int) AdminSetting::get('fake_live_users_max', 143);

        $seed = (int) floor(time() / self::CACHE_TTL_SECONDS);
        mt_srand($seed);

        $baseCount = mt_rand($min, $max);
        $roundedCount = (int) round($baseCount / 10) * 10;
        $offset = mt_rand(0, 9);
        $finalCount = max($min, min($max, $roundedCount + $offset));

        return [
            'count' => $finalCount,
            'visitors' => (int) floor($finalCount * 0.7),
            'users' => (int) floor($finalCount * 0.3),
            'fake_mode' => true,
        ];
    }

    private function buildRealSnapshot(): array
    {
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;

        $visitorCount = DB::table('sessions')
            ->where('last_activity', '>=', $fiveMinutesAgo)
            ->where('is_visitor', true)
            ->distinct('fingerprint')
            ->count();

        $loggedInCount = DB::table('sessions')
            ->where('last_activity', '>=', $fiveMinutesAgo)
            ->where('is_visitor', false)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count();

        return [
            'count' => $visitorCount + $loggedInCount,
            'visitors' => $visitorCount,
            'users' => $loggedInCount,
            'fake_mode' => false,
        ];
    }
}
