<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SkippedQuestionsDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public int $skippedCount;
    public array $topicBreakdown;
    public \Carbon\Carbon $detectedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $skippedCount, array $topicBreakdown = [])
    {
        $this->user = $user;
        $this->skippedCount = $skippedCount;
        $this->topicBreakdown = $topicBreakdown;
        $this->detectedAt = now();
    }
}