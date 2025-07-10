<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quizAttempt;
    public $score;
    public $totalQuestions;
    public $percentage;

    /**
     * Create a new event instance.
     */
    public function __construct(QuizAttempt $quizAttempt, int $score, int $totalQuestions, float $percentage)
    {
        $this->quizAttempt = $quizAttempt;
        $this->score = $score;
        $this->totalQuestions = $totalQuestions;
        $this->percentage = $percentage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('quiz.attempt'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'quiz_attempt_id' => $this->quizAttempt->id,
            'user_id' => $this->quizAttempt->user_id,
            'topic_id' => $this->quizAttempt->topic_id,
            'score' => $this->score,
            'total_questions' => $this->totalQuestions,
            'percentage' => $this->percentage,
            'timestamp' => now()->toISOString(),
        ];
    }
} 