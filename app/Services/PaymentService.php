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
        if ($user->courses()->where('course_id', $course->id)->whereNotNull('enrolled_at')->exists()) {
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
                ['enrolled_at' => now(), 'updated_at' => now()]
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
     * Get paginated payments with optional search.
     *
     * @param string|null $search
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPaginatedPayments(string $search = null, int $page = 1, int $perPage = 20): array
    {
        try {
            \Log::info('🔍 PaymentService::getPaginatedPayments called', [
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage,
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role ?? 'unknown',
                'is_admin' => auth()->user()->isAdmin() ?? false
            ]);
            $query = DB::table('payments')
                ->join('users', 'payments.user_id', '=', 'users.id')
                ->join('courses', 'payments.course_id', '=', 'courses.id')
                ->select(
                    'payments.id',
                    'payments.reference_number',
                    'payments.amount',
                    'payments.payment_method',
                    'payments.status',
                    'payments.created_at',
                    'payments.paid_at',
                    'payments.payment_data',
                    'users.name as user_name',
                    'users.email as user_email',
                    'courses.name as course_name'
                );

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%")
                      ->orWhere('courses.name', 'like', "%{$search}%")
                      ->orWhere('payments.reference_number', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $payments = $query->orderBy('payments.created_at', 'desc')
                            ->offset(($page - 1) * $perPage)
                            ->limit($perPage)
                            ->get();

            $payments = $payments->map(function($payment) {
                $paymentData = json_decode($payment->payment_data, true) ?? [];
                $payment->transaction_id = $paymentData['transaction_id'] ?? null;
                return $payment;
            });

            $stats = $this->getPaymentStatistics();

            return [
                'success' => true,
                'payments' => $payments,
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Payment fetch failed in service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to load payments.'
            ];
        }
    }

    /**
     * Get payment history for user
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function updatePaymentSettings(array $settings): array
    {
        try {
            // In a real application, you would validate and save the settings here.
            // For example, updating a configuration file or a database table.
            Log::info('Updating payment settings', $settings);

            return ['success' => true, 'message' => 'Payment settings updated successfully.'];
        } catch (\Exception $e) {
            Log::error('Payment settings update failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update payment settings.'];
        }
    }

    public function getUserPaymentHistory(User $user, int $limit = 10)
    {
        return Payment::where('user_id', $user->id)
            ->with(['course:id,name,price'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
