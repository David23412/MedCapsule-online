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
        User::create([
            'name' => 'David George',
            'email' => 'geodavid627@gmail.com',
            'password' => Hash::make('Einstein234'),
            'is_admin' => true,
        ]);

        // Create regular user
        User::create([
            'name' => 'Tuna User',
            'email' => 'tuna@gmail.com',
            'password' => Hash::make('Einstein234'),
            'is_admin' => false,
        ]);
    }
}
