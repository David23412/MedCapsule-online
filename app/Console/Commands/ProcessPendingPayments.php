<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class ProcessPendingPayments extends Command
{
    protected $signature = 'payments:process-pending {--limit=50}';
    protected $description = 'Clean up old pending payments';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        // Mark old pending payments as expired (older than 24 hours)
        $expired = Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->limit($limit)
            ->update(['status' => 'expired']);
            
        $this->info("Marked {$expired} old payments as expired");
        
        return 0;
    }
}