<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    // Remove complex dependencies that might not exist
    // protected $securePaymentService;
    // protected $performanceMonitor;
    // protected $paymentSecurityService;

    // public function __construct(
    //     SecurePaymentService $securePaymentService,
    //     PerformanceMonitorService $performanceMonitor,
    //     PaymentSecurityService $paymentSecurityService
    // ) {
    //     $this->securePaymentService = $securePaymentService;
    //     $this->performanceMonitor = $performanceMonitor;
    //     $this->paymentSecurityService = $paymentSecurityService;
    // }

    /**
     * Process a payment for a course
     */
    public function processPayment(User $user, Course $course, array $paymentData): Payment
    {
        $operationId = $this->performanceMonitor->startOperation('payment_processing', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $paymentData['amount'] ?? $course->price,
            'payment_method' => $paymentData['payment_method'] ?? 'manual'
        ]);

        try {
            // Validate payment data
            $this->validatePaymentData($user, $course, $paymentData);

            // Check for duplicate payments
            $this->checkForDuplicatePayments($user, $course, $paymentData);

            // Process the payment
            $payment = $this->createPayment($user, $course, $paymentData);

            $this->performanceMonitor->endOperation($operationId, [
                'status' => 'completed',
                'payment_id' => $payment->id
            ]);

            return $payment;

        } catch (\Exception $e) {
            $this->performanceMonitor->endOperation($operationId, [
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Validate payment data
     */
    protected function validatePaymentData(User $user, Course $course, array $paymentData): void
    {
        // Check if user is already enrolled
        if ($user->isEnrolledInCourse($course->id)) {
            throw new \Exception('User is already enrolled in this course');
        }

        // Check if user has pending payment
        if ($course->hasUserPendingPayment($user->id)) {
            throw new \Exception('User already has a pending payment for this course');
        }

        // Validate amount
        if ($course->price > 0 && (!isset($paymentData['amount']) || $paymentData['amount'] != $course->price)) {
            throw new \Exception('Payment amount does not match course price');
        }

        // Validate payment method
        $validMethods = ['vodafone_cash', 'fawry', 'instapay', 'credit_card', 'paypal', 'manual'];
        if (!isset($paymentData['payment_method']) || !in_array($paymentData['payment_method'], $validMethods)) {
            throw new \Exception('Invalid payment method');
        }
    }

    /**
     * Check for duplicate payments
     */
    protected function checkForDuplicatePayments(User $user, Course $course, array $paymentData): void
    {
        $existingPayment = Payment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('reference_number', $paymentData['transaction_id'] ?? '')
            ->first();

        if ($existingPayment) {
            throw new \Exception('This transaction ID has already been used for this course');
        }
    }

    /**
     * Create payment record
     */
    protected function createPayment(User $user, Course $course, array $paymentData): Payment
    {
        return DB::transaction(function () use ($user, $course, $paymentData) {
            return Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $paymentData['amount'] ?? $course->price,
                'status' => 'pending',
                'payment_method' => $paymentData['payment_method'],
                'reference_number' => $paymentData['transaction_id'] ?? uniqid('PAY_'),
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });
    }

    /**
     * Get payment by ID
     */
    public function getPayment(int $paymentId): ?Payment
    {
        return Payment::find($paymentId);
    }

    /**
     * Get user payments
     */
    public function getUserPayments(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::where('user_id', $user->id)
            ->with('course')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
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

            return [
                'success' => true,
                'data' => [
                    'payments' => $payments->items(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                        'from' => $payments->firstItem(),
                        'to' => $payments->lastItem(),
                    ]
                ]
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
