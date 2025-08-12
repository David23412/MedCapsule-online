<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HomePageService;
use Illuminate\Support\Facades\Cache;

class WarmHomePageCache extends Command
{
    protected $signature = 'cache:warm-home';
    protected $description = 'Warm up home page cache for lightning fast loading';

    public function handle()
    {
        $this->info('🔥 Warming up home page cache...');

        $homePageService = app(HomePageService::class);
        
        // Warm up home page data
        $data = $homePageService->getHomePageData();
        
        // Cache for guests
        Cache::put('home_nuclear_v4_guest_' . now()->format('Y-m-d-H'), $data, 7200);
        
        // Cache fallback data
        Cache::rememberForever('home_fallback_nuclear', function () {
            return [
                'statistics' => [
                    'quizzes' => 0,
                    'cases' => 0,
                    'practical' => 0,
                    'questions' => 0,
                    'enrolled_students' => 0
                ],
                'featuredCourses' => collect([])
            ];
        });

        $this->info('✅ Home page cache warmed successfully!');
        $this->info('📊 Statistics cached: ' . json_encode($data['statistics']));
        $this->info('📚 Featured courses cached: ' . $data['featuredCourses']->count() . ' courses');
        
        return 0;
    }
}