<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\IndependentVerificationPipeline;
use App\Services\PaymentVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:process-pending 
                            {--limit=50 : Maximum number of payments to process}
                            {--expedite-only : Only process payments marked for expedited review}
                            {--batch-size=10 : Number of payments to process in each batch}
                            {--max-attempts=3 : Maximum retry attempts for failed payments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending payments using the independent verification pipeline';

    /**
     * @var IndependentVerificationPipeline
     */
    protected $verificationPipeline;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $expediteOnly = $this->option('expedite-only');
        $batchSize = (int) $this->option('batch-size');
        $maxAttempts = (int) $this->option('max-attempts');
        
        $this->info("Processing pending payments (limit: $limit, batch size: $batchSize)...");
        
        // Initialize independent verification pipeline
        try {
            $this->verificationPipeline = app(IndependentVerificationPipeline::class);
            $this->info("Using Independent Verification Pipeline for payment processing");
        } catch (\Exception $e) {
            $this->warn("Failed to initialize Independent Verification Pipeline, falling back to standard verification");
            $this->verificationPipeline = app(PaymentVerificationService::class);
            Log::warning("Failed to initialize Independent Verification Pipeline", [
                'error' => $e->getMessage()
            ]);
        }
        
        // Get pending payments that are due for processing
        $query = $this->buildPaymentQuery($expediteOnly);
        
        // Get total count for informational purposes
        try {
            $totalCount = $query->count();
            $this->info("Found {$totalCount} pending payments eligible for processing.");
        } catch (\Exception $e) {
            $this->warn("Unable to get total count of pending payments.");
            $totalCount = 'unknown';
        }
        
        // Process in batches to improve performance and prevent memory issues
        $processed = 0;
        $verified = 0;
        $flaggedForReview = 0;
        $failed = 0;
        $retried = 0;
        
        while ($processed < $limit) {
            // Get the next batch of payments
            try {
                $pendingPayments = $query->limit($batchSize)->get();
                
                if ($pendingPayments->isEmpty()) {
                    $this->info("No more pending payments to process.");
                    break;
                }
                
                $this->processPaymentBatch(
                    $pendingPayments, 
                    $verified, 
                    $flaggedForReview, 
                    $failed, 
                    $retried,
                    $maxAttempts
                );
                
                $processed += $pendingPayments->count();
                $this->info("Processed {$processed} payments so far...");
                
                // Short pause to prevent hammering the database
                usleep(100000); // 100ms pause
            } catch (\Exception $e) {
                $this->error("Error retrieving payment batch: {$e->getMessage()}");
                Log::error("Error retrieving payment batch", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with the next batch despite the error
                $processed += $batchSize;
            }
        }
        
        $this->newLine();
        $this->info("Payment processing completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Verified automatically', $verified],
                ['Flagged for review', $flaggedForReview],
                ['Retried (will process later)', $retried],
                ['Failed to process', $failed],
                ['Total processed', $processed],
            ]
        );
        
        return 0;
    }
    
    /**
     * Build the query to retrieve payments for processing
     *
     * @param bool $expediteOnly
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildPaymentQuery($expediteOnly)
    {
        $query = Payment::where('status', 'pending_verification');
        
        // If expedite-only flag is set, only process payments with specific markers
        if ($expediteOnly) {
            $this->info("Processing only expedited payments...");
            $query->whereRaw("JSON_EXTRACT(payment_data, '$.admin_review_priority') = ?", ['high']);
        }
        
        // Check for payments due for retry and prioritize them
        try {
            $query->where(function($query) {
                $query->whereRaw("JSON_EXTRACT(payment_data, '$.next_retry_at') IS NULL")
                      ->orWhereRaw("JSON_EXTRACT(payment_data, '$.next_retry_at') <= ?", [now()->toDateTimeString()]);
            });
            
            // Order by priority and creation date
            $query->orderByRaw("CASE WHEN JSON_EXTRACT(payment_data, '$.admin_review_priority') = 'high' THEN 0 ELSE 1 END")
                  ->orderBy('created_at', 'asc');
        } catch (\Exception $e) {
            // If the advanced query fails, fall back to a simpler query
            $this->warn("Using simplified payment query due to an error: " . $e->getMessage());
            $query = Payment::where('status', 'pending_verification')
                           ->orderBy('created_at', 'asc');
        }
        
        return $query;
    }
    
    /**
     * Process a batch of payments
     *
     * @param \Illuminate\Database\Eloquent\Collection $payments
     * @param int &$verified Count of verified payments (passed by reference)
     * @param int &$flaggedForReview Count of payments flagged for review (passed by reference)
     * @param int &$failed Count of failed payments (passed by reference)
     * @param int &$retried Count of retried payments (passed by reference)
     * @param int $maxAttempts Maximum retry attempts
     * @return void
     */
    protected function processPaymentBatch($payments, &$verified, &$flaggedForReview, &$failed, &$retried, $maxAttempts)
    {
        $progressBar = $this->output->createProgressBar($payments->count());
        $progressBar->start();
        
        foreach ($payments as $payment) {
            try {
                // Extract payment data safely with fallbacks
                $transactionId = $payment->payment_data['transaction_id'] ?? null;
                $proofPath = $payment->payment_data['proof_path'] ?? null;
                $attempts = $payment->payment_data['processing_attempts'] ?? 0;
                
                // Skip if missing essential data
                if (!$transactionId) {
                    $this->handleMissingData($payment, "Missing transaction ID", $failed);
                    $progressBar->advance();
                    continue;
                }
                
                // Increment attempt counter
                $attempts++;
                $payment->payment_data = array_merge($payment->payment_data ?? [], [
                    'processing_attempts' => $attempts,
                    'last_attempt_at' => now()->toDateTimeString()
                ]);
                
                // Check if we've exceeded max attempts
                if ($attempts > $maxAttempts) {
                    $this->handleMaxAttemptsExceeded($payment, $failed);
                    $progressBar->advance();
                    continue;
                }
                
                // Verify the payment using the pipeline
                $verificationResults = $this->verificationPipeline->verify(
                    $payment,
                    $transactionId,
                    $proofPath
                );
                
                if ($verificationResults['verified']) {
                    $verified++;
                    $this->logSuccess($payment, $verificationResults);
                } else {
                    // Check if we should retry or flag for review
                    if ($this->shouldRetry($payment, $verificationResults)) {
                        $this->scheduleForRetry($payment, $attempts, $retried);
                    } else {
                        $this->flagForReview($payment, $verificationResults, $flaggedForReview);
                    }
                }
            } catch (\Exception $e) {
                $this->handleProcessingError($payment, $e, $failed);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    /**
     * Handle missing payment data
     *
     * @param Payment $payment
     * @param string $reason
     * @param int &$failed
     * @return void
     */
    protected function handleMissingData(Payment $payment, $reason, &$failed)
    {
        $this->warn("Payment #{$payment->id}: {$reason}");
        
        try {
            // Update payment data to mark the error
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'processing_error' => $reason,
                'error_at' => now()->toDateTimeString(),
                'admin_review_priority' => 'high', // Flag for urgent admin attention
                'admin_review_reason' => $reason
            ]);
            $payment->save();
        } catch (\Exception $e) {
            Log::error("Failed to update payment data for error", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
        
        $failed++;
    }
    
    /**
     * Handle payment that has exceeded maximum retry attempts
     *
     * @param Payment $payment
     * @param int &$failed
     * @return void
     */
    protected function handleMaxAttemptsExceeded(Payment $payment, &$failed)
    {
        $this->warn("Payment #{$payment->id}: Maximum retry attempts exceeded");
        
        try {
            // Mark for admin review with high priority
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'processing_status' => 'max_attempts_exceeded',
                'max_attempts_at' => now()->toDateTimeString(),
                'admin_review_priority' => 'high',
                'admin_review_reason' => 'Maximum retry attempts exceeded'
            ]);
            $payment->save();
            
            // Send notification to admins
            $notificationService = app(App\Services\NotificationService::class);
            $lastError = $payment->payment_data['processing_error'] ?? 'Unknown error';
            $attempts = $payment->payment_data['processing_attempts'] ?? 0;
            
            $notificationService->notifyAboutExceededRetryAttempts($payment, $attempts, $lastError);
        } catch (\Exception $e) {
            Log::error("Failed to update payment after max attempts", [
                'payment_id' => $payment->id, 
                'error' => $e->getMessage()
            ]);
        }
        
        $failed++;
    }
    
    /**
     * Determine if payment should be retried
     *
     * @param Payment $payment
     * @param array $verificationResults
     * @return bool
     */
    protected function shouldRetry(Payment $payment, array $verificationResults)
    {
        // Retry if verification failed due to temporary issues
        $retryableErrors = [
            'OCR verification failed',
            'database error',
            'connection error',
            'timeout'
        ];
        
        foreach ($verificationResults['errors'] as $error) {
            foreach ($retryableErrors as $retryableError) {
                if (stripos($error, $retryableError) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Schedule payment for retry with exponential backoff
     *
     * @param Payment $payment
     * @param int $attempts
     * @param int &$retried
     * @return void
     */
    protected function scheduleForRetry(Payment $payment, $attempts, &$retried)
    {
        // Calculate backoff time (5, 10, 20 minutes)
        $backoffMinutes = pow(2, $attempts - 1) * 5;
        $nextRetry = now()->addMinutes($backoffMinutes);
        
        try {
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'next_retry_at' => $nextRetry->toDateTimeString(),
                'retry_count' => $attempts,
                'last_retry_reason' => 'Temporary error occurred'
            ]);
            $payment->save();
            
            $this->info("Payment #{$payment->id}: Scheduled for retry in {$backoffMinutes} minutes");
            $retried++;
        } catch (\Exception $e) {
            Log::error("Failed to schedule payment for retry", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Flag payment for admin review
     *
     * @param Payment $payment
     * @param array $verificationResults
     * @param int &$flaggedForReview
     * @return void
     */
    protected function flagForReview(Payment $payment, array $verificationResults, &$flaggedForReview)
    {
        try {
            // Determine priority based on confidence score
            $confidence = $verificationResults['confidence'] ?? 0;
            $priority = $confidence >= 40 ? 'medium' : 'high';
            
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'verification_results' => $verificationResults,
                'auto_processed' => true,
                'auto_processed_at' => now()->toDateTimeString(),
                'admin_review_priority' => $priority,
                'admin_review_reason' => implode(', ', $verificationResults['errors'] ?? ['Verification failed']),
                'verification_confidence' => $confidence
            ]);
            $payment->save();
            
            // Notify admins if confidence is below threshold for auto-approval
            if ($confidence < config('payment.verification.auto_approval_threshold', 70)) {
                $notificationService = app(App\Services\NotificationService::class);
                $notificationService->notifyAboutPaymentVerification($payment, $confidence);
            }
            
            $flaggedForReview++;
        } catch (\Exception $e) {
            Log::error("Failed to flag payment for review", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle processing error
     *
     * @param Payment $payment
     * @param \Exception $exception
     * @param int &$failed
     * @return void
     */
    protected function handleProcessingError(Payment $payment, \Exception $exception, &$failed)
    {
        $this->error("Error processing payment #{$payment->id}: {$exception->getMessage()}");
        
        try {
            // Add error information to payment data
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'processing_error' => $exception->getMessage(),
                'error_at' => now()->toDateTimeString(),
                'error_trace' => $exception->getTraceAsString(),
                'admin_review_priority' => 'medium',
                'admin_review_reason' => 'Processing error: ' . $exception->getMessage()
            ]);
            $payment->save();
        } catch (\Exception $e) {
            Log::error("Failed to update payment with error details", [
                'payment_id' => $payment->id,
                'original_error' => $exception->getMessage(),
                'update_error' => $e->getMessage()
            ]);
        }
        
        Log::error("Payment processing error", [
            'payment_id' => $payment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $failed++;
    }
    
    /**
     * Log successful verification
     *
     * @param Payment $payment
     * @param array $verificationResults
     * @return void
     */
    protected function logSuccess(Payment $payment, array $verificationResults)
    {
        $methods = array_keys($verificationResults['verification_methods'] ?? []);
        $methodsUsed = implode(', ', $methods);
        
        $this->info("Payment #{$payment->id}: Verified successfully using {$methodsUsed}");
        
        Log::info("Payment verified automatically", [
            'payment_id' => $payment->id,
            'confidence' => $verificationResults['confidence'] ?? 0,
            'methods_used' => $methods
        ]);
    }
} 