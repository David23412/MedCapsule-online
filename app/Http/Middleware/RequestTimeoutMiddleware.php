<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestTimeoutMiddleware
{
    /**
     * Maximum execution time in seconds
     */
    const MAX_EXECUTION_TIME = 30;

    /**
     * Maximum memory usage in bytes (256MB)
     */
    const MAX_MEMORY_USAGE = 268435456;

    /**
     * Warning thresholds
     */
    const EXECUTION_WARNING_THRESHOLD = 10;   // 10 seconds
    const MEMORY_WARNING_THRESHOLD = 134217728; // 128MB

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $requestId = uniqid('req_');

        // Set execution time limit
        set_time_limit(self::MAX_EXECUTION_TIME);

        // Log request start
        Log::info('Request processing started', [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'start_memory' => $this->formatBytes($startMemory),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        // Register shutdown function to catch timeout/memory errors
        register_shutdown_function(function() use ($requestId, $request, $startTime) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_PARSE, E_RECOVERABLE_ERROR])) {
                $this->handleShutdownError($error, $requestId, $request, $startTime);
            }
        });

        try {
            // Process the request
            $response = $next($request);

            // Check execution time and memory after processing
            $this->checkPerformanceMetrics($requestId, $request, $startTime, $startMemory);

            // Add performance headers
            $this->addPerformanceHeaders($response, $requestId, $startTime, $startMemory);

            return $response;

        } catch (\Throwable $e) {
            return $this->handleException($e, $requestId, $request, $startTime, $startMemory);
        }
    }

    /**
     * Check performance metrics and log warnings/alerts
     */
    private function checkPerformanceMetrics(string $requestId, Request $request, float $startTime, int $startMemory): void
    {
        $executionTime = microtime(true) - $startTime;
        $currentMemory = memory_get_usage(true);
        $memoryUsed = $currentMemory - $startMemory;
        $peakMemory = memory_get_peak_usage(true);

        // Check execution time
        if ($executionTime > self::EXECUTION_WARNING_THRESHOLD) {
            $level = $executionTime > (self::MAX_EXECUTION_TIME * 0.8) ? 'critical' : 'warning';
            
            Log::{$level}('Slow request detected', [
                'request_id' => $requestId,
                'execution_time_seconds' => round($executionTime, 3),
                'threshold_seconds' => self::EXECUTION_WARNING_THRESHOLD,
                'max_allowed_seconds' => self::MAX_EXECUTION_TIME,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
            ]);
        }

        // Check memory usage
        if ($peakMemory > self::MEMORY_WARNING_THRESHOLD) {
            $level = $peakMemory > (self::MAX_MEMORY_USAGE * 0.8) ? 'critical' : 'warning';
            
            Log::{$level}('High memory usage detected', [
                'request_id' => $requestId,
                'peak_memory' => $this->formatBytes($peakMemory),
                'memory_used' => $this->formatBytes($memoryUsed),
                'threshold' => $this->formatBytes(self::MEMORY_WARNING_THRESHOLD),
                'max_allowed' => $this->formatBytes(self::MAX_MEMORY_USAGE),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
            ]);
        }

        // Log performance summary
        Log::info('Request processing completed', [
            'request_id' => $requestId,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_used' => $this->formatBytes($memoryUsed),
            'peak_memory' => $this->formatBytes($peakMemory),
            'status' => $this->getPerformanceStatus($executionTime, $peakMemory),
        ]);
    }

    /**
     * Add performance headers to response
     */
    private function addPerformanceHeaders(Response $response, string $requestId, float $startTime, int $startMemory): void
    {
        $executionTime = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', round($executionTime * 1000, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsed));
        $response->headers->set('X-Peak-Memory', $this->formatBytes(memory_get_peak_usage(true)));
    }

    /**
     * Handle exceptions with performance context
     */
    private function handleException(\Throwable $e, string $requestId, Request $request, float $startTime, int $startMemory): Response
    {
        $executionTime = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage(true) - $startMemory;
        $peakMemory = memory_get_peak_usage(true);

        // Check if this was a timeout or memory related error
        $isTimeoutError = $executionTime >= self::MAX_EXECUTION_TIME;
        $isMemoryError = $peakMemory >= self::MAX_MEMORY_USAGE || 
                        strpos($e->getMessage(), 'memory') !== false ||
                        strpos($e->getMessage(), 'Maximum execution time') !== false;

        Log::error('Request failed with exception', [
            'request_id' => $requestId,
            'exception' => $e->getMessage(),
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'execution_time_seconds' => round($executionTime, 3),
            'memory_used' => $this->formatBytes($memoryUsed),
            'peak_memory' => $this->formatBytes($peakMemory),
            'is_timeout_error' => $isTimeoutError,
            'is_memory_error' => $isMemoryError,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Return appropriate error response
        if ($isTimeoutError) {
            return $this->timeoutResponse($requestId);
        } elseif ($isMemoryError) {
            return $this->memoryLimitResponse($requestId);
        } else {
            return $this->generalErrorResponse($requestId, $e);
        }
    }

    /**
     * Handle shutdown errors (timeouts, fatal errors)
     */
    private function handleShutdownError(array $error, string $requestId, Request $request, float $startTime): void
    {
        $executionTime = microtime(true) - $startTime;
        $peakMemory = memory_get_peak_usage(true);

        $isTimeoutError = strpos($error['message'], 'Maximum execution time') !== false;
        $isMemoryError = strpos($error['message'], 'memory') !== false;

        Log::critical('Request terminated with fatal error', [
            'request_id' => $requestId,
            'error_message' => $error['message'],
            'error_type' => $error['type'],
            'error_file' => $error['file'],
            'error_line' => $error['line'],
            'execution_time_seconds' => round($executionTime, 3),
            'peak_memory' => $this->formatBytes($peakMemory),
            'is_timeout_error' => $isTimeoutError,
            'is_memory_error' => $isMemoryError,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        // Clean up any resources if possible
        $this->performEmergencyCleanup();
    }

    /**
     * Perform emergency cleanup on fatal errors
     */
    private function performEmergencyCleanup(): void
    {
        try {
            // Close database connections
            if (app()->bound('db')) {
                app('db')->disconnect();
            }

            // Clear any large variables
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

        } catch (\Throwable $e) {
            // Ignore cleanup errors in emergency situations
        }
    }

    /**
     * Get performance status based on metrics
     */
    private function getPerformanceStatus(float $executionTime, int $peakMemory): string
    {
        $timeRatio = $executionTime / self::MAX_EXECUTION_TIME;
        $memoryRatio = $peakMemory / self::MAX_MEMORY_USAGE;

        if ($timeRatio > 0.8 || $memoryRatio > 0.8) {
            return 'critical';
        } elseif ($timeRatio > 0.6 || $memoryRatio > 0.6) {
            return 'warning';
        } elseif ($timeRatio > 0.3 || $memoryRatio > 0.3) {
            return 'moderate';
        } else {
            return 'good';
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Return timeout error response
     */
    private function timeoutResponse(string $requestId): Response
    {
        return response()->json([
            'error' => 'Request timeout',
            'message' => 'The request took too long to process and was terminated.',
            'request_id' => $requestId,
            'code' => 'REQUEST_TIMEOUT',
            'max_execution_time' => self::MAX_EXECUTION_TIME . ' seconds',
        ], 408);
    }

    /**
     * Return memory limit error response
     */
    private function memoryLimitResponse(string $requestId): Response
    {
        return response()->json([
            'error' => 'Memory limit exceeded',
            'message' => 'The request exceeded the allowed memory usage and was terminated.',
            'request_id' => $requestId,
            'code' => 'MEMORY_LIMIT_EXCEEDED',
            'max_memory_limit' => $this->formatBytes(self::MAX_MEMORY_USAGE),
        ], 507);
    }

    /**
     * Return general error response
     */
    private function generalErrorResponse(string $requestId, \Throwable $e): Response
    {
        return response()->json([
            'error' => 'Internal server error',
            'message' => 'An error occurred while processing your request.',
            'request_id' => $requestId,
            'code' => 'INTERNAL_ERROR',
            // Only include exception details in debug mode
            'debug' => config('app.debug') ? [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        ], 500);
    }
}