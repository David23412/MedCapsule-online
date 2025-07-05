<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentStatusController extends Controller
{
    /**
     * Check payment status by reference number.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_number' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reference number',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = Payment::where('reference_number', $request->reference_number)->first();
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'status' => null
            ], 404);
        }
        
        // Check if the user is authorized to view this payment
        if ($request->user() && $request->user()->id !== $payment->user_id && !$request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'status' => null
            ], 403);
        }

        // Prepare response
        $response = [
            'success' => true,
            'status' => $payment->status,
            'status_description' => $this->getStatusDescription($payment->status),
            'reference_number' => $payment->reference_number,
            'amount' => $payment->amount,
            'course_id' => $payment->course_id,
            'created_at' => $payment->created_at->toDateTimeString()
        ];
        
        // Add additional fields based on status
        if ($payment->status === 'pending_verification') {
            $response['verification_in_progress'] = true;
            $response['submitted_at'] = $payment->payment_data['submission_date'] ?? null;
            $response['verification_attempts'] = $payment->payment_data['verification_attempts'] ?? 0;
        }
        
        if ($payment->status === 'completed') {
            $response['paid_at'] = $payment->paid_at ? $payment->paid_at->toDateTimeString() : null;
        }
        
        if ($payment->status === 'rejected') {
            $response['rejection_reason'] = $payment->payment_data['rejection_reason'] ?? 'Payment information could not be verified';
        }

        return response()->json($response);
    }
    
    /**
     * Get a human-readable description of a payment status.
     *
     * @param  string  $status
     * @return string
     */
    protected function getStatusDescription($status)
    {
        $descriptions = [
            'pending' => 'Your payment is pending. Please complete the payment using the provided reference number.',
            'pending_verification' => 'Your payment proof has been submitted and is currently being verified.',
            'completed' => 'Your payment has been successfully verified. You now have access to the course.',
            'rejected' => 'Your payment was rejected. Please check the rejection reason for more information.',
            'expired' => 'This payment reference has expired. Please generate a new payment.'
        ];
        
        return $descriptions[$status] ?? 'Unknown status';
    }
} 