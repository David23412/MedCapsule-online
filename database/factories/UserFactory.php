<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // User role - randomly assign student, tutor, or admin
            'role' => fake()->randomElement([
                User::ROLE_STUDENT,
                User::ROLE_TUTOR,
                User::ROLE_ADMIN
            ]),

            // Profile information
            'university' => fake()->randomElement([
                'Harvard University',
                'Stanford University',
                'MIT',
                'University of California, Berkeley',
                'Yale University',
                'Princeton University',
                'Columbia University',
                'University of Chicago',
                'California Institute of Technology',
                'Massachusetts Institute of Technology'
            ]),

            'profile_picture_url' => fake()->randomElement([
                'https://images.pexels.com/photos/4386431/pexels-photo-4386431.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
                'https://images.pexels.com/photos/3777943/pexels-photo-3777943.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
                'https://images.pexels.com/photos/1181690/pexels-photo-1181690.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
                'https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop',
                'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop'
            ]),

            // Quiz statistics
            'correct_answers_count' => fake()->numberBetween(0, 500),
            'total_questions_attempted' => fake()->numberBetween(0, 1000),

            // XP and leveling system
            'xp' => fake()->numberBetween(0, 50000),

            // Study tracking
            'study_streak_days' => fake()->numberBetween(0, 365),
            'study_time_seconds' => fake()->numberBetween(0, 86400), // Up to 24 hours

            // Activity tracking
            'last_active_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => fake()->ipv4(),
            'last_user_agent' => fake()->userAgent(),

            // Security tracking
            'active_session_token' => Str::random(64),
            'session_created_at' => now(),
            'device_fingerprint' => hash('sha256', fake()->uuid()),
            'last_security_check' => now(),
            'concurrent_login_attempts' => fake()->numberBetween(0, 5),
            'last_suspicious_activity' => fake()->optional(0.1)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'name' => fake()->name() . ' (Admin)',
        ]);
    }

    /**
     * Create a tutor user.
     */
    public function tutor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_TUTOR,
            'name' => fake()->name() . ' (Tutor)',
        ]);
    }

    /**
     * Create a student user.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_STUDENT,
            'name' => fake()->name() . ' (Student)',
        ]);
    }

    /**
     * Create a user with high XP (experienced user).
     */
    public function experienced(): static
    {
        return $this->state(fn (array $attributes) => [
            'xp' => fake()->numberBetween(10000, 100000),
            'correct_answers_count' => fake()->numberBetween(200, 1000),
            'total_questions_attempted' => fake()->numberBetween(400, 2000),
            'study_streak_days' => fake()->numberBetween(50, 365),
        ]);
    }

    /**
     * Create a user with a long study streak.
     */
    public function dedicated(): static
    {
        return $this->state(fn (array $attributes) => [
            'study_streak_days' => fake()->numberBetween(100, 365),
            'study_time_seconds' => fake()->numberBetween(3600, 86400), // 1-24 hours
            'xp' => fake()->numberBetween(5000, 50000),
        ]);
    }

    /**
     * Create a user with suspicious activity flags.
     */
    public function suspicious(): static
    {
        return $this->state(fn (array $attributes) => [
            'concurrent_login_attempts' => fake()->numberBetween(3, 10),
            'last_suspicious_activity' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create a user with specific statistics for testing.
     */
    public function withStats(int $correctAnswers, int $totalQuestions, int $xp, int $streak): static
    {
        return $this->state(fn (array $attributes) => [
            'correct_answers_count' => $correctAnswers,
            'total_questions_attempted' => $totalQuestions,
            'xp' => $xp,
            'study_streak_days' => $streak,
        ]);
    }
}
