<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MistakesNeedReview
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public int $mistakeCount;
    public Collection $mistakes;
    public array $topicBreakdown;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $mistakeCount, Collection $mistakes, array $topicBreakdown = [])
    {
        $this->user = $user;
        $this->mistakeCount = $mistakeCount;
        $this->mistakes = $mistakes;
        $this->topicBreakdown = $topicBreakdown;
    }
}