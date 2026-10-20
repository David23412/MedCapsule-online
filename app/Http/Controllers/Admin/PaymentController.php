<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationTriggerService;

class PaymentController extends Controller
{
    /**
     * Display admin payment history
     */
    public function adminPaymentHistory()
    {
        $payments = Payment::with(['user', 'course'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.payments.history', compact('payments'));
    }

    /**
     * Display admin payment details
     */
    public function adminPaymentDetails(Payment $payment)
    {
        $payment->load(['user', 'course']);

        return view('admin.payments.details', compact('payment'));
    }

    /**
     * Accept a pending payment - Simple and straightforward
     */
    public function acceptPayment(Payment $payment)
    {
        return $this->resolvePayment($payment, 'completed');
    }

    public function rejectPayment(Payment $payment)
    {
        return $this->resolvePayment($payment, 'rejected');
    }

    private function resolvePayment(Payment $payment, string $status)
    {
        try {
            $changed = DB::transaction(function () use ($payment, $status) {
                $current = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($current->status === $status) {
                    return false;
                }
                if ($current->status !== 'pending') {
                    throw new \DomainException('This payment has already been processed. Refresh the payment list.');
                }
                if ($status === 'completed') {
                    $user = User::whereKey($current->user_id)->lockForUpdate()->firstOrFail();
                    $user->enrollInCourse($current->course_id, User::COURSE_ACCESS_FULL);
                    $current->paid_at = now();
                }
                $current->status = $status;
                $current->save();
                return true;
            }, 3);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            Log::error('Payment decision failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Payment was not updated. Please try again.'], 500);
        }

        $this->clearUserCourseAccessCache($payment->user_id);
        if ($changed && $status === 'completed') {
            try {
                NotificationTriggerService::onPaymentAccepted($payment->user, [
                    'amount' => $payment->amount,
                    'course_name' => $payment->course->name ?? 'Course',
                    'transaction_id' => $payment->reference_number,
                ]);
            } catch (\Throwable $e) {
                // Notification delivery must not misreport an already committed approval as failed.
                Log::error('Payment approval notification failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            }
        }
        return response()->json([
            'success' => true,
            'message' => $status === 'completed' ? 'Payment accepted. User enrolled in course.' : 'Payment rejected.',
        ]);
    }

    /**
     * Get payment options for a specific course
     */
    public function getPaymentOptions(Course $course)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get the authenticated user
        $user = auth()->user();

        if (!$course->canBePurchased()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment is not configured for this course.'
            ], 400);
        }

        if (!$user->isAdmin() && ($course->hasUserPaid($user->id) || $course->hasUserPendingPayment($user->id))) {
            return response()->json([
                'success' => false,
                'message' => 'You have already paid for this course or have a pending payment'
            ], 400);
        }

        // Course details for payment modal
        $courseData = [
            'id' => $course->id,
            'name' => $course->name,
            'price' => $course->price,
            'formatted_price' => $course->formatted_price,
            'currency' => $course->currency ?? 'EGP',
            'image' => $course->image_url
        ];

        // Render the payment modal view
        $html = view('payments.single_course_payment_methods', ['course' => $courseData])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'course' => $courseData
        ]);
    }

    /**
     * Process manual payment submission
     */
    public function processManualPayment(Request $request, Course $course)
    {
        try {
            // Check if user is authenticated
            if (!auth()->check()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $user = auth()->user();

            if (!$course->canBePurchased()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course does not require payment.'
                ], 400);
            }

            if (!$user->isAdmin() && ($course->hasUserPaid($user->id) || $course->hasUserPendingPayment($user->id))) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already paid for this course or have a pending payment'
                ], 400);
            }

            // Validate the request
            $request->validate([
                'payment_method' => 'required|string|in:instapay',
                'transaction_id' => 'required|string|min:1|max:255',
                'amount' => 'required|numeric|min:0'
            ]);

            // Check if amount matches course price
            if ($request->amount != $course->price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount does not match course price'
                ], 400);
            }

            // Check if transaction_id is unique for this course
            $existingPayment = Payment::where('course_id', $course->id)
                ->where('reference_number', $request->transaction_id)
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction ID has already been used for this course'
                ], 400);
            }

            // Create the payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $request->amount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'reference_number' => $request->transaction_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->clearUserCourseAccessCache($user->id);

            // Log the payment submission

            // 🔔 TRIGGER PAYMENT SUBMITTED NOTIFICATION
            NotificationTriggerService::onPaymentSubmitted($user, [
                'amount' => $request->amount,
                'course_name' => $course->name,
                'transaction_id' => $request->transaction_id,
                'payment_method' => $request->payment_method
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment submitted successfully. We will verify it shortly.',
                'payment_id' => $payment->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['transaction_id'] ?? ['Invalid data'])
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'course_id' => $course->id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again.'
            ], 500);
        }
    }

    private function clearUserCourseAccessCache(int $userId): void
    {
        Cache::forget("user_course_optimization_data_{$userId}");
    }
}
