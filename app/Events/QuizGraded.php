<?php

namespace App\Events;

use App\Models\User;
use App\Models\Topic;
use App\Models\QuizAttempt;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizGraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Topic $topic;
    public QuizAttempt $quizAttempt;
    public array $gradingResult;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Topic $topic, QuizAttempt $quizAttempt, array $gradingResult)
    {
        $this->user = $user;
        $this->topic = $topic;
        $this->quizAttempt = $quizAttempt;
        $this->gradingResult = $gradingResult;
    }
}