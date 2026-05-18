<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class FastSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Create users quickly
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@medcapsule.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Test Tutor',
                'email' => 'tutor@medcapsule.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TUTOR,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Test Student',
                'email' => 'student@medcapsule.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        DB::table('users')->insert($users);
        
        // Create a test course
        $courseId = DB::table('courses')->insertGetId([
            'name' => 'Medical Fundamentals',
            'description' => 'Basic medical knowledge course',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create a test topic
        $topicId = DB::table('topics')->insertGetId([
            'name' => 'Anatomy Basics',
            'course_id' => $courseId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create test questions
        $questions = [
            [
                'question' => 'What is the largest organ in the human body?',
                'option_a' => 'Heart',
                'option_b' => 'Liver',
                'option_c' => 'Skin',
                'option_d' => 'Brain',
                'correct_answer' => 'C',
                'topic_id' => $topicId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'question' => 'How many chambers does a human heart have?',
                'option_a' => '2',
                'option_b' => '3',
                'option_c' => '4',
                'option_d' => '5',
                'correct_answer' => 'C',
                'topic_id' => $topicId,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        DB::table('questions')->insert($questions);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('Fast seeding completed successfully!');
    }
}