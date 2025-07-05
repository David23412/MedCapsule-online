<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentLogService;

/**
 * Independent Verification Pipeline
 *
 * This class provides a completely dependency-free verification pipeline
 * that can continue functioning even when external services fail.
 * It orchestrates multiple verification methods with fallbacks.
 */
class IndependentVerificationPipeline
{
    /**
     * Available verification methods in order of preference
     *
     * @var array
     */
    protected $verifiers = [];

    /**
     * Operational status of individual verifiers
     *
     * @var array
     */
    protected $operationalStatus = [];

    /**
     * Processing lock timeout in seconds
     * 
     * @var int
     */
    protected $processingLockTimeout = 30;

    /**
     * Payment log service
     * 
     * @var PaymentLogService|null
     */
    protected $logService;

    /**
     * Constructor
     *
     * @param TransactionPatternMatcher|null $patternMatcher
     * @param PaymentLogService|null $logService
     */
    public function __construct(
        TransactionPatternMatcher $patternMatcher = null,
        PaymentLogService $logService = null
    ) {
        // Core pattern matcher (lowest dependency, most reliable)
        $this->verifiers['pattern'] = $patternMatcher ?? new TransactionPatternMatcher();
        $this->operationalStatus['pattern'] = true;

        // Set up other verifiers if available (will be checked later)
        $this->operationalStatus['ocr'] = false;
        $this->operationalStatus['metadata'] = true; // Metadata validation always available as it's local
        
        // Payment logging service (optional, will fall back to direct logging if unavailable)
        $this->logService = $logService;
    }

    /**
     * Verify a payment using all available methods with automatic fallbacks
     *
     * @param Payment $payment
     * @param string $transactionId
     * @param string|null $proofImagePath
     * @return array Verification results
     */
    public function verify(Payment $payment, string $transactionId, ?string $proofImagePath = null)
    {
        // Initialize the result structure
        $result = [
            'verified' => false,
            'confidence' => 0,
            'verification_methods' => [],
            'fallbacks_used' => false,
            'errors' => [],
            'data' => [
                'transaction_id' => $transactionId,
                'payment_id' => $payment->id,
                'has_proof_image' => !empty($proofImagePath)
            ],
        ];

        try {
            // Use database transaction to prevent race conditions
            return DB::transaction(function() use ($payment, $transactionId, $proofImagePath, $result) {
                // Reload payment with FOR UPDATE lock to prevent concurrent processing
                $lockedPayment = Payment::lockForUpdate()->find($payment->id);
                
                if (!$lockedPayment) {
                    $result['errors'][] = 'Payment not found after locking';
                    return $result;
                }
                
                // Check if payment is already being processed by another worker
                if ($this->isAlreadyProcessing($lockedPayment)) {
                    $result['errors'][] = 'Payment is already being processed by another worker';
                    $this->logVerificationAttempt($lockedPayment, $result, 'concurrent_processing_detected');
                    return $result;
                }
                
                // Mark payment as being processed
                $this->markAsProcessing($lockedPayment);
                
                // First check basic payment validity
                if ($this->isPaymentInvalidState($lockedPayment)) {
                    $result['errors'][] = 'Payment is in an invalid state for verification';
                    $this->clearProcessingFlag($lockedPayment);
                    $this->logVerificationAttempt($lockedPayment, $result, 'invalid_payment_state');
                    return $result;
                }

                // Log start of verification attempt
                $this->logVerificationAttempt($lockedPayment, [
                    'action' => 'verification_started',
                    'transaction_id' => $transactionId,
                    'has_image' => !empty($proofImagePath)
                ], 'verification_started');

                // Standalone transaction pattern verification (always available)
                $patternResult = $this->performPatternVerification($lockedPayment, $transactionId);
                $result['verification_methods']['pattern'] = $patternResult;

                // Local metadata verification (always available)
                $metadataResult = $this->performMetadataVerification($lockedPayment, $transactionId);
                $result['verification_methods']['metadata'] = $metadataResult;

                // Try OCR verification if proof image is available (may fail gracefully)
                if ($proofImagePath && $this->isOcrAvailable()) {
                    try {
                        $ocrResult = $this->performOcrVerification($lockedPayment, $proofImagePath);
                        $result['verification_methods']['ocr'] = $ocrResult;
                    } catch (\Exception $e) {
                        // OCR failed, but we can continue with other verification methods
                        $this->operationalStatus['ocr'] = false;
                        $result['fallbacks_used'] = true;
                        $result['errors'][] = 'OCR verification failed: ' . $e->getMessage();
                        Log::warning('OCR verification failed, using fallback methods', [
                            'payment_id' => $lockedPayment->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Log OCR failure
                        $this->logError($lockedPayment, $e, 'ocr_verification_failed');
                    }
                }

                // Calculate final confidence score from all available methods
                $confidenceScore = $this->calculateConfidenceScore($result['verification_methods']);
                $result['confidence'] = $confidenceScore;

                // Determine if payment is verified based on confidence score
                $threshold = $this->getApprovalThreshold();
                $result['verified'] = $confidenceScore >= $threshold;

                // If verification was successful, mark the payment as paid
                if ($result['verified']) {
                    $this->markPaymentAsVerified($lockedPayment, $result);
                    $this->logAutoVerification($lockedPayment, $result);
                } else {
                    // Clear the processing flag if not verified
                    $this->clearProcessingFlag($lockedPayment);
                    
                    // Log failed verification attempt with full details
                    $this->logVerificationAttempt($lockedPayment, $result, 'verification_failed');
                }

                return $result;
            }, 3); // Retry transaction up to 3 times if deadlock occurs
        } catch (\Exception $e) {
            // Catch any unexpected errors to ensure the verification process always returns a result
            Log::error('Unexpected error in verification pipeline', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log error for auditing
            $this->logError($payment, $e, 'verification_system_error');

            // Try to clear processing flag in case of error
            try {
                $this->clearProcessingFlag($payment);
            } catch (\Exception $clearEx) {
                Log::error('Failed to clear processing flag after error', [
                    'payment_id' => $payment->id,
                    'error' => $clearEx->getMessage()
                ]);
            }

            $result['errors'][] = 'An unexpected error occurred during verification: ' . $e->getMessage();
            $result['verified'] = false;
            return $result;
        }
    }

    /**
     * Check if payment is already being processed by another worker
     *
     * @param Payment $payment
     * @return bool
     */
    protected function isAlreadyProcessing(Payment $payment)
    {
        // Check if payment has a processing flag and if it's still valid
        $processingData = $payment->payment_data['processing'] ?? null;
        
        if (!$processingData) {
            return false;
        }
        
        // Check if processing started_at timestamp is within the timeout window
        $startedAt = \Carbon\Carbon::parse($processingData['started_at']);
        $hasTimedOut = $startedAt->diffInSeconds(now()) > $this->processingLockTimeout;
        
        // If processing has timed out, consider it not processing anymore
        if ($hasTimedOut) {
            Log::warning('Found stale processing lock on payment', [
                'payment_id' => $payment->id,
                'started_at' => $processingData['started_at'],
                'timeout_seconds' => $this->processingLockTimeout
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Mark payment as being processed
     *
     * @param Payment $payment
     * @return void
     */
    protected function markAsProcessing(Payment $payment)
    {
        $processingData = [
            'processing' => [
                'started_at' => now()->toDateTimeString(),
                'worker_id' => uniqid('worker_'),
                'timeout_at' => now()->addSeconds($this->processingLockTimeout)->toDateTimeString()
            ]
        ];
        
        $payment->payment_data = array_merge($payment->payment_data ?? [], $processingData);
        $payment->save();
        
        Log::info('Payment marked as processing', [
            'payment_id' => $payment->id,
            'worker_id' => $processingData['processing']['worker_id']
        ]);
    }
    
    /**
     * Clear processing flag from payment
     *
     * @param Payment $payment
     * @return void
     */
    protected function clearProcessingFlag(Payment $payment)
    {
        if (isset($payment->payment_data['processing'])) {
            $processingData = $payment->payment_data['processing'];
            
            // Keep processing history but mark as completed
            $payment->payment_data['processing_history'][] = array_merge(
                $processingData,
                ['completed_at' => now()->toDateTimeString()]
            );
            
            // Remove current processing flag
            unset($payment->payment_data['processing']);
            $payment->save();
            
            Log::info('Processing flag cleared from payment', [
                'payment_id' => $payment->id
            ]);
        }
    }

    /**
     * Check if payment is in a valid state for verification
     *
     * @param Payment $payment
     * @return bool
     */
    protected function isPaymentInvalidState(Payment $payment)
    {
        // Payments can only be verified if they are in pending_verification state
        if ($payment->status !== 'pending_verification') {
            Log::warning('Payment in invalid state for verification', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
                'expected_status' => 'pending_verification'
            ]);
            return true;
        }

        // Check for expiration
        $expiryHours = config('payment.reference_expiry_hours', 24);
        if ($payment->created_at->diffInHours(now()) > $expiryHours) {
            Log::warning('Payment reference expired', [
                'payment_id' => $payment->id,
                'created_at' => $payment->created_at,
                'expiry_hours' => $expiryHours
            ]);
            return true;
        }

        return false;
    }

    /**
     * Perform transaction pattern verification (core method, always available)
     *
     * @param Payment $payment
     * @param string $transactionId
     * @return array
     */
    protected function performPatternVerification(Payment $payment, string $transactionId)
    {
        // Extract payment method from payment data
        $paymentMethod = $payment->payment_data['payment_method_used'] ?? '';

        // Prepare context data for verification
        $context = [];
        if (isset($payment->payment_data['payment_date'])) {
            $context['payment_date'] = $payment->payment_data['payment_date'];
        }
        if (isset($payment->payment_data['submission_date'])) {
            $context['submission_date'] = $payment->payment_data['submission_date'];
        }

        // Perform pattern verification
        $result = $this->verifiers['pattern']->validate($transactionId, $paymentMethod, $context);

        return [
            'valid' => $result['valid'],
            'confidence' => $result['confidence'],
            'score' => $result['score'],
            'max_score' => $result['max_score'],
            'checks' => $result['checks'],
            'errors' => $result['errors']
        ];
    }

    /**
     * Perform metadata verification (simple check against payment amount and user history)
     *
     * @param Payment $payment
     * @param string $transactionId
     * @return array
     */
    protected function performMetadataVerification(Payment $payment, string $transactionId)
    {
        $result = [
            'valid' => false,
            'score' => 0,
            'max_score' => 3,
            'checks' => []
        ];

        // Check 1: Course price matches payment amount
        try {
            if ($payment->course && $payment->course->price == $payment->amount) {
                $result['score'] += 1;
                $result['checks']['price_match'] = true;
            } else {
                $result['checks']['price_match'] = false;
            }
        } catch (\Exception $e) {
            // If course relationship fails, skip this check
            $result['checks']['price_match'] = false;
            $result['checks']['price_match_error'] = $e->getMessage();
        }

        // Check 2: Reference number is present in payment data
        if (isset($payment->payment_data['reference_number']) && 
            $payment->payment_data['reference_number'] === $payment->reference_number) {
            $result['score'] += 1;
            $result['checks']['reference_match'] = true;
        } else {
            $result['checks']['reference_match'] = false;
        }

        // Check 3: User has provided contact information
        try {
            if ($payment->user && !empty($payment->user->email) && !empty($payment->user->phone)) {
                $result['score'] += 1;
                $result['checks']['user_contact'] = true;
            } else {
                $result['checks']['user_contact'] = false;
            }
        } catch (\Exception $e) {
            // If user relationship fails, skip this check
            $result['checks']['user_contact'] = false;
            $result['checks']['user_contact_error'] = $e->getMessage();
        }

        // Calculate confidence percentage
        $result['confidence'] = ($result['score'] / $result['max_score']) * 100;
        $result['valid'] = $result['score'] >= 2; // Need at least 2 out of 3 checks to pass

        return $result;
    }

    /**
     * Perform OCR verification if available
     *
     * @param Payment $payment
     * @param string $proofImagePath
     * @return array
     */
    protected function performOcrVerification(Payment $payment, string $proofImagePath)
    {
        // Check if OCR is enabled in config or use default (true)
        $ocrEnabled = config('payment.ocr.enabled', true);
        if (!$ocrEnabled) {
            return [
                'valid' => false,
                'confidence' => 0,
                'score' => 0,
                'max_score' => 10,
                'disabled' => true
            ];
        }

        try {
            // We'll try to get the validator service but with a timeout
            $validator = app(PaymentReceiptValidator::class);
            
            // Add a 5-second timeout to prevent hanging if OCR process gets stuck
            $result = $this->withTimeout(5, function() use ($validator, $proofImagePath, $payment) {
                return $validator->validateReceipt($proofImagePath, $payment);
            });
            
            $this->operationalStatus['ocr'] = true;
            
            return [
                'valid' => $result['is_valid'] ?? false,
                'confidence' => $result['confidence'] ?? 0,
                'score' => $result['score'] ?? 0,
                'max_score' => $result['max_score'] ?? 10,
                'extracted_data' => $result['extracted_data'] ?? []
            ];
        } catch (\Exception $e) {
            // OCR failed, mark as non-operational
            $this->operationalStatus['ocr'] = false;
            
            // Return a result that indicates OCR failed but doesn't cause the whole pipeline to fail
            return [
                'valid' => false,
                'confidence' => 0,
                'score' => 0,
                'max_score' => 10,
                'error' => $e->getMessage(),
                'failed' => true
            ];
        }
    }

    /**
     * Execute a callback with a timeout to prevent hanging
     *
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    protected function withTimeout($seconds, callable $callback)
    {
        // For PHP 7.1+ we could use pcntl_alarm, but for maximum compatibility
        // we'll use set_time_limit if available
        
        $originalLimit = ini_get('max_execution_time');
        
        try {
            // Set a new time limit if possible
            if (function_exists('set_time_limit')) {
                set_time_limit($seconds);
            }
            
            // Execute the callback
            return $callback();
        } finally {
            // Restore original time limit if possible
            if (function_exists('set_time_limit') && $originalLimit) {
                set_time_limit($originalLimit);
            }
        }
    }

    /**
     * Calculate final confidence score based on all available verification methods
     *
     * @param array $verificationMethods
     * @return float
     */
    protected function calculateConfidenceScore(array $verificationMethods)
    {
        $totalScore = 0;
        $maxScore = 0;
        $weights = [
            'pattern' => 5,    // 50% from pattern verification
            'metadata' => 2,   // 20% from metadata verification
            'ocr' => 3         // 30% from OCR (if available)
        ];

        // Apply weights to each verification method's score
        foreach ($weights as $method => $weight) {
            if (isset($verificationMethods[$method])) {
                // Get the confidence score for this method (0-100)
                $methodConfidence = $verificationMethods[$method]['confidence'] ?? 0;
                
                // Apply weighting to this method's confidence
                $totalScore += ($methodConfidence / 100) * $weight;
                $maxScore += $weight;
            }
        }

        // If no scores were found, return 0
        if ($maxScore === 0) {
            return 0;
        }

        // Return weighted confidence percentage
        return ($totalScore / $maxScore) * 100;
    }

    /**
     * Get the approval threshold based on config or default
     *
     * @return float
     */
    protected function getApprovalThreshold()
    {
        try {
            return config('payment.verification.auto_approval_threshold', 70);
        } catch (\Exception $e) {
            // If config fails, use a conservative default
            return 70;
        }
    }

    /**
     * Check if OCR verification is available
     *
     * @return bool
     */
    protected function isOcrAvailable()
    {
        // Check if OCR validator is registered in the container
        try {
            return app()->bound(PaymentReceiptValidator::class) && 
                   $this->operationalStatus['ocr'] &&
                   config('payment.ocr.enabled', true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Mark a payment as verified
     *
     * @param Payment $payment
     * @param array $verificationResults
     * @return bool
     */
    protected function markPaymentAsVerified(Payment $payment, array $verificationResults)
    {
        try {
            // Prepare verification details
            $verificationDetails = [
                'verified_by' => 'system_independent',
                'verified_at' => now()->toDateTimeString(),
                'verification_method' => 'independent_pipeline',
                'verification_results' => $verificationResults,
                'confidence' => $verificationResults['confidence'] ?? null,
                'admin_notes' => 'Automatically verified by independent system with ' . 
                                round($verificationResults['confidence'] ?? 0) . '% confidence'
            ];

            // Update payment status and data
            $payment->status = 'completed';
            $payment->paid_at = now();
            
            // Merge in verification details but keep processing history
            $processingHistory = $payment->payment_data['processing_history'] ?? [];
            
            // Remove processing flag and add completion data
            if (isset($payment->payment_data['processing'])) {
                $processingHistory[] = array_merge(
                    $payment->payment_data['processing'],
                    ['completed_at' => now()->toDateTimeString(), 'result' => 'verified']
                );
            }
            
            // Build the new payment data
            $paymentData = array_merge($payment->payment_data ?? [], $verificationDetails);
            
            // Remove current processing flag
            unset($paymentData['processing']);
            
            // Add processing history 
            $paymentData['processing_history'] = $processingHistory;
            
            // Update payment data
            $payment->payment_data = $paymentData;
            $payment->save();
            
            // Attempt to enroll the user in the course if possible
            try {
                if (isset($payment->user) && isset($payment->course)) {
                    $payment->user->courses()->syncWithoutDetaching([$payment->course_id]);
                }
            } catch (\Exception $e) {
                // Log enrollment error but don't fail the verification
                Log::error('Course enrollment failed after payment verification', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark payment as verified', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Log an automatic verification
     * 
     * @param Payment $payment
     * @param array $verificationResults
     * @return void
     */
    protected function logAutoVerification(Payment $payment, array $verificationResults)
    {
        try {
            if ($this->logService) {
                $this->logService->logAutoVerification($payment, $verificationResults);
            } else {
                // Fallback to direct logging if service unavailable
                Log::info('Payment automatically verified', [
                    'payment_id' => $payment->id,
                    'confidence' => $verificationResults['confidence'],
                    'methods_used' => array_keys($verificationResults['verification_methods'])
                ]);
            }
        } catch (\Exception $e) {
            // Never fail the verification due to logging issues
            Log::warning('Failed to log auto verification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log a verification attempt
     * 
     * @param Payment $payment
     * @param array $data
     * @param string $context Additional context about the verification attempt
     * @return void
     */
    protected function logVerificationAttempt(Payment $payment, array $data, string $context = '')
    {
        try {
            if ($this->logService) {
                $data['context'] = $context;
                $this->logService->logVerificationAttempt($payment, $data);
            } else {
                // Fallback to direct logging if service unavailable
                Log::info('Payment verification attempt', [
                    'payment_id' => $payment->id,
                    'context' => $context,
                    'data' => $data
                ]);
            }
        } catch (\Exception $e) {
            // Never fail the verification due to logging issues
            Log::warning('Failed to log verification attempt', [
                'payment_id' => $payment->id,
                'context' => $context,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log an error during verification
     * 
     * @param Payment $payment
     * @param \Exception $error
     * @param string $context
     * @return void
     */
    protected function logError(Payment $payment, \Exception $error, string $context = '')
    {
        try {
            if ($this->logService) {
                $this->logService->logError($payment, $error, ['context' => $context]);
            } else {
                // Fallback to direct logging if service unavailable
                Log::error('Payment verification error', [
                    'payment_id' => $payment->id,
                    'context' => $context,
                    'error' => $error->getMessage(),
                    'trace' => $error->getTraceAsString()
                ]);
            }
        } catch (\Exception $e) {
            // Last resort logging if everything else fails
            Log::emergency('Critical error logging failure', [
                'payment_id' => $payment->id,
                'original_error' => $error->getMessage(),
                'logging_error' => $e->getMessage()
            ]);
        }
    }
} 