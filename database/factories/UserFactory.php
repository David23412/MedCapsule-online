<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'remember_token' => Str::random(10),
            'university' => $this->faker->company(),
            'role' => 'student',
            'profile_picture_url' => null,
            'last_active_at' => now(),
            'correct_answers_count' => $this->faker->numberBetween(0, 1000),
            'total_questions_attempted' => $this->faker->numberBetween(0, 1500),
            'xp' => $this->faker->numberBetween(0, 5000),
            'study_streak_days' => $this->faker->numberBetween(0, 30),
            'study_time_seconds' => $this->faker->numberBetween(0, 36000),
            'active_session_token' => null,
            'session_created_at' => null,
            'device_fingerprint' => null,
            'last_login_ip' => null,
            'last_user_agent' => null,
            'last_security_check' => null,
            'concurrent_login_attempts' => 0,
            'last_suspicious_activity' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function tutor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'tutor',
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
        ]);
    }

    public function withActiveSession(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_session_token' => 'session_' . Str::random(32),
            'session_created_at' => now(),
            'device_fingerprint' => hash('sha256', 'device_' . Str::random(16)),
            'last_login_ip' => $this->faker->ipv4(),
            'last_user_agent' => $this->faker->userAgent(),
            'last_security_check' => now(),
        ]);
    }
}