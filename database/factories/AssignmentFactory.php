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
            'student_id' => User::factory()->create(['role' => 'student'])->id,
            'topic_id' => Topic::factory()->create()->id,
            'course_id' => Course::factory()->create()->id,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'deadline' => $this->faker->dateTimeBetween('now', '+30 days'),
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'status' => $this->faker->randomElement([
                Assignment::STATUS_ACTIVE,
                Assignment::STATUS_COMPLETED,
                Assignment::STATUS_OVERDUE,
                Assignment::STATUS_LOCKED
            ]),
            'completed_at' => null,
            'score' => null
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
}