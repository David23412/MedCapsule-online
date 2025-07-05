<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendAnnouncementNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:announce {title} {message} {--admin-only : Send only to admin users} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an announcement notification to all users';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $title = $this->argument('title');
        $message = $this->argument('message');
        $adminOnly = $this->option('admin-only');
        $force = $this->option('force');
        
        // Confirm action unless force flag is used
        if (!$force && !$this->confirm("Are you sure you want to send an announcement to " . 
            ($adminOnly ? "all admin users" : "ALL users") . 
            " with title '{$title}' and message '{$message}'?")) {
            $this->info('Operation cancelled.');
            return Command::FAILURE;
        }
        
        // Get users to send to
        $query = User::query();
        if ($adminOnly) {
            $query->where('is_admin', true);
        }
        $users = $query->get();
        
        $this->info("Sending announcement to {$users->count()} users...");
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        $successful = 0;
        
        foreach ($users as $user) {
            try {
                $notificationService->createNotification(
                    $user->id,
                    'announcement',
                    $title,
                    $message,
                    [
                        'is_admin_announcement' => true,
                        'sent_at' => now()->toIso8601String()
                    ]
                );
                $successful++;
            } catch (\Exception $e) {
                $this->error("Failed to send notification to user ID {$user->id}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Announcement successfully sent to {$successful} out of {$users->count()} users.");
        
        return Command::SUCCESS;
    }
}
