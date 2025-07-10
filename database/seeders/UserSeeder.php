<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'geodavid627@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Einstein234'),
                'is_admin' => true,
                'email_verified_at' => now()
            ]
        );

        // Create regular user
        User::firstOrCreate(
            ['email' => 'tuna@gmail.com'],
            [
                'name' => 'Tuna',
                'password' => Hash::make('Einstein234'),
                'is_admin' => false,
                'email_verified_at' => now()
            ]
        );

        $this->command->info('Users seeded successfully.');
    }
}
