<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteToAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:promote-admin {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to admin status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $email = $this->ask('Enter the email address of the user to promote to admin');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with email: {$email}");
            return 1;
        }

        if ($user->isAdmin()) {
            $this->info("User {$email} is already an admin!");
            return 0;
        }

        $user->update(['role' => User::ROLE_ADMIN]);
        $this->info("Successfully promoted {$email} to admin!");
        
        return 0;
    }
}
