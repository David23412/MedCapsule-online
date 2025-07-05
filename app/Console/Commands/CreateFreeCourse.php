<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;

class CreateFreeCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-free-course';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a FREE course if it does not exist for automatic enrollment of new users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking if FREE course exists...');
        
        $freeCourse = Course::where('name', 'FREE')->first();
        
        if ($freeCourse) {
            $this->info('FREE course already exists with ID: ' . $freeCourse->id);
            return 0;
        }
        
        $this->info('Creating FREE course...');
        
        try {
            $course = Course::create([
                'name' => 'FREE',
                'description' => 'This is a free course available to all new users. It contains introductory medical content.',
                'image' => 'https://i.imgur.com/example-image.jpg', // Replace with an actual image URL
                'color' => '#28a745', // Green color
                'semester' => 'Year 1- First semester', // Adjust as needed for your semester structure
            ]);
            
            $this->info('FREE course created successfully with ID: ' . $course->id);
            $this->info('All new users will automatically be enrolled in this course.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create FREE course: ' . $e->getMessage());
            return 1;
        }
    }
}
