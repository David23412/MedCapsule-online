<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentService
{
    /**
     * Validate if user has access to course
     *
     * @param User $user
     * @param Course $course
     * @return array
     */
    public function validateCourseAccess(User $user, Course $course): array
    {
        // Check if user already has access
        $hasAccess = $user->courses()
            ->where('course_id', $course->id)
            ->where('enrollment_status', 'active')
            ->exists();

        if ($hasAccess) {
            return [
                'success' => false,
                'message' => 'You already have access to this course.',
                'redirect' => route('topics.forCourse', $course->id)
            ];
        }
        
        // Check if course has a valid price configuration
        if ($course->price && $course->price > 0 && (!isset($course->price) || $course->price <= 0)) {
            return [
                'success' => false,
                'message' => 'This course has an invalid price configuration. Please contact support.'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Course access validation passed.'
        ];
    }

    /**
     * Validate transaction ID
     *
     * @param string|null $transactionId
     * @return array
     */
    public function validateTransactionId(?string $transactionId): array
    {
        $validator = Validator::make(['transaction_id' => $transactionId], [
            'transaction_id' => 'required|string|min:10|unique:payments,reference_number'
        ], [
            'transaction_id.required' => 'Please provide a transaction code.',
            'transaction_id.min' => 'Transaction code must be at least 10 characters long.',
            'transaction_id.unique' => 'This transaction code has already been used.'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first()
            ];
        }

        return [
            'success' => true,
            'message' => 'Transaction ID validation passed.'
        ];
    }

    /**
     * Create payment record
     *
     * @param User $user
     * @param Course $course
     * @param string $transactionId
     * @return Payment
     */
    public function createPaymentRecord(User $user, Course $course, string $transactionId): Payment
    {
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->course_id = $course->id;
        $payment->reference_number = $transactionId;
        $payment->amount = $course->price;
        $payment->payment_method = 'manual';
        $payment->status = 'pending';
        $payment->save();

        return $payment;
    }

    /**
     * Process payment verification
     *
     * @param Payment $payment
     * @param User $admin
     * @param string|null $adminNotes
     * @return array
     */
    public function verifyPayment(Payment $payment, User $admin, ?string $adminNotes = null): array
    {
        try {
            // Check if there's an active payment for this user and course
            $activePayment = Payment::where('user_id', $payment->user_id)
                ->where('course_id', $payment->course_id)
                ->where('status', 'completed')
                ->where('id', '!=', $payment->id)
                ->first();

            if ($activePayment) {
                // If there's an active payment, mark it as expired in payment_data
                $activePayment->payment_data = array_merge($activePayment->payment_data ?? [], [
                    'expired_at' => now()->toDateTimeString(),
                    'expired_by' => $admin->id,
                    'expiry_reason' => 'New payment verification'
                ]);
                $activePayment->save();
            }

            // Update payment status
            $payment->status = 'completed';
            $payment->paid_at = now();
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'verified_by' => $admin->id,
                'verified_at' => now()->toDateTimeString(),
                'admin_notes' => $adminNotes,
                'verification_method' => 'admin_manual',
                'is_renewal' => $activePayment ? true : false
            ]);
            $payment->save();

            // Grant course access to user
            $user = User::find($payment->user_id);
            $course = Course::find($payment->course_id);
            
            if ($user && $course) {
                // Attach the course with active status and enrollment date
                $user->courses()->syncWithoutDetaching([
                    $course->id => [
                        'enrollment_status' => 'active',
                        'enrolled_at' => now()
                    ]
                ]);
            }

            return [
                'success' => true,
                'message' => 'Payment verified successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'admin_id' => $admin->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Could not verify payment. Please try again.'
            ];
        }
    }

    /**
     * Process payment rejection
     *
     * @param Payment $payment
     * @param User $admin
     * @param string|null $rejectionReason
     * @return array
     */
    public function rejectPayment(Payment $payment, User $admin, ?string $rejectionReason = null): array
    {
        try {
            // Add admin rejection info
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'rejected_by' => $admin->id,
                'rejected_at' => now()->toDateTimeString(),
                'rejection_reason' => $rejectionReason ?? 'Payment rejected by admin',
                'admin_notes' => $rejectionReason
            ]);
            
            // Mark as rejected
            $payment->status = 'rejected';
            $payment->save();
            
            Log::info('Payment rejected by admin', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'course_id' => $payment->course_id,
                'admin_id' => $admin->id,
                'rejection_reason' => $rejectionReason
            ]);

            return [
                'success' => true,
                'message' => 'Payment rejected successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error rejecting payment: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'admin_id' => $admin->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Could not reject payment. Please try again.'
            ];
        }
    }

    /**
     * Get payment statistics for admin dashboard
     *
     * @return array
     */
    public function getPaymentStatistics(): array
    {
        return [
            'total_payments' => Payment::count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'completed_payments' => Payment::where('status', 'completed')->count(),
            'rejected_payments' => Payment::where('status', 'rejected')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'pending_revenue' => Payment::where('status', 'pending')->sum('amount'),
        ];
    }

    /**
     * Check for duplicate payments
     *
     * @param User $user
     * @param Course $course
     * @return bool
     */
    public function hasDuplicatePayment(User $user, Course $course): bool
    {
        return Payment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Get payment history for user
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserPaymentHistory(User $user, int $limit = 10)
    {
        return Payment::where('user_id', $user->id)
            ->with(['course:id,name,price'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}