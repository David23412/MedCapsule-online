<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidatePaymentCache implements ShouldQueue
{
    use InteractsWithQueue;

    protected $cacheService;

    /**
     * Create the event listener.
     */
    public function __construct(UltraConsolidatedCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            $payment = $event->payment;
            $userId = $event->userId;
            $courseIds = $event->courseIds;

            // Clear course enrollment caches
            foreach ($courseIds as $courseId) {
                $this->cacheService->clearCourseEnrollmentCaches($courseId, $userId);
            }

            // Clear user progress caches for enrolled courses
            $this->cacheService->clearUserProgressCachesForCourses($userId, $courseIds);

            // Clear payment-specific caches
            $this->cacheService->clearPaymentCaches($payment->id, $userId);

            Log::info('Payment cache invalidated', [
                'payment_id' => $payment->id,
                'user_id' => $userId,
                'course_ids' => $courseIds,
                'amount' => $payment->amount,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating payment cache', [
                'payment_id' => $event->payment->id,
                'user_id' => $event->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 