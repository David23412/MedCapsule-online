<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Internal Medicine',
                'description' => 'Comprehensive course covering internal medicine topics',
                'image_path' => 'courses/internal-medicine.jpg',
            ],
            [
                'name' => 'Surgery',
                'description' => 'Essential surgical concepts and procedures',
                'image_path' => 'courses/surgery.jpg',
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Child health and pediatric medicine',
                'image_path' => 'courses/pediatrics.jpg',
            ]
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
