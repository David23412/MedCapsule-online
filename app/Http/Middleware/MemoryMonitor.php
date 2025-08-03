<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memory Monitor Middleware
 * 
 * Monitors memory usage during requests and triggers
 * optimization when thresholds are exceeded.
 */
class MemoryMonitor
{
    private const MEMORY_WARNING_THRESHOLD = 128 * 1024 * 1024; // 128MB
    private const MEMORY_CRITICAL_THRESHOLD = 256 * 1024 * 1024; // 256MB
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Record initial memory usage
        $initialMemory = memory_get_usage(true);
        $initialPeak = memory_get_peak_usage(true);
        
        // Process the request
        $response = $next($request);
        
        // Record final memory usage
        $finalMemory = memory_get_usage(true);
        $finalPeak = memory_get_peak_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Check for memory issues
        $this->checkMemoryThresholds($request, $finalMemory, $finalPeak, $memoryIncrease);
        
        // Add memory headers in debug mode
        if (config('app.debug')) {
            $response->headers->set('X-Memory-Usage', $this->formatBytes($finalMemory));
            $response->headers->set('X-Memory-Peak', $this->formatBytes($finalPeak));
            $response->headers->set('X-Memory-Increase', $this->formatBytes($memoryIncrease));
        }
        
        return $response;
    }
    
    /**
     * Check memory usage against thresholds
     */
    private function checkMemoryThresholds(Request $request, int $currentMemory, int $peakMemory, int $increase): void
    {
        $route = $request->route() ? $request->route()->getName() : $request->path();
        
        if ($currentMemory > self::MEMORY_CRITICAL_THRESHOLD) {
            Log::critical('Critical memory usage detected', [
                'route' => $route,
                'memory_current' => $this->formatBytes($currentMemory),
                'memory_peak' => $this->formatBytes($peakMemory),
                'memory_increase' => $this->formatBytes($increase),
                'user_id' => auth()->id(),
                'request_data' => $this->sanitizeRequestData($request)
            ]);
            
            // Trigger immediate garbage collection
            gc_collect_cycles();
            
        } elseif ($currentMemory > self::MEMORY_WARNING_THRESHOLD) {
            Log::warning('High memory usage detected', [
                'route' => $route,
                'memory_current' => $this->formatBytes($currentMemory),
                'memory_peak' => $this->formatBytes($peakMemory),
                'memory_increase' => $this->formatBytes($increase),
                'user_id' => auth()->id()
            ]);
        }
        
        // Log significant memory increases
        if ($increase > 50 * 1024 * 1024) { // 50MB increase
            Log::info('Significant memory increase detected', [
                'route' => $route,
                'memory_increase' => $this->formatBytes($increase),
                'memory_final' => $this->formatBytes($currentMemory),
                'user_id' => auth()->id()
            ]);
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Sanitize request data for logging
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();
        
        // Remove sensitive data
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key'];
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }
        
        // Limit data size for logging
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 1000) {
            return ['data_size' => strlen($jsonData), 'truncated' => true];
        }
        
        return $data;
    }
}