<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'user:create';
    protected $description = 'Create a new regular user';

    public function handle()
    {
        $name = $this->ask('What is the user\'s name?');
        $email = $this->ask('What is the user\'s email?');
        $password = $this->secret('What is the user\'s password?');

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => false,
            ]);

            $this->info('User created successfully!');
            $this->info('Email: ' . $user->email);
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
        }
    }
} 