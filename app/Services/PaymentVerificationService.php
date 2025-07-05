<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentVerificationService
{
    /**
     * @var PaymentReceiptValidator
     */
    protected $receiptValidator;

    /**
     * Create a new service instance.
     *
     * @param PaymentReceiptValidator $receiptValidator
     */
    public function __construct(PaymentReceiptValidator $receiptValidator = null)
    {
        $this->receiptValidator = $receiptValidator;
    }

    /**
     * Verify a payment using multiple verification methods
     *
     * @param Payment $payment
     * @param string $transactionId
     * @param string|null $proofImagePath
     * @return array
     */
    public function verifyPayment(Payment $payment, string $transactionId, ?string $proofImagePath = null)
    {
        $results = [
            'verified' => false,
            'confidence' => 0,
            'verification_methods' => [],
            'reasons' => [],
            'data' => [],
        ];

        // Check if the reference has expired
        if ($this->isReferenceExpired($payment)) {
            $results['reasons'][] = 'Payment reference has expired';
            return $results;
        }

        // Initialize verification score
        $score = 0;
        $maxScore = 10;

        // METHOD 1: Transaction ID Pattern Validation
        $patternVerification = $this->verifyTransactionIdPattern($payment, $transactionId);
        $results['verification_methods']['pattern'] = $patternVerification;
        $results['data']['transaction_id'] = $transactionId;

        if ($patternVerification['valid']) {
            $score += 5; // Pattern validation is worth 5 points (out of 10)
        }

        // METHOD 2: OCR Receipt Validation (if enabled and available)
        $ocrResults = null;
        if (config('payment.ocr.enabled', true) && $this->receiptValidator && $proofImagePath) {
            try {
                $ocrResults = $this->receiptValidator->validateReceipt($proofImagePath, $payment);
                $results['verification_methods']['ocr'] = [
                    'valid' => $ocrResults['is_valid'],
                    'confidence' => $ocrResults['confidence'],
                    'score' => $ocrResults['score'],
                    'max_score' => $ocrResults['max_score'],
                    'extracted_data' => $ocrResults['extracted_data']
                ];

                // Add OCR score to total (max 5 points out of 10 from OCR)
                // We scale it so that a perfect OCR score (10/10) would add 5 points to our total
                $score += ($ocrResults['score'] / $ocrResults['max_score']) * 5;
            } catch (\Exception $e) {
                Log::error('OCR verification error: ' . $e->getMessage(), [
                    'payment_id' => $payment->id,
                    'trace' => $e->getTraceAsString()
                ]);

                $results['verification_methods']['ocr'] = [
                    'valid' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // METHOD 3: Payment Metadata Validation
        // Add checks for payment amount, user history, etc.
        $metadataVerification = $this->verifyPaymentMetadata($payment);
        $results['verification_methods']['metadata'] = $metadataVerification;

        // Add metadata score
        $score += $metadataVerification['score'];

        // Calculate final confidence score (0-100%)
        $confidence = ($score / $maxScore) * 100;
        $results['confidence'] = $confidence;

        // Determine verification threshold from config
        $autoApprovalThreshold = config('payment.verification_threshold', 70);

        // Check if payment should be auto-verified
        if ($confidence >= $autoApprovalThreshold) {
            $results['verified'] = true;
            $this->markPaymentAsPaid($payment, 'auto_verification', $results);
        } else {
            // Add reasons for requiring admin review
            if (!$patternVerification['valid']) {
                $results['reasons'][] = 'Transaction ID pattern did not match expected format';
            }

            if ($ocrResults && !$ocrResults['is_valid']) {
                $results['reasons'][] = 'OCR verification failed to confirm payment details';
            }

            if ($confidence < 40) {
                $results['reasons'][] = 'Low overall confidence score';
            }
        }

        return $results;
    }

    /**
     * Verify the transaction ID against the payment method pattern
     *
     * @param Payment $payment
     * @param string $transactionId
     * @return array
     */
    protected function verifyTransactionIdPattern(Payment $payment, string $transactionId)
    {
        $result = [
            'valid' => false,
            'pattern' => null,
            'transaction_id' => $transactionId
        ];

        // Make sure auto-verification is enabled
        if (!config('payment.enable_auto_verification', true)) {
            return $result;
        }

        // Get the payment method used
        $method = $payment->payment_data['payment_method_used'] ?? '';
        
        // Get patterns from config
        $patterns = config('payment.transaction_patterns', []);
        
        // Check if the transaction ID matches the expected pattern for this payment method
        if (isset($patterns[$method]['pattern'])) {
            $pattern = '/' . $patterns[$method]['pattern'] . '/';
            $result['pattern'] = $patterns[$method]['pattern'];
            
            if (preg_match($pattern, $transactionId)) {
                $result['valid'] = true;
            }
        }

        return $result;
    }

    /**
     * Verify payment metadata (payment amount, course price, etc.)
     *
     * @param Payment $payment
     * @return array
     */
    protected function verifyPaymentMetadata(Payment $payment)
    {
        $result = [
            'score' => 0,
            'max_score' => 3,
            'checks' => []
        ];

        // Check 1: Course price matches payment amount
        if ($payment->course && $payment->course->price == $payment->amount) {
            $result['score'] += 1;
            $result['checks']['price_match'] = true;
        } else {
            $result['checks']['price_match'] = false;
        }

        // Check 2: User payment history validation
        // Give higher confidence to users who have completed successful payments before
        $userHasSuccessfulPayments = $this->userHasSuccessfulPayments($payment->user_id);
        if ($userHasSuccessfulPayments) {
            $result['score'] += 1;
            $result['checks']['user_history'] = true;
        } else {
            $result['checks']['user_history'] = false;
        }

        // Check 3: Payment was submitted within a reasonable timeframe after creation
        // Typically, legitimate payments are submitted soon after creation
        if (isset($payment->payment_data['submission_date'])) {
            $submissionTime = \Carbon\Carbon::parse($payment->payment_data['submission_date']);
            $creationTime = $payment->created_at;
            
            // If payment was submitted within 3 hours of creation, it's more likely legitimate
            if ($creationTime->diffInHours($submissionTime) <= 3) {
                $result['score'] += 1;
                $result['checks']['submission_timing'] = true;
            } else {
                $result['checks']['submission_timing'] = false;
            }
        } else {
            $result['checks']['submission_timing'] = false;
        }

        return $result;
    }

    /**
     * Check if payment reference has expired
     *
     * @param Payment $payment
     * @return bool
     */
    protected function isReferenceExpired(Payment $payment)
    {
        $expiryHours = config('payment.reference_expiry_hours', 24);
        return $payment->created_at->addHours($expiryHours)->isPast();
    }

    /**
     * Check if user has successful payments in the past
     *
     * @param int $userId
     * @return bool
     */
    protected function userHasSuccessfulPayments($userId)
    {
        return Payment::where('user_id', $userId)
            ->where('status', 'completed')
            ->count() > 0;
    }

    /**
     * Mark a payment as paid and process enrollment
     *
     * @param Payment $payment
     * @param string $method
     * @param array $verificationResults
     * @return Payment
     */
    public function markPaymentAsPaid(Payment $payment, string $method, array $verificationResults = [])
    {
        // Prepare verification details
        $verificationDetails = [
            'verified_by' => 'system',
            'verified_at' => now()->toDateTimeString(),
            'verification_method' => $method,
            'verification_results' => $verificationResults,
            'confidence' => $verificationResults['confidence'] ?? null,
            'admin_notes' => 'Automatically verified by system with ' . 
                             round($verificationResults['confidence'] ?? 0) . '% confidence'
        ];

        // Update payment status and data
        $payment->status = 'completed';
        $payment->paid_at = now();
        $payment->payment_data = array_merge($payment->payment_data ?? [], $verificationDetails);
        $payment->save();
        
        // Enroll the user in the course
        $user = User::find($payment->user_id);
        $user->courses()->syncWithoutDetaching([$payment->course_id]);
        
        // Log the verification
        Log::info('Payment auto-verified', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'course_id' => $payment->course_id,
            'method' => $method,
            'confidence' => $verificationResults['confidence'] ?? null
        ]);
        
        return $payment;
    }

    /**
     * Process payments requiring admin review
     *
     * @param int $limit Number of payments to process
     * @return array
     */
    public function flagPaymentsForReview($limit = 20)
    {
        $pendingPayments = Payment::where('status', 'pending_verification')
            ->whereRaw("JSON_EXTRACT(payment_data, '$.ocr_validation.confidence') >= ?", [
                config('payment.ocr.admin_review_threshold', 40)
            ])
            ->whereRaw("JSON_EXTRACT(payment_data, '$.ocr_validation.confidence') < ?", [
                config('payment.ocr.approval_threshold', 70)
            ])
            ->limit($limit)
            ->get();
        
        $processed = [];
        
        foreach ($pendingPayments as $payment) {
            // Add a flag for admin review
            $payment->payment_data = array_merge($payment->payment_data, [
                'admin_review_priority' => 'high',
                'admin_review_reason' => 'Partial verification achieved (' . 
                                       round($payment->payment_data['ocr_validation']['confidence']) . '% confidence)'
            ]);
            $payment->save();
            
            $processed[] = $payment->id;
        }
        
        return [
            'processed' => count($processed),
            'payment_ids' => $processed
        ];
    }
} 