<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Create an admin user or make existing user admin';

    public function handle()
    {
        $email = $this->ask('What is the admin email?');
        
        // Check if user exists
        $user = User::where('email', $email)->first();
        
        if ($user) {
            if ($this->confirm('User already exists. Make this user admin?')) {
                $user->update(['is_admin' => true]);
                $this->info('User is now an admin!');
                return;
            }
        }

        // Create new user
        $name = $this->ask('What is the admin name?');
        $password = $this->secret('What is the admin password?');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info('Admin user created successfully!');
        $this->info('Email: ' . $user->email);
    }
} 