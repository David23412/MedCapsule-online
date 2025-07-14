<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestTimeoutMiddleware
{
    /**
     * GOLDEN STANDARD: Maximum request processing time (30 seconds)
     */
    const MAX_EXECUTION_TIME = 30;
    
    /**
     * GOLDEN STANDARD: Maximum memory usage (256MB)
     */
    const MAX_MEMORY_USAGE = 268435456; // 256MB in bytes
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $requestId = uniqid('req_timeout_');
        
        // Set execution time limit
        set_time_limit(self::MAX_EXECUTION_TIME);
        
        // Log request start for monitoring
        Log::info('Request timeout monitoring started', [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'start_memory' => $this->formatBytes($startMemory)
        ]);
        
        try {
            // Process the request
            $response = $next($request);
            
            // Calculate execution metrics
            $executionTime = microtime(true) - $startTime;
            $endMemory = memory_get_usage(true);
            $memoryUsed = $endMemory - $startMemory;
            $peakMemory = memory_get_peak_usage(true);
            
            // Log performance metrics
            $this->logPerformanceMetrics($requestId, $request, $executionTime, $memoryUsed, $peakMemory);
            
            // Check for performance warnings
            $this->checkPerformanceWarnings($requestId, $request, $executionTime, $peakMemory);
            
            return $response;
            
        } catch (\Throwable $e) {
            $executionTime = microtime(true) - $startTime;
            $peakMemory = memory_get_peak_usage(true);
            
            Log::error('Request failed during timeout monitoring', [
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'execution_time' => round($executionTime, 3),
                'peak_memory' => $this->formatBytes($peakMemory),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Re-throw the exception
            throw $e;
        }
    }
    
    /**
     * Log performance metrics for monitoring
     */
    private function logPerformanceMetrics(string $requestId, Request $request, float $executionTime, int $memoryUsed, int $peakMemory): void
    {
        Log::info('Request completed - performance metrics', [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_used' => $this->formatBytes($memoryUsed),
            'peak_memory' => $this->formatBytes($peakMemory),
            'user_id' => auth()->id()
        ]);
    }
    
    /**
     * Check for performance warnings and log them
     */
    private function checkPerformanceWarnings(string $requestId, Request $request, float $executionTime, int $peakMemory): void
    {
        $warnings = [];
        
        // Check execution time warnings
        if ($executionTime > 10) {
            $warnings[] = 'SLOW_REQUEST';
        }
        if ($executionTime > 20) {
            $warnings[] = 'VERY_SLOW_REQUEST';
        }
        
        // Check memory usage warnings
        if ($peakMemory > 134217728) { // 128MB
            $warnings[] = 'HIGH_MEMORY_USAGE';
        }
        if ($peakMemory > self::MAX_MEMORY_USAGE) {
            $warnings[] = 'CRITICAL_MEMORY_USAGE';
        }
        
        // Log warnings if any
        if (!empty($warnings)) {
            Log::warning('Request performance warnings detected', [
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'warnings' => $warnings,
                'execution_time_s' => round($executionTime, 3),
                'peak_memory' => $this->formatBytes($peakMemory),
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }
    
    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}