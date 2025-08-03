<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Command;

class SendPaymentDigest extends Command
{
    protected $signature = 'payments:send-digest';
    protected $description = 'Send daily payment digest to admins';

    public function handle()
    {
        $pendingCount = Payment::where('status', 'pending')->count();
        
        if ($pendingCount > 0) {
            $this->info("Found {$pendingCount} pending payments");
            // Simple notification logic here if needed
        } else {
            $this->info('No pending payments');
        }
        
        return 0;
    }
}