<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * Maximum number of payment submissions per hour from a single IP address
     *
     * @var int
     */
    protected $maxSubmissionsPerHour = 3;

    /**
     * Cache time for IP tracking (in minutes)
     *
     * @var int
     */
    protected $ipCacheTime = 60;

    /**
     * Check if a submission attempt might be fraudulent
     *
     * @param Payment $payment
     * @param string $transactionId
     * @param string $ipAddress
     * @return array
     */
    public function checkSubmission(Payment $payment, string $transactionId, string $ipAddress)
    {
        $result = [
            'suspicious' => false,
            'reasons' => [],
            'score' => 0,
            'max_score' => 5,
            'ip_limited' => false
        ];

        // Check 1: IP submission rate limiting
        $ipLimited = $this->checkIpSubmissionRate($ipAddress);
        if ($ipLimited) {
            $result['suspicious'] = true;
            $result['reasons'][] = 'Too many submissions from this IP address';
            $result['score'] += 2;
            $result['ip_limited'] = true;
        }

        // Check 2: Unusual payment amount
        $unusualAmount = $this->checkUnusualPaymentAmount($payment);
        if ($unusualAmount) {
            $result['suspicious'] = true;
            $result['reasons'][] = 'Unusual payment amount detected';
            $result['score'] += 1;
        }

        // Check 3: Transaction ID pattern similarity to recent submissions
        $similarTransactionId = $this->checkTransactionIdSimilarity($transactionId);
        if ($similarTransactionId['similar']) {
            $result['suspicious'] = true;
            $result['reasons'][] = 'Transaction ID similar to recent submission: ' . $similarTransactionId['similar_to'];
            $result['score'] += 2;
        }

        // Log suspicious activity if score is significant
        if ($result['score'] >= 2) {
            Log::warning('Suspicious payment activity detected', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'ip_address' => $ipAddress,
                'score' => $result['score'],
                'reasons' => $result['reasons']
            ]);
        }

        return $result;
    }

    /**
     * Check for unusually high submission rate from a single IP address
     *
     * @param string $ipAddress
     * @return bool
     */
    protected function checkIpSubmissionRate(string $ipAddress)
    {
        $cacheKey = 'payment_ip_' . str_replace('.', '_', $ipAddress);
        
        // Get current count from cache
        $currentCount = Cache::get($cacheKey, 0);
        
        // If over the limit, return true (suspicious)
        if ($currentCount >= $this->maxSubmissionsPerHour) {
            return true;
        }
        
        // Otherwise increment the counter
        Cache::put($cacheKey, $currentCount + 1, $this->ipCacheTime);
        
        return false;
    }

    /**
     * Check if a payment amount is unusual compared to standard course pricing
     *
     * @param Payment $payment
     * @return bool
     */
    protected function checkUnusualPaymentAmount(Payment $payment)
    {
        try {
            // Get the standard price for this course
            $coursePrice = $payment->course->price ?? 0;
            
            // If the course has no price set, skip this check
            if ($coursePrice <= 0) {
                return false;
            }
            
            // Calculate the difference percentage
            $difference = abs($payment->amount - $coursePrice);
            $percentageDiff = ($difference / $coursePrice) * 100;
            
            // Consider it unusual if the payment amount is more than 10% different
            // from the standard course price
            return $percentageDiff > 10;
        } catch (\Exception $e) {
            // In case of any error, log but don't flag as suspicious
            Log::error('Error checking unusual payment amount: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a transaction ID is suspiciously similar to recent submissions
     *
     * @param string $transactionId
     * @return array
     */
    protected function checkTransactionIdSimilarity(string $transactionId)
    {
        $result = [
            'similar' => false,
            'similar_to' => null,
            'similarity_score' => 0
        ];
        
        // Get recent transaction IDs from cache
        $recentTransactions = Cache::get('recent_transaction_ids', []);
        
        foreach ($recentTransactions as $recent) {
            // Calculate similarity using Levenshtein distance
            $distance = levenshtein($transactionId, $recent);
            $maxLength = max(strlen($transactionId), strlen($recent));
            
            // Normalize the score (0-100%)
            $similarityScore = 100 - (($distance / $maxLength) * 100);
            
            // If similarity is over 80% but not exact match, flag as suspicious
            if ($similarityScore > 80 && $similarityScore < 100) {
                $result['similar'] = true;
                $result['similar_to'] = $recent;
                $result['similarity_score'] = $similarityScore;
                break;
            }
        }
        
        // Add this transaction ID to the cache for future checks
        if (!in_array($transactionId, $recentTransactions)) {
            // Keep only the last 50 transaction IDs to avoid cache bloat
            if (count($recentTransactions) >= 50) {
                array_shift($recentTransactions);
            }
            
            $recentTransactions[] = $transactionId;
            Cache::put('recent_transaction_ids', $recentTransactions, 60 * 24); // 24 hours
        }
        
        return $result;
    }

    /**
     * Calculate a trust score for a user based on their payment history and account age
     *
     * @param User $user
     * @return array
     */
    public function calculateUserTrustScore(User $user)
    {
        $result = [
            'score' => 0,
            'max_score' => 10,
            'factors' => []
        ];

        // Factor 1: Account age (up to 3 points)
        $accountAgeInDays = $user->created_at->diffInDays(now());
        if ($accountAgeInDays > 180) { // More than 6 months
            $result['score'] += 3;
            $result['factors']['account_age'] = 'Account older than 6 months';
        } elseif ($accountAgeInDays > 30) { // More than 1 month
            $result['score'] += 2;
            $result['factors']['account_age'] = 'Account older than 1 month';
        } elseif ($accountAgeInDays > 7) { // More than 1 week
            $result['score'] += 1;
            $result['factors']['account_age'] = 'Account older than 1 week';
        } else {
            $result['factors']['account_age'] = 'New account (less than 1 week)';
        }

        // Factor 2: Successfully completed payments (up to 4 points)
        $completedPayments = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
            
        if ($completedPayments >= 3) {
            $result['score'] += 4;
            $result['factors']['payment_history'] = '3+ successful payments';
        } elseif ($completedPayments == 2) {
            $result['score'] += 3;
            $result['factors']['payment_history'] = '2 successful payments';
        } elseif ($completedPayments == 1) {
            $result['score'] += 2;
            $result['factors']['payment_history'] = '1 successful payment';
        } else {
            $result['factors']['payment_history'] = 'No successful payments';
        }

        // Factor 3: Rejected payments (negative impact, up to -3 points)
        $rejectedPayments = Payment::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->count();
            
        if ($rejectedPayments >= 3) {
            $result['score'] -= 3;
            $result['factors']['rejected_payments'] = '3+ rejected payments';
        } elseif ($rejectedPayments == 2) {
            $result['score'] -= 2;
            $result['factors']['rejected_payments'] = '2 rejected payments';
        } elseif ($rejectedPayments == 1) {
            $result['score'] -= 1;
            $result['factors']['rejected_payments'] = '1 rejected payment';
        } else {
            $result['factors']['rejected_payments'] = 'No rejected payments';
        }

        // Factor 4: Profile completeness (up to 3 points)
        $profileScore = 0;
        if (!empty($user->email) && !empty($user->email_verified_at)) $profileScore++;
        if (!empty($user->phone)) $profileScore++;
        if (!empty($user->address)) $profileScore++;
        
        $result['score'] += $profileScore;
        $result['factors']['profile_completeness'] = "Profile completeness: {$profileScore}/3";

        // Ensure score doesn't go below 0
        $result['score'] = max(0, $result['score']);

        return $result;
    }
} 