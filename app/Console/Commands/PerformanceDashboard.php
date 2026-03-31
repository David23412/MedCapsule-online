<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PerformanceDashboard extends Command
{
    protected $signature = 'performance:dashboard {--hours=24}';
    protected $description = 'Display performance dashboard with metrics';
    
    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Performance Dashboard - Last {$hours} hours");
        $this->line(str_repeat('=', 60));
        
        $this->displayOverallMetrics($hours);
        $this->displayRouteMetrics($hours);
        $this->displayCacheMetrics();
        $this->displayRecommendations();
    }
    
    private function displayOverallMetrics(int $hours): void
    {
        $this->info("\n📊 OVERALL PERFORMANCE METRICS");
        $this->line(str_repeat('-', 40));
        
        $totalRequests = 0;
        $totalTime = 0;
        $slowRequests = 0;
        
        for ($i = 0; $i < $hours; $i++) {
            $key = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($key, []);
            
            foreach ($metrics as $route => $data) {
                $totalRequests += $data['count'];
                $totalTime += $data['total_time'];
                
                if ($data['max_time'] > 1000) {
                    $slowRequests++;
                }
            }
        }
        
        $avgResponseTime = $totalRequests > 0 ? round($totalTime / $totalRequests, 2) : 0;
        
        $this->table(['Metric', 'Value'], [
            ['Total Requests', number_format($totalRequests)],
            ['Average Response Time', $avgResponseTime . 'ms'],
            ['Slow Requests (>1s)', $slowRequests],
            ['Performance Score', $this->calculatePerformanceScore($avgResponseTime, $slowRequests, $totalRequests)],
        ]);
    }
    
    private function displayRouteMetrics(int $hours): void
    {
        $this->info("\n🛣️ TOP ROUTES BY RESPONSE TIME");
        $this->line(str_repeat('-', 40));
        
        $routeMetrics = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $key = 'performance_metrics:' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $metrics = Cache::get($key, []);
            
            foreach ($metrics as $route => $data) {
                if (!isset($routeMetrics[$route])) {
                    $routeMetrics[$route] = [
                        'count' => 0,
                        'total_time' => 0,
                        'max_time' => 0,
                    ];
                }
                
                $routeMetrics[$route]['count'] += $data['count'];
                $routeMetrics[$route]['total_time'] += $data['total_time'];
                $routeMetrics[$route]['max_time'] = max($routeMetrics[$route]['max_time'], $data['max_time']);
            }
        }
        
        // Sort by average response time
        uasort($routeMetrics, function ($a, $b) {
            $avgA = $a['count'] > 0 ? $a['total_time'] / $a['count'] : 0;
            $avgB = $b['count'] > 0 ? $b['total_time'] / $b['count'] : 0;
            return $avgB <=> $avgA;
        });
        
        $tableData = [];
        $count = 0;
        foreach ($routeMetrics as $route => $data) {
            if ($count >= 10) break; // Show top 10
            
            $avgTime = $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0;
            $tableData[] = [
                $route ?: 'unknown',
                $data['count'],
                $avgTime . 'ms',
                round($data['max_time'], 2) . 'ms',
            ];
            $count++;
        }
        
        $this->table(['Route', 'Requests', 'Avg Time', 'Max Time'], $tableData);
    }
    
    private function displayCacheMetrics(): void
    {
        $this->info("\n⚡ CACHE PERFORMANCE");
        $this->line(str_repeat('-', 40));
        
        try {
            $cacheService = app(\App\Services\Audit\Performance\AdvancedCachingService::class);
            $stats = $cacheService->getCacheStats();
            
            $this->table(['Metric', 'Value'], [
                ['Total Keys', $stats['total_keys']],
                ['Memory Usage', $stats['memory_usage']],
                ['Hit Rate', $stats['hit_rate'] . '%'],
                ['Cache Size', $stats['cache_size']],
            ]);
        } catch (\Exception $e) {
            $this->warn('Cache metrics not available: ' . $e->getMessage());
        }
    }
    
    private function displayRecommendations(): void
    {
        $this->info("\n💡 PERFORMANCE RECOMMENDATIONS");
        $this->line(str_repeat('-', 40));
        
        $recommendations = [
            '🚀 Enable OPcache for 20-50% performance improvement',
            '⚡ Use Redis for session and cache storage',
            '📦 Optimize images and enable WebP format',
            '🗄️ Add database indexes for slow queries',
            '🔄 Implement cache warming for critical data',
            '📊 Monitor slow requests and optimize them',
        ];
        
        foreach ($recommendations as $recommendation) {
            $this->line($recommendation);
        }
    }
    
    private function calculatePerformanceScore(float $avgTime, int $slowRequests, int $totalRequests): string
    {
        $score = 100;
        
        // Deduct points for slow average response time
        if ($avgTime > 500) $score -= 20;
        elseif ($avgTime > 200) $score -= 10;
        
        // Deduct points for slow requests
        if ($totalRequests > 0) {
            $slowPercentage = ($slowRequests / $totalRequests) * 100;
            if ($slowPercentage > 5) $score -= 30;
            elseif ($slowPercentage > 1) $score -= 15;
        }
        
        $score = max(0, $score);
        
        if ($score >= 90) return $score . '% ✅ Excellent';
        elseif ($score >= 70) return $score . '% 🟡 Good';
        else return $score . '% 🔴 Needs Improvement';
    }
}