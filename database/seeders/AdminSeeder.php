<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $email = 'admin@admin.com';
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->role !== 'admin') {
                $this->command->info('User exists. Making them admin...');
                $user->update(['role' => 'admin']);
                $this->command->info('User is now an admin!');
            } else {
                $this->command->info('User already exists and is already an admin!');
            }
        } else {
            User::create([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'admin'
            ]);
            $this->command->info('Admin user created successfully!');
        }
    }
} 