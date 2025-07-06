<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topicNames = [
            'Anatomy and Physiology',
            'Pathophysiology',
            'Clinical Assessment',
            'Diagnostic Procedures',
            'Treatment Modalities',
            'Pharmacological Interventions',
            'Patient Management',
            'Emergency Procedures',
            'Preventive Care',
            'Evidence-Based Practice',
            'Clinical Decision Making',
            'Patient Education',
            'Interprofessional Collaboration',
            'Quality Improvement',
            'Research Methodology'
        ];

        return [
            'name' => $this->faker->randomElement($topicNames),
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'description' => $this->faker->paragraph(2),
            'display_order' => $this->faker->numberBetween(1, 20),
            'case_type' => $this->faker->randomElement(['quiz', 'cases', 'practical']),
            'percentage_grade' => $this->faker->optional(0.7)->randomFloat(2, 0, 100),
            'last_attempt_date' => $this->faker->optional(0.6)->dateTimeBetween('-6 months', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create a topic for a specific course.
     */
    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
        ]);
    }

    /**
     * Create a topic for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a topic with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Create a mastered topic (100% grade).
     */
    public function mastered(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage_grade' => 100.0,
            'last_attempt_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a weak topic (< 60% grade).
     */
    public function weak(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage_grade' => $this->faker->randomFloat(2, 0, 59.99),
            'last_attempt_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Create a good topic (60-79% grade).
     */
    public function good(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage_grade' => $this->faker->randomFloat(2, 60, 79.99),
            'last_attempt_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Create a great topic (80-99% grade).
     */
    public function great(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage_grade' => $this->faker->randomFloat(2, 80, 99.99),
            'last_attempt_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ]);
    }
} 