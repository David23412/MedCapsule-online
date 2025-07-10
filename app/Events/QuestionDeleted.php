<?php

namespace App\Events;

use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $question;
    public $topicId;
    public $source;

    /**
     * Create a new event instance.
     */
    public function __construct(Question $question, int $topicId, ?string $source = null)
    {
        $this->question = $question;
        $this->topicId = $topicId;
        $this->source = $source;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('question.deletion'),
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
            'question_id' => $this->question->id,
            'topic_id' => $this->topicId,
            'source' => $this->source,
            'timestamp' => now()->toISOString(),
        ];
    }
} 