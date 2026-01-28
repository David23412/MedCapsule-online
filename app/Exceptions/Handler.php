<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Session\TokenMismatchException;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Exceptions\QuizException;
use App\Exceptions\PaymentException;
use App\Exceptions\ValidationException;

/**
 * Global Exception Handler for MedCapsule
 * Provides centralized error handling and structured logging
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        // Custom exceptions with appropriate log levels
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'credit_card_number',
        'card_security_code',
        'api_key',
        'secret',
        'token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic can be added here
            // This is called for all exceptions, even if they're handled
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response|JsonResponse
    {
        // Handle CSRF token mismatch (419 errors) - CRITICAL FIX
        if ($e instanceof TokenMismatchException) {
            return $this->handleTokenMismatchException($request, $e);
        }

        // Handle custom exceptions
        if ($e instanceof QuizException) {
            return $this->handleQuizException($request, $e);
        }

        if ($e instanceof PaymentException) {
            return $this->handlePaymentException($request, $e);
        }

        if ($e instanceof ValidationException) {
            return $this->handleValidationException($request, $e);
        }

        // Handle Laravel's built-in validation exceptions
        if ($e instanceof LaravelValidationException) {
            return $this->handleLaravelValidationException($request, $e);
        }

        // Handle common HTTP exceptions
        if ($e instanceof NotFoundHttpException) {
            return $this->handleNotFoundException($request, $e);
        }

        if ($e instanceof AccessDeniedHttpException) {
            return $this->handleAccessDeniedException($request, $e);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->handleMethodNotAllowedException($request, $e);
        }

        // Default exception handling
        return parent::render($request, $e);
    }

    /**
     * Handle CSRF Token Mismatch (419 errors) - CRITICAL FIX
     * Gracefully redirect users instead of showing error page
     */
    protected function handleTokenMismatchException(Request $request, TokenMismatchException $e)
    {
        // Log the CSRF error for monitoring
        Log::warning('CSRF Token Mismatch - Redirecting user', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // If it's an AJAX request, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Session Expired',
                'message' => 'Your session has expired. Please refresh the page.',
                'code' => 'SESSION_EXPIRED',
                'redirect' => route('home'),
            ], 419);
        }

        // For regular requests, redirect to homepage with a friendly message
        // Clear the session and regenerate token for fresh start
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('warning', 'Your session expired. Please log in again if needed.')
            ->with('session_expired', true);
    }

    /**
     * Handle QuizException
     */
    protected function handleQuizException(Request $request, QuizException $e): JsonResponse
    {
        $this->logException($e, 'quiz_error', [
            'quiz_id' => $e->getContext()['quiz_id'] ?? null,
            'user_id' => $e->getContext()['user_id'] ?? null,
            'error_code' => $e->getCode(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Quiz Error',
            'message' => $this->getUserFriendlyMessage($e),
            'code' => 'QUIZ_ERROR',
            'details' => config('app.debug') ? $e->getContext() : null,
        ], $e->getCode());
    }

    /**
     * Handle PaymentException
     */
    protected function handlePaymentException(Request $request, PaymentException $e): JsonResponse
    {
        $this->logException($e, 'payment_error', [
            'payment_id' => $e->getContext()['payment_id'] ?? null,
            'user_id' => $e->getContext()['user_id'] ?? null,
            'amount' => $e->getContext()['amount'] ?? null,
            'error_code' => $e->getCode(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Payment Error',
            'message' => $this->getUserFriendlyMessage($e),
            'code' => 'PAYMENT_ERROR',
            'details' => config('app.debug') ? $e->getContext() : null,
        ], $e->getCode());
    }

    /**
     * Handle custom ValidationException
     */
    protected function handleValidationException(Request $request, ValidationException $e): JsonResponse
    {
        $this->logException($e, 'validation_error', [
            'errors' => $e->getErrors(),
            'validated_data_count' => count($e->getValidatedData()),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Validation Error',
            'message' => 'The given data was invalid.',
            'errors' => $e->getErrors(),
            'code' => 'VALIDATION_ERROR',
        ], 422);
    }

    /**
     * Handle Laravel's built-in ValidationException
     */
    protected function handleLaravelValidationException(Request $request, LaravelValidationException $e): JsonResponse
    {
        $this->logException($e, 'laravel_validation_error', [
            'errors' => $e->errors(),
            'failed_rules' => $e->failedRules ?? [],
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Validation Error',
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
            'code' => 'VALIDATION_ERROR',
        ], 422);
    }

    /**
     * Handle 404 Not Found exceptions
     */
    protected function handleNotFoundException(Request $request, NotFoundHttpException $e): JsonResponse
    {
        $this->logException($e, 'not_found', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Resource Not Found',
            'message' => 'The requested resource could not be found.',
            'code' => 'NOT_FOUND',
        ], 404);
    }

    /**
     * Handle 403 Access Denied exceptions
     */
    protected function handleAccessDeniedException(Request $request, AccessDeniedHttpException $e): JsonResponse
    {
        $this->logException($e, 'access_denied', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role ?? 'guest',
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Access Denied',
            'message' => 'You do not have permission to access this resource.',
            'code' => 'ACCESS_DENIED',
        ], 403);
    }

    /**
     * Handle 405 Method Not Allowed exceptions
     */
    protected function handleMethodNotAllowedException(Request $request, MethodNotAllowedHttpException $e): JsonResponse
    {
        $this->logException($e, 'method_not_allowed', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Method Not Allowed',
            'message' => 'The HTTP method is not supported for this endpoint.',
            'code' => 'METHOD_NOT_ALLOWED',
            'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
        ], 405);
    }

    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyMessage(Throwable $e): string
    {
        return match (true) {
            $e instanceof QuizException => $this->getQuizErrorMessage($e),
            $e instanceof PaymentException => $this->getPaymentErrorMessage($e),
            $e instanceof ValidationException => $e->getMessage(),
            default => $e->getMessage(),
        };
    }

    /**
     * Get user-friendly quiz error message
     */
    protected function getQuizErrorMessage(QuizException $e): string
    {
        return match ($e->getCode()) {
            404 => 'The requested quiz could not be found.',
            403 => 'You do not have permission to access this quiz.',
            409 => 'This quiz has already been completed.',
            408 => 'The quiz timed out. Please try again.',
            422 => 'The quiz configuration is invalid.',
            default => 'An error occurred with the quiz. Please try again.',
        };
    }

    /**
     * Get user-friendly payment error message
     */
    protected function getPaymentErrorMessage(PaymentException $e): string
    {
        return match ($e->getCode()) {
            402 => $this->getPaymentSpecificMessage($e),
            408 => 'Payment processing timed out. Please try again.',
            409 => 'This payment has already been processed.',
            503 => 'Payment service is temporarily unavailable. Please try again later.',
            422 => 'Invalid payment information provided.',
            default => 'An error occurred during payment processing.',
        };
    }

    /**
     * Get specific payment error messages
     */
    protected function getPaymentSpecificMessage(PaymentException $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'declined')) {
            return 'Your payment method was declined. Please try a different payment method.';
        }

        if (str_contains($message, 'Insufficient funds')) {
            return 'Insufficient funds in your account. Please check your balance or try a different payment method.';
        }

        if (str_contains($message, 'fraud')) {
            return 'Your payment was flagged for security reasons. Please contact support.';
        }

        return 'Payment processing failed. Please try again or contact support.';
    }

    /**
     * Log exception with structured data
     */
    protected function logException(Throwable $e, string $type, array $context = []): void
    {
        $logData = [
            'type' => $type,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email ?? 'guest',
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        $logData = array_merge($logData, $context);

        // Log to appropriate channel based on severity
        if ($e->getCode() >= 500) {
            Log::error("{$type}: {$e->getMessage()}", $logData);
        } elseif ($e->getCode() >= 400) {
            Log::warning("{$type}: {$e->getMessage()}", $logData);
        } else {
            Log::info("{$type}: {$e->getMessage()}", $logData);
        }

        // Additional logging for security events
        if (in_array($e->getCode(), [403, 401])) {
            Log::channel('security')->warning("Security event: {$type}", $logData);
        }
    }
}
