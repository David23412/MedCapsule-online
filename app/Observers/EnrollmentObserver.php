<?php

namespace App\Observers;

use App\Support\State\EnrollmentService;
use Illuminate\Support\Facades\Log;

/**
 * Enrollment Observer - Dual-Write Synchronization
 * 
 * Handles synchronization between legacy enrollment fields and SSOT:
 * - course_user.is_enrolled (boolean) ↔ EnrollmentService
 * - course_user.enrolled_at (datetime) ↔ EnrollmentService
 * - payments.status ↔ EnrollmentService
 * 
 * @todo Remove course_user.is_enrolled after all reads migrated to EnrollmentService (target: 2025-09-15)
 * @todo Remove direct enrollment checks after PaymentService integration (target: 2025-09-10)
 */
class EnrollmentObserver
{
    private EnrollmentService $enrollmentService;
    private static array $syncInProgress = [];

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Handle course_user table updates
     */
    public function updatingCourseUser($courseUser): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.enrollment.enabled')) {
            return;
        }

        $syncKey = "course_user_{$courseUser->user_id}_{$courseUser->course_id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        $original = $courseUser->getOriginal();
        
        // Check for legacy field changes
        if ($courseUser->isDirty('is_enrolled') || $courseUser->isDirty('enrolled_at')) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleLegacyEnrollmentChange($courseUser, $original);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle payment status updates
     */
    public function updatedPayment($payment): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.enrollment.enabled')) {
            return;
        }

        $syncKey = "payment_{$payment->user_id}_{$payment->course_id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        if ($payment->wasChanged('status') && $payment->status === 'completed') {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handlePaymentCompletion($payment);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle legacy enrollment field changes
     */
    private function handleLegacyEnrollmentChange($courseUser, array $original): void
    {
        $userId = $courseUser->user_id;
        $courseId = $courseUser->course_id;
        
        // Get current SSOT status
        $ssotStatus = $this->enrollmentService->getEnrollmentStatus($userId, $courseId);
        
        // Check for conflicts
        $newIsEnrolled = (bool) $courseUser->is_enrolled;
        $newEnrolledAt = $courseUser->enrolled_at;
        
        if ($ssotStatus['enrolled'] !== $newIsEnrolled) {
            Log::warning('SSOT Enrollment Conflict Detected', [
                'type' => 'LEGACY_WRITE_CONFLICT',
                'model' => 'course_user',
                'user_id' => $userId,
                'course_id' => $courseId,
                'legacy_is_enrolled' => $newIsEnrolled,
                'ssot_enrolled' => $ssotStatus['enrolled'],
                'legacy_enrolled_at' => $newEnrolledAt,
                'ssot_enrolled_at' => $ssotStatus['enrolled_at'],
                'original_is_enrolled' => $original['is_enrolled'] ?? null,
                'original_enrolled_at' => $original['enrolled_at'] ?? null,
                'conflict_resolution' => 'SSOT_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);
        }

        // Log legacy write operation
        Log::info('SSOT Legacy Enrollment Write Detected', [
            'type' => 'LEGACY_WRITE',
            'model' => 'course_user',
            'user_id' => $userId,
            'course_id' => $courseId,
            'changed_fields' => [
                'is_enrolled' => [
                    'old' => $original['is_enrolled'] ?? null,
                    'new' => $courseUser->is_enrolled
                ],
                'enrolled_at' => [
                    'old' => $original['enrolled_at'] ?? null,
                    'new' => $courseUser->enrolled_at
                ]
            ],
            'sync_action' => 'VALIDATE_AGAINST_SSOT',
            'timestamp' => now()
        ]);

        // If enrollment is being set to true, ensure SSOT consistency
        if ($newIsEnrolled && !$ssotStatus['enrolled']) {
            // This might indicate a direct database write - sync to SSOT
            if ($newEnrolledAt) {
                $this->enrollmentService->enrollUser($userId, $courseId, 'legacy_sync');
                
                Log::info('SSOT Enrollment Synced from Legacy', [
                    'type' => 'LEGACY_TO_SSOT_SYNC',
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'enrolled_at' => $newEnrolledAt,
                    'sync_method' => 'legacy_sync'
                ]);
            }
        }
    }

    /**
     * Handle payment completion and sync enrollment
     */
    private function handlePaymentCompletion($payment): void
    {
        $userId = $payment->user_id;
        $courseId = $payment->course_id;
        
        Log::info('SSOT Payment Completion Detected', [
            'type' => 'PAYMENT_COMPLETION',
            'payment_id' => $payment->id,
            'user_id' => $userId,
            'course_id' => $courseId,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at,
            'sync_action' => 'AUTO_ENROLL'
        ]);

        // Process enrollment through SSOT service
        $enrolled = $this->enrollmentService->processPaymentEnrollment($payment);
        
        if ($enrolled) {
            Log::info('SSOT Enrollment Created from Payment', [
                'type' => 'SSOT_WRITE',
                'payment_id' => $payment->id,
                'user_id' => $userId,
                'course_id' => $courseId,
                'enrollment_method' => 'payment_completion',
                'enrolled_at' => now()
            ]);
        } else {
            Log::warning('SSOT Enrollment Failed from Payment', [
                'type' => 'SSOT_WRITE_FAILED',
                'payment_id' => $payment->id,
                'user_id' => $userId,
                'course_id' => $courseId,
                'failure_reason' => 'payment_not_completed_or_already_enrolled'
            ]);
        }
    }

    /**
     * Sync SSOT changes back to legacy fields
     */
    public function syncSSOTToLegacy(int $userId, int $courseId, array $ssotStatus): void
    {
        if (!config('ssot.sync_enabled') || !in_array('ssot_to_legacy', config('ssot.observers.enrollment.sync_directions'))) {
            return;
        }

        $syncKey = "ssot_sync_{$userId}_{$courseId}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        self::$syncInProgress[$syncKey] = true;
        
        try {
            // Update course_user table to match SSOT
            $updated = \DB::table('course_user')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->update([
                    'is_enrolled' => $ssotStatus['enrolled'],
                    'enrolled_at' => $ssotStatus['enrolled_at'],
                    'updated_at' => now()
                ]);

            if ($updated) {
                Log::info('SSOT Legacy Fields Synced', [
                    'type' => 'SSOT_TO_LEGACY_SYNC',
                    'model' => 'course_user',
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'ssot_enrolled' => $ssotStatus['enrolled'],
                    'ssot_enrolled_at' => $ssotStatus['enrolled_at'],
                    'sync_method' => $ssotStatus['method'],
                    'synced_at' => now()
                ]);
            }
        } finally {
            unset(self::$syncInProgress[$syncKey]);
        }
    }

    /**
     * Verify enrollment consistency
     */
    public function verifyConsistency(int $userId, int $courseId): array
    {
        $ssotStatus = $this->enrollmentService->getEnrollmentStatus($userId, $courseId);
        
        $legacyRecord = \DB::table('course_user')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        $inconsistencies = [];
        
        if ($legacyRecord) {
            $legacyEnrolled = (bool) $legacyRecord->is_enrolled;
            $legacyEnrolledAt = $legacyRecord->enrolled_at;
            
            if ($legacyEnrolled !== $ssotStatus['enrolled']) {
                $inconsistencies[] = [
                    'field' => 'is_enrolled',
                    'legacy_value' => $legacyEnrolled,
                    'ssot_value' => $ssotStatus['enrolled'],
                    'conflict_type' => 'boolean_mismatch'
                ];
            }
            
            if ($legacyEnrolledAt !== $ssotStatus['enrolled_at']?->toDateTimeString()) {
                $inconsistencies[] = [
                    'field' => 'enrolled_at',
                    'legacy_value' => $legacyEnrolledAt,
                    'ssot_value' => $ssotStatus['enrolled_at']?->toDateTimeString(),
                    'conflict_type' => 'timestamp_mismatch'
                ];
            }
        }

        return [
            'consistent' => empty($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'ssot_status' => $ssotStatus,
            'legacy_record' => $legacyRecord,
            'checked_at' => now()
        ];
    }
}