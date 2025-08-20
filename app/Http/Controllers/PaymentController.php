<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Get payment options for a single course.
     */
    public function getPaymentOptions(Course $course)
    {
        $user = Auth::user();

        // Check if user already has access
        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have access to this course.',
                'redirect' => route('topics.forCourse', $course->id)
            ]);
        }

        // Generate payment options HTML
        $html = view('payments.single_course_payment_methods', compact('course'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Payment options loaded successfully.',
            'html' => $html
        ]);
    }

    /**
     * Process manual payment for a single course.
     */
    public function processManualPayment(Request $request, Course $course)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'payment_method' => 'required|string|in:vodafone_cash,fawry,instapay',
            'transaction_id' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Check if user already has a pending payment for this course
            $existingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['pending', 'under_review'])
                ->first();

            if ($existingPayment) {
                // Update existing payment
                $existingPayment->update([
                    'payment_method' => $validated['payment_method'],
                    'reference_number' => $validated['transaction_id'],
                    'status' => 'under_review',
                    'paid_at' => now()
                ]);
                $payment = $existingPayment;
            } else {
                // Create new payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'reference_number' => $validated['transaction_id'],
                    'status' => 'under_review',
                    'paid_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment submitted successfully and is under review.',
                'payment_id' => $payment->id
            ]);

        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
