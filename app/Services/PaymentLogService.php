<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PaymentLogService
{
    /**
     * Create a new payment log entry.
     *
     * @param Payment $payment
     * @param string $action
     * @param array $data
     * @param User|null $user
     * @param User|null $admin
     * @return PaymentLog
     */
    public function create(Payment $payment, string $action, array $data = [], ?User $user = null, ?User $admin = null)
    {
        // Get the current user if none provided
        $user = $user ?? Auth::user();
        $userId = $user?->id;
        
        // Get admin user if not provided
        $adminId = $admin?->id;
        
        // Create the log entry
        $log = new PaymentLog([
            'payment_id' => $payment->id,
            'user_id' => $userId,
            'admin_id' => $adminId,
            'action' => $action,
            'status_before' => $payment->getOriginal('status'),
            'status_after' => $payment->status,
            'data' => $data,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
        
        $log->save();
        
        return $log;
    }
    
    /**
     * Log a payment creation event.
     */
    public function logCreation(Payment $payment, User $user, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'created',
            array_merge([
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'reference_number' => $payment->reference_number,
            ], $additionalData),
            $user
        );
    }
    
    /**
     * Log a payment proof submission event.
     */
    public function logProofSubmission(Payment $payment, User $user, string $transactionId, string $proofPath, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'submitted',
            array_merge([
                'transaction_id' => $transactionId,
                'proof_path' => $proofPath,
                'submitted_at' => now()->toDateTimeString(),
            ], $additionalData),
            $user
        );
    }
    
    /**
     * Log an automatic verification event.
     */
    public function logAutoVerification(Payment $payment, array $verificationResults, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'auto_verified',
            array_merge([
                'verification_method' => 'automatic',
                'confidence' => $verificationResults['confidence'] ?? null,
                'verification_methods' => $verificationResults['verification_methods'] ?? [],
                'verified_at' => now()->toDateTimeString(),
            ], $additionalData)
        );
    }
    
    /**
     * Log a manual (admin) verification event.
     */
    public function logAdminVerification(Payment $payment, User $admin, string $notes = null, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'admin_verified',
            array_merge([
                'verification_method' => 'manual',
                'admin_name' => $admin->name,
                'admin_notes' => $notes,
                'verified_at' => now()->toDateTimeString(),
            ], $additionalData),
            null,
            $admin
        );
    }
    
    /**
     * Log a payment rejection event.
     */
    public function logRejection(Payment $payment, User $admin, string $reason, string $notes = null, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'rejected',
            array_merge([
                'rejection_reason' => $reason,
                'admin_name' => $admin->name,
                'admin_notes' => $notes,
                'rejected_at' => now()->toDateTimeString(),
            ], $additionalData),
            null,
            $admin
        );
    }
    
    /**
     * Log a verification attempt event.
     */
    public function logVerificationAttempt(Payment $payment, array $verificationResults, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'verification_attempted',
            array_merge([
                'confidence' => $verificationResults['confidence'] ?? null,
                'verification_methods' => $verificationResults['verification_methods'] ?? [],
                'reasons' => $verificationResults['reasons'] ?? [],
                'success' => $verificationResults['verified'] ?? false,
                'attempted_at' => now()->toDateTimeString(),
            ], $additionalData)
        );
    }
    
    /**
     * Log a payment error event.
     */
    public function logError(Payment $payment, \Exception $error, array $additionalData = [])
    {
        return $this->create(
            $payment,
            'error',
            array_merge([
                'error_message' => $error->getMessage(),
                'error_code' => $error->getCode(),
                'error_trace' => $error->getTraceAsString(),
                'error_at' => now()->toDateTimeString(),
            ], $additionalData)
        );
    }
    
    /**
     * Get complete payment history.
     */
    public function getPaymentHistory(Payment $payment)
    {
        return PaymentLog::forPayment($payment->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Summarize old logs to prevent database bloat.
     *
     * @param int $olderThanDays
     * @param int $batchSize
     * @return array
     */
    public function summarizeOldLogs(int $olderThanDays = 90, int $batchSize = 100)
    {
        $cutoffDate = now()->subDays($olderThanDays);
        $paymentIds = PaymentLog::where('created_at', '<', $cutoffDate)
            ->whereNotIn('action', ['log_summary'])
            ->select('payment_id')
            ->distinct()
            ->limit($batchSize)
            ->pluck('payment_id');
        
        $summarized = 0;
        foreach ($paymentIds as $paymentId) {
            PaymentLog::summarizeLogs($paymentId, $cutoffDate);
            $summarized++;
        }
        
        return [
            'processed_payments' => $summarized,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ];
    }
    
    /**
     * Export payment logs to CSV.
     */
    public function exportToCsv(array $paymentIds = [], array $actions = [], $startDate = null, $endDate = null)
    {
        $query = PaymentLog::query();
        
        if (!empty($paymentIds)) {
            $query->whereIn('payment_id', $paymentIds);
        }
        
        if (!empty($actions)) {
            $query->whereIn('action', $actions);
        }
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        $logs = $query->with(['payment', 'user', 'admin'])->get();
        
        // Generate CSV
        $csvFileName = 'payment_logs_' . now()->format('Y-m-d_His') . '.csv';
        $csvPath = storage_path('app/exports/' . $csvFileName);
        
        // Create directory if it doesn't exist
        if (!file_exists(dirname($csvPath))) {
            mkdir(dirname($csvPath), 0755, true);
        }
        
        $csv = fopen($csvPath, 'w');
        
        // Add CSV headers
        fputcsv($csv, [
            'ID', 'Payment ID', 'Reference Number', 'User', 'Admin', 
            'Action', 'Description', 'Status Before', 'Status After', 
            'Date', 'IP Address',
        ]);
        
        // Add data rows
        foreach ($logs as $log) {
            fputcsv($csv, [
                $log->id,
                $log->payment_id,
                $log->payment->reference_number ?? 'N/A',
                $log->user->name ?? 'N/A',
                $log->admin->name ?? 'N/A',
                $log->action,
                $log->action_description,
                $log->status_before,
                $log->status_after,
                $log->created_at->toDateTimeString(),
                $log->ip_address,
            ]);
        }
        
        fclose($csv);
        
        return [
            'path' => $csvPath,
            'filename' => $csvFileName,
            'count' => $logs->count(),
        ];
    }
} 