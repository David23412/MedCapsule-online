<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\User;

class RandomQuizTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create test user
        $user = User::where('email', 'geodavid627@gmail.com')->first();
        
        if (!$user) {
            $this->command->error('Test user not found!');
            return;
        }

        // Get all courses
        $courses = Course::all();

        foreach ($courses as $course) {
            // Ensure user is enrolled in the course
            if (!$user->courses()->where('course_id', $course->id)->exists()) {
                $user->courses()->attach($course->id, ['is_enrolled' => true]);
            }

            // Create multiple test topics per course
            for ($topicNum = 1; $topicNum <= 3; $topicNum++) {
                $topic = Topic::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'name' => "Random Quiz Test Topic {$topicNum}"
                    ],
                    [

                        'case_type' => 'quiz',
                        'display_order' => $topicNum
                    ]
                );

                // Add 10 test questions per topic
                for ($i = 1; $i <= 10; $i++) {
                    Question::create([
                        'topic_id' => $topic->id,
                        'question' => "Test Question {$i} for {$course->name} - Topic {$topicNum}",
                        'option_a' => "Option A for question {$i}",
                        'option_b' => "Option B for question {$i}",
                        'option_c' => "Option C for question {$i}",
                        'option_d' => "Option D for question {$i}",
                        'correct_answer' => 'A',
                        'explanation' => "This is a test explanation for question {$i} in {$course->name} - Topic {$topicNum}"
                    ]);
                }
            }
        }

        $this->command->info('Test questions added successfully!');
        $this->command->info('Created 30 questions per course across 3 topics.');
        $this->command->info('Ensured user enrollment in all courses.');
    }
}
