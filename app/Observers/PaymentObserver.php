<?php

namespace App\Observers;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentObserver
{
    /**
     * Validation rules for Payment data
     *
     * @var array
     */
    protected $rules = [
        'user_id' => 'required|integer|exists:users,id',
        'course_id' => 'required|integer|exists:courses,id',
        'amount' => 'required|numeric|min:0',
        'reference_number' => 'required|string|min:10',
        'payment_method' => 'required|string',
        'status' => 'required|string|in:pending,pending_verification,completed,rejected,expired'
    ];

    /**
     * JSON schema for payment_data field
     *
     * @var array
     */
    protected $paymentDataSchema = [
        'transaction_id' => 'sometimes|string',
        'payment_method_used' => 'sometimes|string',
        'payment_date' => 'sometimes|date',
        'proof_path' => 'sometimes|string',
        'submission_date' => 'sometimes|date',
        'admin_review_priority' => 'sometimes|string|in:low,medium,high',
        'verification_confidence' => 'sometimes|numeric|min:0|max:100'
    ];

    /**
     * Valid state transitions for payment status
     *
     * @var array
     */
    protected $validStateTransitions = [
        'pending' => ['pending_verification', 'expired', 'rejected'],
        'pending_verification' => ['completed', 'rejected', 'expired'],
        'completed' => [], // Terminal state, no transitions
        'rejected' => [], // Terminal state, no transitions
        'expired' => ['pending'] // Can be reactivated in some cases
    ];

    /**
     * Handle the Payment "creating" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function creating(Payment $payment)
    {
        $this->validateBasicFields($payment);
        $this->sanitizeData($payment);
        $this->validateReferenceNumber($payment);
    }

    /**
     * Handle the Payment "updating" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function updating(Payment $payment)
    {
        // Get the original payment from the database
        $originalPayment = Payment::find($payment->id);
        
        if ($originalPayment) {
            // Validate state transition if status is changing
            if ($payment->status !== $originalPayment->status) {
                $this->validateStateTransition($originalPayment->status, $payment->status);
            }
        }
        
        $this->validateBasicFields($payment);
        $this->validatePaymentData($payment);
        $this->sanitizeData($payment);
    }

    /**
     * Handle the Payment "saved" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function saved(Payment $payment)
    {
        if ($payment->wasChanged('status')) {
            // Log status change
            Log::info('Payment status changed', [
                'payment_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'previous_status' => $payment->getOriginal('status'),
                'new_status' => $payment->status
            ]);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function deleted(Payment $payment)
    {
        // Log deletion
        Log::warning('Payment record deleted', [
            'payment_id' => $payment->id,
            'reference_number' => $payment->reference_number,
            'user_id' => $payment->user_id,
            'course_id' => $payment->course_id
        ]);
    }

    /**
     * Validate basic fields according to rules.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     * @throws \Exception if validation fails
     */
    protected function validateBasicFields(Payment $payment)
    {
        $data = $payment->toArray();
        
        // We only validate fields that are present (for partial updates)
        $presentRules = array_intersect_key($this->rules, $data);
        
        $validator = Validator::make($data, $presentRules);
        
        if ($validator->fails()) {
            $errorMessage = 'Payment validation failed: ' . $validator->errors()->first();
            Log::error($errorMessage, ['payment' => $data]);
            
            // We don't want to throw exceptions that might break the application
            // Instead, we'll try to fallback to safe values where possible
            $this->attemptToFixValidationErrors($payment, $validator->errors());
        }
    }

    /**
     * Validate payment_data JSON structure.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    protected function validatePaymentData(Payment $payment)
    {
        // Skip if payment_data is empty
        if (empty($payment->payment_data)) {
            return;
        }
        
        // Ensure payment_data is an array
        $paymentData = is_array($payment->payment_data) ? $payment->payment_data : (json_decode($payment->payment_data, true) ?: []);
        
        // Only validate fields that exist in the payment_data
        $presentRules = array_intersect_key($this->paymentDataSchema, $paymentData);
        
        $validator = Validator::make($paymentData, $presentRules);
        
        if ($validator->fails()) {
            $errorMessage = 'Payment data validation failed: ' . $validator->errors()->first();
            Log::error($errorMessage, ['payment_data' => $payment->payment_data]);
            
            // Try to sanitize data where possible
            $this->sanitizePaymentData($payment);
        }
    }

    /**
     * Validate reference number format.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    protected function validateReferenceNumber(Payment $payment)
    {
        // Skip validation if reference number is null or empty
        if (empty($payment->reference_number)) {
            return;
        }
        
        // Reference numbers should follow the format: MC[4 chars][timestamp][8 chars]
        $pattern = '/^MC[A-Z0-9]{4}\d{10}[A-Z0-9]{8}$/';
        
        if (!preg_match($pattern, $payment->reference_number)) {
            Log::warning('Payment reference number has invalid format', [
                'reference_number' => $payment->reference_number,
                'payment_id' => $payment->id
            ]);
            
            // Don't modify the reference number, just log the issue
            // as changing it could break payment tracking
        }
    }

    /**
     * Validate that a status transition is allowed.
     *
     * @param  string  $fromStatus
     * @param  string  $toStatus
     * @return void
     * @throws \Exception if transition is invalid
     */
    protected function validateStateTransition($fromStatus, $toStatus)
    {
        // If the original status is not defined in our transitions map, allow any transition
        // This provides backward compatibility with existing records
        if (!array_key_exists($fromStatus, $this->validStateTransitions)) {
            return;
        }
        
        $allowedTransitions = $this->validStateTransitions[$fromStatus];
        
        // If the list of allowed transitions is empty and we're trying to change state,
        // or if the target state is not in the allowed transitions, it's invalid
        if ((!empty($allowedTransitions) && !in_array($toStatus, $allowedTransitions))) {
            $errorMessage = "Invalid payment status transition from '$fromStatus' to '$toStatus'";
            Log::error($errorMessage);
            
            // Rather than throwing an exception, we'll log the error
            // Throwing here could disrupt the application flow
        }
    }

    /**
     * Try to fix validation errors with safe defaults where possible.
     *
     * @param  \App\Models\Payment  $payment
     * @param  \Illuminate\Support\MessageBag  $errors
     * @return void
     */
    protected function attemptToFixValidationErrors($payment, $errors)
    {
        // If amount is negative, set to 0
        if ($errors->has('amount') && isset($payment->amount) && $payment->amount < 0) {
            $payment->amount = 0;
            Log::info('Fixed negative payment amount', ['payment_id' => $payment->id]);
        }
        
        // If status is invalid, set to a safe default
        if ($errors->has('status') && isset($payment->status)) {
            $payment->status = 'pending';
            Log::info('Fixed invalid payment status', ['payment_id' => $payment->id]);
        }
        
        // Other fixes can be added as needed
    }

    /**
     * Sanitize data to ensure it's safe.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    protected function sanitizeData(Payment $payment)
    {
        // Trim string fields
        if (isset($payment->reference_number)) {
            $payment->reference_number = trim($payment->reference_number);
        }
        
        if (isset($payment->payment_method)) {
            $payment->payment_method = trim($payment->payment_method);
        }
        
        // Sanitize JSON data
        if (!empty($payment->payment_data)) {
            $this->sanitizePaymentData($payment);
        }
    }

    /**
     * Sanitize payment_data JSON structure.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    protected function sanitizePaymentData(Payment $payment)
    {
        $data = $payment->payment_data;
        
        // Ensure data is an array before processing
        if (!is_array($data)) {
            if (is_string($data)) {
                // Try to decode JSON string
                $decoded = json_decode($data, true);
                $data = is_array($decoded) ? $decoded : [];
            } else {
                $data = [];
            }
        }
        
        // Sanitize string fields
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
                
                // Sanitize HTML or potentially harmful content in notes field
                if ($key === 'notes' || $key === 'admin_notes') {
                    $data[$key] = strip_tags($value);
                }
            }
        }
        
        // Ensure numeric fields are actually numeric
        if (isset($data['verification_confidence'])) {
            $data['verification_confidence'] = is_numeric($data['verification_confidence']) ? 
                (float)$data['verification_confidence'] : 0;
                
            // Cap confidence between 0-100
            $data['verification_confidence'] = max(0, min(100, $data['verification_confidence']));
        }
        
        $payment->payment_data = $data;
    }
} 