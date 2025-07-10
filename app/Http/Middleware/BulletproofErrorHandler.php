<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;

class BulletproofErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (TokenMismatchException $e) {
            return $this->handleCsrfError($request, $e);
        } catch (AuthenticationException $e) {
            return $this->handleAuthError($request, $e);
        } catch (ValidationException $e) {
            return $this->handleValidationError($request, $e);
        } catch (ModelNotFoundException $e) {
            return $this->handleNotFoundError($request, $e);
        } catch (NotFoundHttpException $e) {
            return $this->handleNotFoundError($request, $e);
        } catch (MethodNotAllowedHttpException $e) {
            return $this->handleMethodNotAllowedError($request, $e);
        } catch (Exception $e) {
            return $this->handleGenericError($request, $e);
        } catch (\Throwable $e) {
            return $this->handleFatalError($request, $e);
        }
    }

    /**
     * Handle CSRF token mismatch errors
     */
    protected function handleCsrfError(Request $request, TokenMismatchException $e)
    {
        Log::warning('CSRF token mismatch detected', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'referer' => $request->header('referer'),
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Session expired. Please refresh the page.',
                'error_code' => 'CSRF_TOKEN_MISMATCH',
                'redirect' => $request->url()
            ], 419);
        }

        return redirect()->back()
            ->with('error', 'Your session has expired. Please try again.')
            ->withInput($request->except(['_token', 'password']));
    }

    /**
     * Handle authentication errors
     */
    protected function handleAuthError(Request $request, AuthenticationException $e)
    {
        Log::info('Authentication required', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required.',
                'error_code' => 'AUTHENTICATION_REQUIRED',
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')
            ->with('info', 'Please log in to continue.');
    }

    /**
     * Handle validation errors
     */
    protected function handleValidationError(Request $request, ValidationException $e)
    {
        Log::debug('Validation error', [
            'url' => $request->fullUrl(),
            'errors' => $e->errors(),
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'The provided data is invalid.',
                'error_code' => 'VALIDATION_ERROR',
                'validation_errors' => $e->errors()
            ], 422);
        }

        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();
    }

    /**
     * Handle not found errors
     */
    protected function handleNotFoundError(Request $request, Exception $e)
    {
        Log::info('Resource not found', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'The requested resource was not found.',
                'error_code' => 'RESOURCE_NOT_FOUND'
            ], 404);
        }

        return response()->view('errors.404', [
            'message' => 'The page you are looking for could not be found.'
        ], 404);
    }

    /**
     * Handle method not allowed errors
     */
    protected function handleMethodNotAllowedError(Request $request, MethodNotAllowedHttpException $e)
    {
        Log::warning('Method not allowed', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'allowed_methods' => $e->getHeaders()['Allow'] ?? 'Unknown',
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'The request method is not allowed for this resource.',
                'error_code' => 'METHOD_NOT_ALLOWED',
                'allowed_methods' => $e->getHeaders()['Allow'] ?? null
            ], 405);
        }

        return redirect()->route('home')
            ->with('error', 'Invalid request method.');
    }

    /**
     * Handle generic errors
     */
    protected function handleGenericError(Request $request, Exception $e)
    {
        $errorId = uniqid('err_');
        
        Log::error('Application error', [
            'error_id' => $errorId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => config('app.debug') 
                    ? $e->getMessage() 
                    : 'An unexpected error occurred. Please try again.',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'error_id' => $errorId,
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }

        return response()->view('errors.500', [
            'message' => config('app.debug') 
                ? $e->getMessage() 
                : 'Something went wrong. Our team has been notified.',
            'error_id' => $errorId
        ], 500);
    }

    /**
     * Handle fatal errors and throwables
     */
    protected function handleFatalError(Request $request, \Throwable $e)
    {
        $errorId = uniqid('fatal_');
        
        Log::critical('Fatal error', [
            'error_id' => $errorId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]);

        // Try to send a simple response
        try {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'A critical error occurred. Please contact support.',
                    'error_code' => 'FATAL_ERROR',
                    'error_id' => $errorId
                ], 500);
            }

            return response('
                <html>
                <head><title>System Error</title></head>
                <body style="font-family: Arial; text-align: center; padding: 50px;">
                    <h1>System Temporarily Unavailable</h1>
                    <p>We are experiencing technical difficulties. Please try again in a few moments.</p>
                    <p><small>Error ID: ' . $errorId . '</small></p>
                    <a href="/" style="color: #007bff; text-decoration: none;">← Return to Home</a>
                </body>
                </html>
            ', 500);
        } catch (\Throwable $fallbackError) {
            // Last resort - plain text response
            return response('System Error - Please contact support. Error ID: ' . $errorId, 500);
        }
    }
}