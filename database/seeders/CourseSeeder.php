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
                'description' => 'Comprehensive course covering internal medicine topics including cardiology, pulmonology, gastroenterology, and more.',
                'image_path' => 'courses/internal-medicine.jpg',
                'price' =>  0.00,
                'academic_year' => 'YEAR 3 - SEMESTER 1',
            ],
            [
                'name' => 'Surgery',
                'description' => 'Essential surgical concepts and procedures for medical students and residents.',
                'image_path' => 'courses/surgery.jpg',
                'price' => 149.99,
                'academic_year' => 'YEAR 4 - SEMESTER 2',
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Child health and pediatric medicine fundamentals - free introductory course.',
                'image_path' => 'courses/pediatrics.jpg',
                'price' => 0.00, // Free course
                'academic_year' => 'YEAR 2 - SEMESTER 1',
            ],
            [
                'name' => 'Emergency Medicine',
                'description' => 'Critical care and emergency procedures for acute medical situations.',
                'image_path' => 'courses/emergency-medicine.jpg',
                'price' => 129.99,
                'academic_year' => 'YEAR 5 - SEMESTER 1',
            ],
            [
                'name' => 'Obstetrics & Gynecology',
                'description' => 'Women\'s health, pregnancy, and reproductive medicine.',
                'image_path' => 'courses/obgyn.jpg',
                'price' => 119.99,
                'academic_year' => 'YEAR 3 - SEMESTER 2',
            ]
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
