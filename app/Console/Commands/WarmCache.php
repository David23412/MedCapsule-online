<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvancedCacheService;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Warm up application cache for better performance';
    
    public function handle(AdvancedCacheService $cacheService)
    {
        $this->info('Warming up application cache...');
        
        $result = $cacheService->warmCriticalCaches();
        
        $this->info('Cache warming completed successfully!');
        
        // Display cache statistics
        $metrics = $cacheService->getCacheMetrics();
        $this->table(['Metric', 'Value'], [
            ['Warmed Keys', $result['warmed'] ?? 0],
            ['Failed Keys', $result['failed'] ?? 0],
            ['Hit Rate', ($metrics['hit_rate'] ?? 0) . '%'],
        ]);
    }
}