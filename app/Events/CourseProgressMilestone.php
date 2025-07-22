<?php

namespace App\Events;

use App\Models\User;
use App\Models\Course;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseProgressMilestone
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Course $course;
    public float $progressPercentage;
    public float $previousProgress;
    public bool $isCompletion;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Course $course, float $progressPercentage, float $previousProgress = 0)
    {
        $this->user = $user;
        $this->course = $course;
        $this->progressPercentage = $progressPercentage;
        $this->previousProgress = $previousProgress;
        $this->isCompletion = $progressPercentage >= 100;
    }
}