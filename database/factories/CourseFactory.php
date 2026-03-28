<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $courseName = fake()->randomElement([
            'Anatomy & Physiology',
            'Pathology Fundamentals',
            'Clinical Medicine',
            'Pharmacology Basics',
            'Medical Biochemistry',
            'Microbiology Essentials',
            'Cardiology Principles',
            'Neurology Fundamentals',
            'Surgery Basics',
            'Internal Medicine',
            'Pediatrics Essentials',
            'Obstetrics & Gynecology',
            'Emergency Medicine',
            'Radiology Interpretation',
            'Laboratory Medicine'
        ]);

        return [
            'name' => $courseName,
            'description' => fake()->paragraph(3),
            'type' => 'medical',
            'price' => fake()->randomElement([0, 49.99, 99.99, 149.99, 199.99, 299.99]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a free course.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
            'access_type' => \App\Models\Course::ACCESS_TYPE_FREE,
            'type' => 'medical',
        ]);
    }

    /**
     * Create a preview course.
     */
    public function preview(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
            'access_type' => \App\Models\Course::ACCESS_TYPE_PREVIEW,
            'type' => 'medical',
        ]);
    }

    /**
     * Create a premium course.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomElement([99.99, 149.99, 199.99, 299.99]),
            'access_type' => \App\Models\Course::ACCESS_TYPE_PAID,
            'type' => 'medical',
        ]);
    }

    /**
     * Create a featured course.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_popular' => true,
            'price' => fake()->randomElement([149.99, 199.99, 299.99]),
        ]);
    }

    /**
     * Create an inactive course.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a course with specific enrollment count.
     */
    public function withEnrollments(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'total_enrollments' => $count,
        ]);
    }

    /**
     * Create a course with high rating.
     */
    public function highlyRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'average_rating' => fake()->randomFloat(1, 4.5, 5.0),
            'total_enrollments' => fake()->numberBetween(100, 1000),
        ]);
    }

    /**
     * Create a beginner-level course.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'beginner',
            'estimated_duration_hours' => fake()->numberBetween(10, 50),
            'price' => fake()->randomElement([0, 49.99, 79.99]),
        ]);
    }

    /**
     * Create an advanced-level course.
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'advanced',
            'estimated_duration_hours' => fake()->numberBetween(100, 200),
            'price' => fake()->randomElement([199.99, 299.99, 399.99]),
            'prerequisites' => json_encode([
                'Intermediate medical knowledge',
                'Clinical experience',
                'Advanced coursework completion'
            ]),
        ]);
    }
}
