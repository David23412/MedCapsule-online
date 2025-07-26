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
            'display_order' => $this->faker->numberBetween(1, 20),
            'case_type' => $this->faker->randomElement(['quiz', 'cases', 'practical']),
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
     * Create a topic with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Create a topic with specific case type.
     */
    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_type' => 'quiz',
        ]);
    }

    /**
     * Create a cases topic.
     */
    public function cases(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_type' => 'cases',
        ]);
    }

    /**
     * Create a practical topic.
     */
    public function practical(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_type' => 'practical',
        ]);
    }
} 