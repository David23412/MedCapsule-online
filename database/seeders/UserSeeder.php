<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        User::firstOrCreate(
            ['email' => 'geodavid627@gmail.com'],
            [
                'name' => 'MedCapsule',
                'password' => Hash::make('Einstein234'),
                'role' => User::ROLE_STUDENT,
                'email_verified_at' => now(),
                'xp' => 2500, // Give some XP for demo
                'study_streak_days' => 15,
                'correct_answers_count' => 85,
                'total_questions_attempted' => 120,
                'show_on_leaderboard' => true
            ]
        );

        // Create regular user
        User::firstOrCreate(
            ['email' => 'tuna@gmail.com'],
            [
                'name' => 'Tuna',
                'password' => Hash::make('Einstein234'),
                'role' => User::ROLE_STUDENT,
                'email_verified_at' => now()
            ]
        );

        $this->command->info('Users seeded successfully.');
    }
}
