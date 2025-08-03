<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Streamlined payment creation
     */
    public function createPayment(User $user, Course $course, array $data): Payment
    {
        // Check if already enrolled
        if ($user->courses()->where('course_id', $course->id)->where('is_enrolled', true)->exists()) {
            throw new \Exception('Already enrolled in this course');
        }

        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'reference_number' => $data['reference_number'],
            'amount' => $course->price,
            'payment_method' => $data['payment_method'],
            'status' => 'pending',
            'payment_data' => json_encode($data)
        ]);
    }

    /**
     * Streamlined payment verification with auto-enrollment
     */
    public function verifyPayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            // Update payment
            $payment->update(['status' => 'completed', 'paid_at' => now()]);
            
            // Auto-enroll user
            DB::table('course_user')->updateOrInsert(
                ['user_id' => $payment->user_id, 'course_id' => $payment->course_id],
                ['is_enrolled' => true, 'enrolled_at' => now(), 'updated_at' => now()]
            );
            
            return true;
        });
    }

    /**
     * Streamlined payment rejection
     */
    public function rejectPayment(Payment $payment, string $reason = null): bool
    {
        return $payment->update([
            'status' => 'rejected',
            'payment_data' => array_merge($payment->payment_data ?? [], [
                'rejection_reason' => $reason ?? 'Rejected by admin'
            ])
        ]);
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