<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Performance\AdvancedCachingService;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Warm up application cache for better performance';
    
    public function handle(AdvancedCachingService $cacheService)
    {
        $this->info('Warming up application cache...');
        
        $cacheService->warmCache();
        
        $this->info('Cache warming completed successfully!');
        
        // Display cache statistics
        $stats = $cacheService->getCacheStats();
        $this->table(['Metric', 'Value'], [
            ['Total Keys', $stats['total_keys']],
            ['Memory Usage', $stats['memory_usage']],
            ['Hit Rate', $stats['hit_rate'] . '%'],
            ['Cache Size', $stats['cache_size']],
        ]);
    }
}