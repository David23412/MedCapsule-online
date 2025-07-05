<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a daily digest of pending payments to administrators';

    /**
     * Execute the console command.
     *
     * @param NotificationService $notificationService
     * @return int
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Generating payment digest...');

        try {
            // Count pending payments
            $pendingCount = Payment::where('status', 'pending_verification')->count();
            
            // Count high priority payments (based on admin_review_priority flag)
            $highPriorityCount = Payment::where('status', 'pending_verification')
                ->whereRaw("JSON_EXTRACT(payment_data, '$.admin_review_priority') = '\"high\"'")
                ->count();
            
            $this->info("Found {$pendingCount} pending payments ({$highPriorityCount} high priority)");
            
            if ($pendingCount > 0) {
                // Send digest notification
                $success = $notificationService->createDailyPaymentDigest($pendingCount, $highPriorityCount);
                
                if ($success) {
                    $this->info('Payment digest sent successfully');
                } else {
                    $this->error('Failed to send payment digest');
                }
            } else {
                $this->info('No pending payments to report');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error generating payment digest: ' . $e->getMessage());
            
            Log::error('Error generating payment digest', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
} 