<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class ClearCourseCaches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'courses:clear-cache {--nuclear : Clear ALL caches (nuclear option)}';

    /**
     * The console command description.
     */
    protected $description = 'Clear all course-related caches for instant updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Clearing course caches for INSTANT updates...');
        
        if ($this->option('nuclear')) {
            $this->warn('💥 NUCLEAR OPTION: Clearing ALL caches...');
            Cache::flush();
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            $this->info('💥 ALL caches cleared!');
        } else {
            // Clear specific course caches
            $this->clearCourseCaches();
        }
        
        // Increment cache version
        $currentVersion = Cache::get('courses_cache_version', 1);
        $newVersion = $currentVersion + 1;
        Cache::put('courses_cache_version', $newVersion, 86400);
        
        $this->info("✅ Cache version incremented to {$newVersion}");
        $this->info('🎉 Course caches cleared! Updates will now be INSTANT!');
        
        return 0;
    }
    
    /**
     * Clear specific course-related caches
     */
    private function clearCourseCaches()
    {
        $cacheKeys = [
            'courses_list',
            'courses_with_stats',
            'courses_metadata_guest_all',
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
            $this->line("   Cleared: {$key}");
        }
        
        // Clear pattern-based caches (if supported by cache driver)
        $patterns = [
            'courses_metadata_*',
            'course_*_enrolled_*',
            'course_*_user_*',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                Cache::forget($pattern);
                $this->line("   Cleared pattern: {$pattern}");
            } catch (\Exception $e) {
                $this->warn("   Could not clear pattern: {$pattern}");
            }
        }
    }
}