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
            'topic_id' => fn() => Topic::factory()->create()->id,
            'course_id' => fn() => Course::factory()->create()->id,
            'title' => $this->faker->sentence(3),
            'deadline' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'assignment_mode' => $this->faker->randomElement([
                Assignment::MODE_SOURCE_SPECIFIC,
                Assignment::MODE_COMPLETE_TOPIC
            ]),
            'assigned_source' => null, // Will be set in sourceSpecific() state
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }


    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }


    public function dueWithinHours(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => now()->addHours($this->faker->numberBetween(1, $hours)),
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
    
    public function withDuration(?int $minutes = null): static
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