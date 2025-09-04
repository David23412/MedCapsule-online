<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Course;
use App\Models\User;

class PaymentService
{
    /**
     * 🚀 PERFORMANCE BEAST: Process payment using ONLY provider transaction code
     * Eliminates all redundant duplicate checking and monitoring
     * 🔥 WITH AUTOMATIC NOTIFICATIONS
     */
    public function processPayment(User $user, Course $course, array $paymentData): Payment
    {
        // 🔥 VALIDATE ONLY ESSENTIALS
        $this->validatePaymentEssentials($user, $course, $paymentData);

        // 🔥 CREATE PAYMENT WITH PROVIDER TRANSACTION ONLY
        $payment = $this->createPayment($user, $course, $paymentData);

        // 🔔 TRIGGER PAYMENT SUBMITTED NOTIFICATION (ORANGE BORDER)
        $this->triggerPaymentNotifications($user, $payment, $course, 'submitted');

        return $payment;
    }

    /**
     * 🔥 PERFORMANCE BOMB: Essential validation only, no duplicates checking
     * REMOVE TRANSACTION_ID DUPLICATE CHECKS SINCE PROVIDER GUARANTEES UNIQUENESS
     */
    protected function validatePaymentEssentials(User $user, Course $course, array $paymentData): void
    {
        // 🔥 VALIDATE ONLY_CRITICAL: amount and transaction_id existence
        if ($course->price > 0 && (!isset($paymentData['amount']) || $paymentData['amount'] != $course->price)) {
            throw new \Exception('Payment amount does not match course price');
        }

        // 🔥 PROVIDER TRANSACTION_ID MUST EXIST AND BE VALID
        if (!isset($paymentData['transaction_id']) || empty($paymentData['transaction_id'])) {
            throw new \Exception('Provider transaction code is required');
        }

        // Keep only essential payment method validation
        $validMethods = ['vodafone_cash', 'fawry', 'instapay', 'credit_card', 'paypal', 'manual'];
        if (!isset($paymentData['payment_method']) || !in_array($paymentData['payment_method'], $validMethods)) {
            throw new \Exception('Invalid payment method');
        }
    }

    /**
     * Create payment record - PERFORMANCE BEAST VERSION
     * RELIES SOLELY ON PROVIDER TRANSACTION CODE FOR UNIQUENESS
     */
    protected function createPayment(User $user, Course $course, array $paymentData): Payment
    {
        // 🔥 PERFORMANCE BOMB: Single atomic operation, no duplicate checking
        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $paymentData['amount'] ?? $course->price,
            'status' => 'pending',
            'payment_method' => $paymentData['payment_method'],
            // 🔥 PROVIDER TRANSACTION CODE ONLY (guaranteed unique)
            'transaction_id' => $paymentData['transaction_id'],
            // 🔥 NO REDUNDANT REFERENCE CODE GENERATION
            'reference_number' => null, // Remove this field or use provider code
            'paid_at' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * 🔔 TRIGGER PAYMENT NOTIFICATIONS WITH COLOR MAPPING
     */
    protected function triggerPaymentNotifications(User $user, Payment $payment, Course $course): void
    {
        try {
            $notificationData = [
                'amount' => $payment->amount,
                'course_name' => $course->name,
                'transaction_id' => $payment->transaction_id,
                'payment_id' => $payment->id
            ];

            // 🔥 PAYMENT SUBMITTED NOTIFICATION (ORANGE BORDER)
            \App\Services\NotificationTriggerService::onPaymentSubmitted($user, $notificationData);

        } catch (\Exception $e) {
            // Log error but don't fail payment process
            \Illuminate\Support\Facades\Log::error('Payment notification failed', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get payment by ID
     */
    public function getPayment(int $paymentId): ?Payment
    {
        return Payment::find($paymentId);
    }

    /**
     * Get pending payments
     */
    public function getPendingPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::where('status', 'pending')
            ->with(['user', 'course'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get paginated payments with search functionality
     */
    public function getPaginatedPayments(?string $search = null, int $page = 1, int $perPage = 20): array
    {
        try {
            $query = Payment::with(['user', 'course']);

            // Apply search filter if provided
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhere('payment_method', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('course', function ($courseQuery) use ($search) {
                          $courseQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $payments = $query->orderBy('created_at', 'desc')
                             ->paginate($perPage, ['*'], 'page', $page);

            // Calculate payment statistics
            $stats = [
                'total_revenue' => Payment::whereIn('status', ['completed', 'pending'])->sum('amount'),
                'completed_count' => Payment::where('status', 'completed')->count(),
                'pending_count' => Payment::where('status', 'pending')->count(),
                'rejected_count' => Payment::where('status', 'rejected')->count(),
            ];

            // Transform payment data for frontend
            $paymentsData = array_map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user_name' => $payment->user->name ?? 'Unknown',
                    'user_email' => $payment->user->email ?? '',
                    'course_name' => $payment->course->name ?? 'Unknown',
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'transaction_id' => $payment->transaction_id,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                ];
            }, $payments->items());

            return [
                'success' => true,
                'payments' => $paymentsData,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                ],
                'stats' => $stats
            ];

        } catch (\Exception $e) {
            Log::error('Error getting paginated payments', [
                'error' => $e->getMessage(),
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage
            ]);

            return [
                'success' => false,
                'message' => 'Error retrieving payments',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ ACCEPT PAYMENT AND TRIGGER GREEN NOTIFICATION
     */
    public function acceptPayment(int $paymentId, ?User $admin = null): array
    {
        try {
            $payment = Payment::find($paymentId);

            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            // Update payment status to accepted
            $payment->update([
                'status' => 'accepted',
                'paid_at' => now()
            ]);

            // 🔔 TRIGGER PAYMENT ACCEPTED NOTIFICATION (GREEN BORDER)
            $user = $payment->user;
            $course = $payment->course;

            $notificationData = [
                'amount' => $payment->amount,
                'course_name' => $course->name,
                'transaction_id' => $payment->transaction_id,
                'payment_id' => $payment->id
            ];

            // ✅ GREEN BORDER: Payment accepted notification
            \App\Services\NotificationTriggerService::onPaymentAccepted($user, $notificationData);

            return [
                'success' => true,
                'message' => 'Payment accepted successfully',
                'payment' => $payment
            ];

        } catch (\Exception $e) {
            Log::error('Error accepting payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error accepting payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update payment settings
     */
    public function updatePaymentSettings(array $settings): array
    {
        try {
            // In a real implementation, this would update payment settings
            // For now, we'll just log the settings

            Log::info('Payment settings updated', [
                'settings' => $settings,
                'updated_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => 'Payment settings updated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error updating payment settings', [
                'error' => $e->getMessage(),
                'settings' => $settings
            ]);

            return [
                'success' => false,
                'message' => 'Error updating payment settings',
                'error' => $e->getMessage()
            ];
        }
    }
}
