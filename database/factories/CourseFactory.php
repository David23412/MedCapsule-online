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
        $courseNames = [
            'Cardiovascular System',
            'Respiratory System', 
            'Nervous System',
            'Endocrine System',
            'Digestive System',
            'Musculoskeletal System',
            'Renal System',
            'Hematology',
            'Immunology',
            'Pharmacology',
            'Emergency Medicine',
            'Internal Medicine',
            'Surgery',
            'Pediatrics',
            'Obstetrics & Gynecology'
        ];

        $courseName = $this->faker->randomElement($courseNames);
        
        return [
            'name' => $courseName,
            'description' => $this->faker->paragraph(3),
            'image' => 'courses/' . Str::slug($courseName) . '.jpg',
            'color' => $this->faker->randomElement(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4']),
            'is_paid' => $this->faker->boolean(70), // 70% chance of being paid
            'price' => $this->faker->randomFloat(2, 0, 299.99),
            'currency' => 'USD',
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the course is free.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
            'price' => 0.00,
        ]);
    }

    /**
     * Indicate that the course is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
            'price' => $this->faker->randomFloat(2, 29.99, 299.99),
        ]);
    }

    /**
     * Create a course with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'image' => 'courses/' . Str::slug($name) . '.jpg',
        ]);
    }
} 