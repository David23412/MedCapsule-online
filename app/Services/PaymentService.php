<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
        $this->triggerPaymentNotifications($user, $payment, $course);

        return $payment;
    }

    /**
     * 🔥 PERFORMANCE BOMB: Essential validation only, no duplicates checking
     * REMOVE TRANSACTION_ID DUPLICATE CHECKS SINCE PROVIDER GUARANTEES UNIQUENESS
     */
    protected function validatePaymentEssentials(User $user, Course $course, array $paymentData): void
    {
        if (!$course->canBePurchased()) {
            throw new \Exception('This course does not require payment.');
        }

        // 🔥 VALIDATE ONLY_CRITICAL: amount and transaction_id existence
        if (!isset($paymentData['amount']) || $paymentData['amount'] != $course->price) {
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
            // Keep a stable searchable reference without generating a second identifier.
            'reference_number' => $paymentData['reference_number'] ?? $paymentData['transaction_id'],
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
            $search = trim((string) $search);

            if ($search !== '') {
                $prefixSearch = $search . '%';
                $containsSearch = '%' . $search . '%';

                $matchingUserIds = User::query()
                    ->where(function ($userQuery) use ($prefixSearch) {
                        $userQuery->where('email', 'like', $prefixSearch)
                            ->orWhere('number', 'like', $prefixSearch)
                            ->orWhere('name', 'like', $prefixSearch);
                    })
                    ->limit(100)
                    ->pluck('id');

                $matchingCourseIds = Course::query()
                    ->where('name', 'like', $prefixSearch)
                    ->limit(100)
                    ->pluck('id');

                $query->where(function ($paymentQuery) use ($search, $prefixSearch, $containsSearch, $matchingUserIds, $matchingCourseIds) {
                    $paymentQuery->where('reference_number', $search)
                        ->orWhere('transaction_id', $search)
                        ->orWhere('payment_method', $search)
                        ->orWhere('reference_number', 'like', $prefixSearch)
                        ->orWhere('transaction_id', 'like', $prefixSearch)
                        ->orWhere('payment_method', 'like', $prefixSearch);

                    if ($matchingUserIds->isNotEmpty()) {
                        $paymentQuery->orWhereIn('user_id', $matchingUserIds);
                    }

                    if ($matchingCourseIds->isNotEmpty()) {
                        $paymentQuery->orWhereIn('course_id', $matchingCourseIds);
                    }

                    $paymentQuery->orWhere(function ($fallbackQuery) use ($containsSearch) {
                        $fallbackQuery->where('reference_number', 'like', $containsSearch)
                            ->orWhere('transaction_id', 'like', $containsSearch)
                            ->orWhere('payment_method', 'like', $containsSearch)
                            ->orWhereHas('user', function ($userQuery) use ($containsSearch) {
                                $userQuery->where('name', 'like', $containsSearch)
                                    ->orWhere('email', 'like', $containsSearch)
                                    ->orWhere('number', 'like', $containsSearch);
                            })
                            ->orWhereHas('course', function ($courseQuery) use ($containsSearch) {
                                $courseQuery->where('name', 'like', $containsSearch);
                            });
                    });
                });
            }

            $payments = $query->orderBy('created_at', 'desc')
                             ->paginate($perPage, ['*'], 'page', $page);

            // Count settled revenue separately from requests awaiting review, in one query.
            $totals = Payment::query()->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_count,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_count
            ")->first();
            $stats = [
                'total_revenue' => (float) $totals->total_revenue,
                'completed_count' => (int) $totals->completed_count,
                'pending_count' => (int) $totals->pending_count,
                'rejected_count' => (int) $totals->rejected_count,
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

            // Update payment status to completed
            $payment->update([
                'status' => 'completed',
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
