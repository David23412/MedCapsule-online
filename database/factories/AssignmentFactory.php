<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'tutor_id' => User::factory()->create(['role' => 'tutor'])->id,
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'topic_id' => Topic::factory()->create()->id,
            'course_id' => Course::factory()->create()->id,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'deadline' => $this->faker->dateTimeBetween('now', '+30 days'),
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'time_limit' => $this->faker->optional(0.7)->numberBetween(15, 120), // 70% chance of having a time limit
            'assignment_mode' => $this->faker->randomElement([
                Assignment::MODE_SOURCE_SPECIFIC,
                Assignment::MODE_COMPLETE_TOPIC
            ]),
            'assigned_source' => null, // Will be set in sourceSpecific() state
            'status' => $this->faker->randomElement([
                Assignment::STATUS_ACTIVE,
                Assignment::STATUS_COMPLETED,
                Assignment::STATUS_OVERDUE,
                Assignment::STATUS_LOCKED,
                Assignment::STATUS_EXPIRED
            ]),
            'completed_at' => null,
            'expired_at' => null,
            'score' => null,
            'score_percentage' => null
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Assignment::STATUS_ACTIVE,
            'deadline' => $this->faker->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
            'score' => null
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Assignment::STATUS_COMPLETED,
            'completed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'score' => $this->faker->numberBetween(0, 100)
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Assignment::STATUS_OVERDUE,
            'deadline' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'completed_at' => null,
            'score' => null
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Assignment::STATUS_LOCKED,
            'completed_at' => null,
            'score' => null
        ]);
    }

    public function dueWithinHours(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => now()->addHours($this->faker->numberBetween(1, $hours)),
            'status' => Assignment::STATUS_ACTIVE
        ]);
    }
    
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Assignment::STATUS_EXPIRED,
            'expired_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'completed_at' => null,
            'score' => $this->faker->optional(0.7)->numberBetween(0, 100),
            'score_percentage' => $this->faker->optional(0.7)->randomFloat(2, 0, 100)
        ]);
    }
    
    public function sourceSpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'assignment_mode' => Assignment::MODE_SOURCE_SPECIFIC,
            'assigned_source' => $this->faker->randomElement(['Kaplan', 'Practice Sheet', 'UWorld', 'First Aid'])
        ]);
    }
    
    public function completeTopic(): static
    {
        return $this->state(fn (array $attributes) => [
            'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC,
            'assigned_source' => null
        ]);
    }
    
    public function withDuration(int $minutes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $minutes ?? $this->faker->numberBetween(15, 120)
        ]);
    }
    
    public function withoutDuration(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => null
        ]);
    }
}