<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class PaymentSecurityService
{
    /**
     * Check if user has exceeded payment rate limit
     */
    public function checkRateLimit(string $userId, string $courseId): bool
    {
        $key = "payment_attempts:{$userId}:{$courseId}";

        $attempts = Cache::get($key, 0);

        // Allow maximum 3 payment attempts per course per user per hour
        if ($attempts >= 3) {
            return false;
        }

        // Increment attempts and set expiry (1 hour)
        Cache::put($key, $attempts + 1, 3600);

        return true;
    }

    /**
     * Validate payment amount
     */
    public function validateAmount(float $amount): bool
    {
        // Amount must be positive and not exceed reasonable limits
        if ($amount <= 0 || $amount > 10000) {
            return false;
        }

        return true;
    }

    /**
     * Check for suspicious payment patterns
     */
    public function detectSuspiciousActivity(string $userId, array $paymentData): array
    {
        $warnings = [];

        // Check for rapid successive payments
        $recentPayments = Cache::get("user_payments:{$userId}", []);
        $recentPayments[] = time();
        $recentPayments = array_slice($recentPayments, -5); // Keep last 5 payments

        Cache::put("user_payments:{$userId}", $recentPayments, 300); // 5 minutes

        if (count($recentPayments) >= 3) {
            $timeDiff = end($recentPayments) - $recentPayments[0];
            if ($timeDiff < 60) { // Less than 1 minute for 3 payments
                $warnings[] = 'Rapid payment attempts detected';
            }
        }

        // Check for unusual amounts
        if (isset($paymentData['amount']) && $paymentData['amount'] > 1000) {
            $warnings[] = 'High payment amount detected';
        }

        return $warnings;
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $data): void
    {
        Log::warning('Payment Security Event', [
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Validate transaction ID format
     */
    public function validateTransactionId(string $transactionId): bool
    {
        // Basic validation - should not be empty and contain reasonable characters
        if (empty($transactionId) || strlen($transactionId) < 3 || strlen($transactionId) > 100) {
            return false;
        }

        // Check for potentially malicious characters
        if (preg_match('/[<>\'";]/', $transactionId)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user is blocked from payments.
     *
     * Uses a per-user cache key instead of fetching and scanning a shared list,
     * eliminating the previous O(n) in_array() scan and full-list deserialisation.
     */
    public function isUserBlocked(string $userId): bool
    {
        return Cache::has("payment_blocked:{$userId}");
    }

    /**
     * Block user from payments (temporary).
     *
     * Stores the block as an individual cache key per user so that:
     *  - isUserBlocked() is a single O(1) lookup, not a full-list fetch + in_array scan.
     *  - Concurrent calls cannot race and lose each other's writes (no read-modify-write cycle).
     */
    public function blockUser(string $userId, int $durationMinutes = 60): void
    {
        Cache::put("payment_blocked:{$userId}", true, $durationMinutes * 60);

        $this->logSecurityEvent('user_blocked', [
            'user_id' => $userId,
            'duration_minutes' => $durationMinutes
        ]);
    }

    /**
     * Generate secure payment reference
     */
    public function generateSecureReference(): string
    {
        return 'PAY_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Validate payment method
     */
    public function validatePaymentMethod(string $method): bool
    {
        $validMethods = [
            'vodafone_cash',
            'fawry',
            'instapay',
            'credit_card',
            'paypal',
            'manual'
        ];

        return in_array($method, $validMethods);
    }

    /**
     * Clean up expired rate limits and blocks
     */
    public function cleanup(): void
    {
        // This would be called by a scheduled job to clean up expired entries
        // For now, we'll rely on Cache TTL
        Cache::forget('cleanup_last_run');
    }
}
