<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $numTopics = rand(5, 10);
            
            for ($i = 0; $i < $numTopics; $i++) {
                // Determine type based on position
                $type = match ($i % 3) {
                    0 => 'quiz',     // Regular topic quiz
                    1 => 'cases',    // Clinical case
                    2 => 'practical' // Practical exercise
                };

                Topic::create([
                    'course_id' => $course->id,
                    'name' => "Topic " . ($i + 1) . " for " . $course->name,
                    'description' => "This is a sample topic for " . $course->name,
                    'case_type' => $type,
                    'display_order' => $i,
                ]);
            }
        }
    }
}
