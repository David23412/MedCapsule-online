<?php

namespace App\Console\Commands;

use App\Services\PaymentLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SummarizePaymentLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:summarize-logs 
                            {--days=90 : Number of days before logs are summarized}
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            {--batch=100 : Number of payments to process in one batch}
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
                            {--force : Force summarization without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summarize old payment logs to prevent database bloat';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PaymentLogService $logService)
    {
        $days = $this->option('days');
        $batchSize = $this->option('batch');
        $force = $this->option('force');

        $this->info("Preparing to summarize payment logs older than {$days} days");
        $this->info("Batch size: {$batchSize} payments");

        // Confirm unless --force is used
        if (!$force && !$this->confirm('Do you wish to continue?')) {
            $this->info('Operation cancelled');
            return 0;
        }

        try {
            $this->output->progressStart(100);
            
            // Get the count of payments that have logs older than the cutoff
            $cutoffDate = now()->subDays($days);
            $totalPaymentsWithOldLogs = \App\Models\PaymentLog::where('created_at', '<', $cutoffDate)
                ->whereNotIn('action', ['log_summary'])
                ->select('payment_id')
                ->distinct()
                ->count();
            
            $this->output->progressDisplay(0);
            
            if ($totalPaymentsWithOldLogs === 0) {
                $this->info("No payments found with logs older than {$days} days.");
                $this->output->progressFinish();
                return 0;
            }
            
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $this->info("Found {$totalPaymentsWithOldLogs} payments with logs to summarize");
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
            
            $processedPayments = 0;
            $totalSummarized = 0;
            
            // Process in batches
            while ($processedPayments < $totalPaymentsWithOldLogs) {
                $result = $logService->summarizeOldLogs($days, $batchSize);
                $summarized = $result['processed_payments'];
                $totalSummarized += $summarized;
                $processedPayments += $batchSize;
                
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            // Update progress
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
                $progress = min(100, round(($processedPayments / $totalPaymentsWithOldLogs) * 100));
                $this->output->progressAdvance($progress - $this->output->getProgressBar()->getProgress());
                
                // Break if we've processed fewer than the batch size (meaning we're done)
                if ($summarized < $batchSize) {
                    break;
                }
            }
            
            $this->output->progressFinish();
            $this->info("Summarized logs for {$totalSummarized} payments");
            
            // Log the operation
            Log::info("Payment logs summarized", [
                'days' => $days,
                'payments_processed' => $totalSummarized,
                'batch_size' => $batchSize,
                'cutoff_date' => $cutoffDate->toDateTimeString()
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->output->progressFinish();
            $this->error("Error summarizing logs: {$e->getMessage()}");
            
            Log::error("Error summarizing payment logs", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
