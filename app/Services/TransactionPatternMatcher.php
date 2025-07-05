<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Standalone transaction ID verification service
 * 
 * This class is designed to work without any external dependencies.
 * It validates transaction IDs based on patterns, uniqueness, and other
 * standalone verification methods.
 */
class TransactionPatternMatcher
{
    /**
     * Transaction patterns from config
     * 
     * @var array
     */
    protected $patterns;
    
    /**
     * Cache duration for transaction IDs (24 hours)
     * 
     * @var int
     */
    protected $cacheDuration = 86400;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Load patterns from config, or use default patterns if config is unavailable
        try {
            $this->patterns = config('payment.transaction_patterns', $this->getDefaultPatterns());
        } catch (\Exception $e) {
            // Fallback to default patterns if config system fails
            $this->patterns = $this->getDefaultPatterns();
            Log::warning('Failed to load transaction patterns from config, using defaults', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Verify a transaction ID against known patterns and rules
     * 
     * @param string $transactionId The transaction ID to verify
     * @param string $paymentMethod The payment method used (vodafone_cash, fawry, etc)
     * @param array $context Additional context information
     * @return array Result with validation details
     */
    public function validate(string $transactionId, string $paymentMethod, array $context = [])
    {
        $result = [
            'valid' => false,
            'score' => 0,
            'max_score' => 10,
            'confidence' => 0,
            'checks' => [],
            'errors' => []
        ];
        
        // Basic sanitization
        $transactionId = trim($transactionId);
        
        // 1. Check transaction ID format against pattern
        $patternResult = $this->validatePattern($transactionId, $paymentMethod);
        $result['checks']['pattern'] = $patternResult;
        if ($patternResult['valid']) {
            $result['score'] += 5; // Pattern match is 50% of score
        } else {
            $result['errors'][] = 'Transaction ID format does not match expected pattern';
        }
        
        // 2. Check for transaction ID uniqueness
        $uniquenessResult = $this->checkUniqueness($transactionId);
        $result['checks']['uniqueness'] = $uniquenessResult;
        if ($uniquenessResult['valid']) {
            $result['score'] += 2; // Uniqueness is 20% of score
        } else {
            $result['errors'][] = 'Transaction ID has already been used';
        }
        
        // 3. Check submission timing if context provides dates
        if (isset($context['payment_date']) && isset($context['submission_date'])) {
            $timingResult = $this->checkSubmissionTiming(
                $context['payment_date'],
                $context['submission_date']
            );
            $result['checks']['timing'] = $timingResult;
            if ($timingResult['valid']) {
                $result['score'] += 2; // Timing is 20% of score
            } else {
                $result['errors'][] = 'Suspicious timing between payment and submission dates';
            }
        } else {
            // Skip this check if dates aren't available
            $result['score'] += 1; // Give partial credit when we can't check
            $result['checks']['timing'] = ['valid' => true, 'skipped' => true];
        }
        
        // 4. Check for suspicious patterns
        $suspiciousResult = $this->checkSuspiciousPatterns($transactionId);
        $result['checks']['suspicious_patterns'] = $suspiciousResult;
        if ($suspiciousResult['valid']) {
            $result['score'] += 1; // Suspicious pattern check is 10% of score
        } else {
            $result['errors'][] = 'Transaction ID matches known suspicious patterns';
        }
        
        // Calculate overall confidence
        $result['confidence'] = ($result['score'] / $result['max_score']) * 100;
        
        // Determine final validity (configurable threshold, default 70%)
        $threshold = 7; // Out of 10
        $result['valid'] = $result['score'] >= $threshold;
        
        return $result;
    }
    
    /**
     * Check if transaction ID matches the expected pattern for payment method
     * 
     * @param string $transactionId
     * @param string $paymentMethod
     * @return array
     */
    protected function validatePattern(string $transactionId, string $paymentMethod)
    {
        $result = ['valid' => false, 'pattern' => null];
        
        // Check if we have a pattern for this payment method
        if (!isset($this->patterns[$paymentMethod]['pattern'])) {
            return $result;
        }
        
        $pattern = '/' . $this->patterns[$paymentMethod]['pattern'] . '/';
        $result['pattern'] = $this->patterns[$paymentMethod]['pattern'];
        
        // Check if transaction ID matches the pattern
        if (preg_match($pattern, $transactionId)) {
            $result['valid'] = true;
        }
        
        return $result;
    }
    
    /**
     * Check if transaction ID is unique (not previously seen)
     * Uses local caching to avoid dependency on database when possible
     * 
     * @param string $transactionId
     * @return array
     */
    protected function checkUniqueness(string $transactionId)
    {
        $result = ['valid' => true, 'cached' => false];
        
        // Check in local cache first (to avoid DB roundtrips)
        $cacheKey = 'transaction_id_' . md5($transactionId);
        
        try {
            if (Cache::has($cacheKey)) {
                $result['valid'] = false;
                $result['cached'] = true;
                return $result;
            }
        } catch (\Exception $e) {
            // If cache fails, we'll try database check
            Log::warning('Cache check failed in TransactionPatternMatcher', [
                'error' => $e->getMessage()
            ]);
        }
        
        // If no cached result, try to check in database
        try {
            // Use the Payment model, but with try/catch to handle DB issues
            $existingPayment = Payment::where('status', 'completed')
                ->whereRaw("JSON_EXTRACT(payment_data, '$.transaction_id') = ?", [$transactionId])
                ->exists();
            
            if ($existingPayment) {
                $result['valid'] = false;
                
                // Cache this transaction ID for future checks
                try {
                    Cache::put($cacheKey, true, $this->cacheDuration);
                } catch (\Exception $e) {
                    // Continue even if cache fails
                }
            } else {
                $result['valid'] = true;
            }
        } catch (\Exception $e) {
            // If database check fails, we'll assume it's valid for now
            // but flag that we couldn't verify uniqueness
            $result['valid'] = true;
            $result['db_error'] = true;
            Log::warning('Database uniqueness check failed in TransactionPatternMatcher', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
        }
        
        return $result;
    }
    
    /**
     * Check if the timing between payment date and submission date is suspicious
     * 
     * @param string $paymentDate
     * @param string $submissionDate
     * @return array
     */
    protected function checkSubmissionTiming(string $paymentDate, string $submissionDate)
    {
        $result = ['valid' => true];
        
        try {
            $paymentTime = Carbon::parse($paymentDate);
            $submissionTime = Carbon::parse($submissionDate);
            
            // Payment should not be in the future
            if ($paymentTime->isFuture()) {
                $result['valid'] = false;
                $result['reason'] = 'payment_date_future';
                return $result;
            }
            
            // Submission should not be before payment
            if ($submissionTime->lt($paymentTime)) {
                $result['valid'] = false;
                $result['reason'] = 'submission_before_payment';
                return $result;
            }
            
            // Transaction submitted too long after claimed payment date (7 days max)
            if ($paymentTime->diffInDays($submissionTime) > 7) {
                $result['valid'] = false;
                $result['reason'] = 'submission_too_late';
                return $result;
            }
            
            // Transaction submitted too quickly after payment (suspicious automation)
            // Allow at least 10 seconds for human interaction
            if ($paymentTime->diffInSeconds($submissionTime) < 10) {
                $result['valid'] = false;
                $result['reason'] = 'submission_too_quick';
                return $result;
            }
        } catch (\Exception $e) {
            // If date parsing fails, we'll assume it's valid
            // but flag that we couldn't verify timing
            $result['valid'] = true;
            $result['parse_error'] = true;
            Log::warning('Date parsing failed in TransactionPatternMatcher', [
                'error' => $e->getMessage(),
                'payment_date' => $paymentDate,
                'submission_date' => $submissionDate
            ]);
        }
        
        return $result;
    }
    
    /**
     * Check for known suspicious patterns in transaction ID
     * 
     * @param string $transactionId
     * @return array
     */
    protected function checkSuspiciousPatterns(string $transactionId)
    {
        $result = ['valid' => true];
        
        // Check for sequential numbers (e.g., 12345678)
        if (preg_match('/\d{5,}/', $transactionId, $matches)) {
            $number = $matches[0];
            if ($this->isSequential($number)) {
                $result['valid'] = false;
                $result['reason'] = 'sequential_numbers';
                return $result;
            }
        }
        
        // Check for repetitive patterns (e.g., 1212121)
        if (preg_match('/(.{2,})\1{2,}/', $transactionId)) {
            $result['valid'] = false;
            $result['reason'] = 'repetitive_pattern';
            return $result;
        }
        
        // Check for all same digits (e.g., 11111)
        if (preg_match('/(\d)\1{4,}/', $transactionId)) {
            $result['valid'] = false;
            $result['reason'] = 'same_digits';
            return $result;
        }
        
        return $result;
    }
    
    /**
     * Check if a number has a sequential pattern
     * 
     * @param string $number
     * @return bool
     */
    protected function isSequential(string $number)
    {
        // Check for ascending sequence
        $ascending = true;
        for ($i = 0; $i < strlen($number) - 1; $i++) {
            if (intval($number[$i]) + 1 !== intval($number[$i + 1])) {
                $ascending = false;
                break;
            }
        }
        
        if ($ascending) {
            return true;
        }
        
        // Check for descending sequence
        $descending = true;
        for ($i = 0; $i < strlen($number) - 1; $i++) {
            if (intval($number[$i]) - 1 !== intval($number[$i + 1])) {
                $descending = false;
                break;
            }
        }
        
        return $descending;
    }
    
    /**
     * Get default transaction patterns if config is unavailable
     * 
     * @return array
     */
    protected function getDefaultPatterns()
    {
        return [
            'vodafone_cash' => [
                'pattern' => '^VC\d{10,12}$',
                'example' => 'VC1234567890'
            ],
            'fawry' => [
                'pattern' => '^FWY\d{12,15}$',
                'example' => 'FWY123456789012'
            ],
            'instapay' => [
                'pattern' => '^IP\d{14,16}$',
                'example' => 'IP12345678901234'
            ]
        ];
    }
} 