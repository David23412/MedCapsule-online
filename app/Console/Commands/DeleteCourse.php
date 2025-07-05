<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;

class DeleteCourse extends Command
{
    protected $signature = 'course:delete {id}';
    protected $description = 'Delete a course by ID';

    public function handle()
    {
        $courseId = $this->argument('id');
        
        try {
            $course = Course::findOrFail($courseId);
            $courseName = $course->name;
            
            if ($this->confirm("Are you sure you want to delete course: {$courseName}? This will also delete all associated topics and questions.")) {
                $course->delete();
                $this->info("Course '{$courseName}' has been deleted successfully.");
            } else {
                $this->info('Delete operation cancelled.');
            }
        } catch (\Exception $e) {
            $this->error("Error: Unable to delete course. {$e->getMessage()}");
        }
    }
} 