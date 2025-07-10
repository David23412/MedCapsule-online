<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\EmergencyFallbackService;
use App\Services\HealthCheckService;
use Exception;
use Throwable;

class ErrorBoundary
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = uniqid('req_');
        $startTime = microtime(true);
        
        try {
            // Check if emergency mode should be activated
            EmergencyFallbackService::checkAndActivateEmergencyMode();
            
            // Process the request
            $response = $next($request);
            
            return $response;
            
        } catch (Throwable $e) {
            return $this->handleError($e, $request, $requestId, $startTime);
        }
    }

    /**
     * Handle errors with comprehensive fallback strategy
     */
    private function handleError(Throwable $e, Request $request, string $requestId, float $startTime): Response
    {
        $duration = (microtime(true) - $startTime) * 1000;
        $errorClass = get_class($e);
        $route = $request->route() ? $request->route()->getName() : 'unknown';
        
        // Critical error logging
        Log::error('Error boundary caught exception', [
            'request_id' => $requestId,
            'error_class' => $errorClass,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'route' => $route,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'duration_ms' => round($duration, 2),
            'stack_trace' => $e->getTraceAsString()
        ]);

        // Activate emergency mode for critical errors
        if ($this->isCriticalError($e)) {
            EmergencyFallbackService::activateEmergencyMode("Critical error: {$errorClass}");
        }

        // Determine response strategy based on request type and error
        return $this->createErrorResponse($e, $request, $requestId);
    }

    /**
     * Create appropriate error response based on request type
     */
    private function createErrorResponse(Throwable $e, Request $request, string $requestId): Response
    {
        $isAjax = $request->ajax() || $request->wantsJson() || $request->expectsJson();
        $errorType = $this->categorizeError($e);
        
        if ($isAjax) {
            return $this->createJsonErrorResponse($e, $request, $requestId, $errorType);
        } else {
            return $this->createHtmlErrorResponse($e, $request, $requestId, $errorType);
        }
    }

    /**
     * Create JSON error response for AJAX requests
     */
    private function createJsonErrorResponse(Throwable $e, Request $request, string $requestId, array $errorType): Response
    {
        $response = [
            'success' => false,
            'error' => $errorType['user_message'],
            'error_code' => $errorType['code'],
            'request_id' => $requestId,
            'timestamp' => now()->toISOString()
        ];

        // Add emergency fallback if available
        if ($this->isQuizRequest($request) && EmergencyFallbackService::isEmergencyModeActive()) {
            $response['fallback_available'] = true;
            $response['fallback_message'] = 'Emergency quiz mode is available.';
            
            EmergencyFallbackService::logEmergencyUsage('quiz_error_fallback', [
                'error_type' => get_class($e),
                'route' => $request->route() ? $request->route()->getName() : 'unknown'
            ]);
        }

        // Add debug info in development
        if (config('app.debug') && config('app.env') !== 'production') {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage()
            ];
        }

        return response()->json($response, $errorType['http_status']);
    }

    /**
     * Create HTML error response for regular requests
     */
    private function createHtmlErrorResponse(Throwable $e, Request $request, string $requestId, array $errorType): Response
    {
        try {
            // Try to render appropriate error view
            $viewData = [
                'error' => $errorType['user_message'],
                'error_code' => $errorType['code'],
                'request_id' => $requestId,
                'status' => EmergencyFallbackService::getSystemStatusMessage(),
                'show_fallback' => EmergencyFallbackService::isEmergencyModeActive()
            ];

            $viewName = $this->getErrorViewName($errorType['http_status']);
            
            if (view()->exists($viewName)) {
                return response()->view($viewName, $viewData, $errorType['http_status']);
            } else {
                // Fallback to basic error response
                return response()->view('errors.generic', $viewData, $errorType['http_status']);
            }
            
        } catch (Exception $viewException) {
            // Ultimate fallback - plain HTML response
            return $this->createBasicHtmlResponse($errorType, $requestId);
        }
    }

    /**
     * Create basic HTML response when view rendering fails
     */
    private function createBasicHtmlResponse(array $errorType, string $requestId): Response
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Service Temporarily Unavailable</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-title { color: #e74c3c; font-size: 24px; margin-bottom: 20px; }
        .error-message { color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 30px; }
        .request-id { color: #999; font-size: 12px; }
        .retry-button { background: #3498db; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 20px 0; }
        .retry-button:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">Service Temporarily Unavailable</h1>
        <p class="error-message">' . htmlspecialchars($errorType['user_message']) . '</p>
        <a href="javascript:window.location.reload()" class="retry-button">Try Again</a>
        <p class="request-id">Request ID: ' . htmlspecialchars($requestId) . '</p>
    </div>
</body>
</html>';

        return response($html, $errorType['http_status'], ['Content-Type' => 'text/html']);
    }

    /**
     * Categorize error for appropriate handling
     */
    private function categorizeError(Throwable $e): array
    {
        $errorClass = get_class($e);
        
        // Database connection errors
        if (str_contains($errorClass, 'QueryException') || 
            str_contains($errorClass, 'PDOException') ||
            str_contains($e->getMessage(), 'database') ||
            str_contains($e->getMessage(), 'connection')) {
            return [
                'code' => 'DATABASE_ERROR',
                'user_message' => 'We\'re experiencing database connectivity issues. Please try again in a moment.',
                'http_status' => 503
            ];
        }

        // Cache errors
        if (str_contains($e->getMessage(), 'cache') || 
            str_contains($e->getMessage(), 'redis')) {
            return [
                'code' => 'CACHE_ERROR',
                'user_message' => 'Temporary performance issue. The system is still functional.',
                'http_status' => 200
            ];
        }

        // File system errors
        if (str_contains($e->getMessage(), 'file') || 
            str_contains($e->getMessage(), 'storage') ||
            str_contains($errorClass, 'FileNotFoundException')) {
            return [
                'code' => 'STORAGE_ERROR',
                'user_message' => 'File system issue detected. Please try uploading again.',
                'http_status' => 500
            ];
        }

        // Memory/timeout errors
        if (str_contains($e->getMessage(), 'memory') || 
            str_contains($e->getMessage(), 'timeout') ||
            str_contains($e->getMessage(), 'execution time')) {
            return [
                'code' => 'RESOURCE_ERROR',
                'user_message' => 'The request took too long to process. Please try again.',
                'http_status' => 503
            ];
        }

        // Validation errors
        if (str_contains($errorClass, 'ValidationException')) {
            return [
                'code' => 'VALIDATION_ERROR',
                'user_message' => 'Please check your input and try again.',
                'http_status' => 422
            ];
        }

        // Authentication/authorization errors
        if (str_contains($errorClass, 'AuthenticationException') || 
            str_contains($errorClass, 'AuthorizationException')) {
            return [
                'code' => 'AUTH_ERROR',
                'user_message' => 'Please log in again to continue.',
                'http_status' => 401
            ];
        }

        // Default error
        return [
            'code' => 'GENERAL_ERROR',
            'user_message' => 'An unexpected error occurred. Our team has been notified and is working on a fix.',
            'http_status' => 500
        ];
    }

    /**
     * Check if error is critical and requires emergency mode
     */
    private function isCriticalError(Throwable $e): bool
    {
        $criticalPatterns = [
            'PDOException',
            'database connection',
            'mysql server has gone away',
            'connection refused',
            'out of memory',
            'maximum execution time',
            'fatal error'
        ];

        $errorMessage = strtolower($e->getMessage());
        $errorClass = strtolower(get_class($e));

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($errorMessage, $pattern) || str_contains($errorClass, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a quiz-related request
     */
    private function isQuizRequest(Request $request): bool
    {
        $route = $request->route() ? $request->route()->getName() : '';
        $path = $request->path();

        return str_contains($route, 'quiz') || 
               str_contains($path, 'quiz') ||
               str_contains($path, 'grade');
    }

    /**
     * Get appropriate error view name
     */
    private function getErrorViewName(int $statusCode): string
    {
        $viewNames = [
            401 => 'errors.401',
            403 => 'errors.403',
            404 => 'errors.404',
            422 => 'errors.422',
            500 => 'errors.500',
            503 => 'errors.503'
        ];

        return $viewNames[$statusCode] ?? 'errors.generic';
    }

    /**
     * Log error statistics for monitoring
     */
    private function logErrorStatistics(Throwable $e, string $route): void
    {
        try {
            $errorKey = 'error_stats_' . date('Y-m-d-H');
            $stats = \Cache::get($errorKey, [
                'total_errors' => 0,
                'error_types' => [],
                'routes' => []
            ]);

            $stats['total_errors']++;
            
            $errorType = get_class($e);
            $stats['error_types'][$errorType] = ($stats['error_types'][$errorType] ?? 0) + 1;
            $stats['routes'][$route] = ($stats['routes'][$route] ?? 0) + 1;

            \Cache::put($errorKey, $stats, 3600); // 1 hour
        } catch (Exception $cacheException) {
            // Don't let statistics logging fail the error handling
        }
    }
}