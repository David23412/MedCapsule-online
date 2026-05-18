<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyAdminUser extends Command
{
    protected $signature = 'admin:verify';
    protected $description = 'Verify admin user details';

    public function handle()
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        if (!$admin) {
            $this->error('No admin user found!');
            return;
        }

        $this->info('Admin User Details:');
        $this->info('Name: ' . $admin->name);
        $this->info('Email: ' . $admin->email);
        $this->info('Created at: ' . $admin->created_at);
        $this->info('Last active: ' . $admin->last_active_at);
    }
} 
