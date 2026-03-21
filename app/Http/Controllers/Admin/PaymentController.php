<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // Check if payment is pending
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is not pending'
            ]);
        }

        // Update payment to completed
        $payment->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);

        // Enroll user in course
        $payment->user->enrollInCourse($payment->course_id, \App\Models\User::COURSE_ACCESS_FULL);

        // 🔔 TRIGGER PAYMENT ACCEPTED NOTIFICATION
        NotificationTriggerService::onPaymentAccepted($payment->user, [
            'amount' => $payment->amount,
            'course_name' => $payment->course->name ?? 'Course',
            'transaction_id' => $payment->reference_number
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment accepted. User enrolled in course.'
        ]);
    }

    /**
     * Reject a pending payment - Simple and straightforward
     */
    public function rejectPayment(Payment $payment)
    {
        // Check if payment is pending
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is not pending'
            ]);
        }

        // Update payment to rejected
        $payment->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Payment rejected.'
        ]);
    }

    /**
     * Get payment statistics for dashboard
     */
    public function getPaymentStats()
    {
        if (!optional(auth()->user())->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'pending' => Payment::where('status', 'pending')->count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'rejected' => Payment::where('status', 'rejected')->count(),
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            'recent_payments' => Payment::with(['user', 'course'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json($stats);
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

        // Check if user is admin or if course is free
        if (!$user->isAdmin() && $course->price > 0) {
            // For non-free courses, check if user already paid or has pending payment
            if ($course->hasUserPaid($user->id) || $course->hasUserPendingPayment($user->id)) {
                return response()->json([
                    'error' => 'You have already paid for this course or have a pending payment'
                ], 400);
            }
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

            // Check if user is admin or if course is free
            if (!$user->isAdmin() && $course->price > 0) {
                // For non-free courses, check if user already paid or has pending payment
                if ($course->hasUserPaid($user->id) || $course->hasUserPendingPayment($user->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have already paid for this course or have a pending payment'
                    ], 400);
                }
            }

            // Validate the request
            $request->validate([
                'payment_method' => 'required|string|in:vodafone_cash,fawry,instapay',
                'transaction_id' => 'required|string|min:1|max:255',
                'amount' => 'required|numeric|min:0'
            ]);

            // Check if amount matches course price (for non-free courses)
            if ($course->price > 0 && $request->amount != $course->price) {
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

            // Log the payment submission
            Log::info('Payment submitted', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id
            ]);

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
}
