<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $userId;
    public $courseIds;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, int $userId, array $courseIds = [])
    {
        $this->payment = $payment;
        $this->userId = $userId;
        $this->courseIds = $courseIds;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payment.completed'),
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
            'payment_id' => $this->payment->id,
            'user_id' => $this->userId,
            'course_ids' => $this->courseIds,
            'amount' => $this->payment->amount,
            'timestamp' => now()->toISOString(),
        ];
    }
} 