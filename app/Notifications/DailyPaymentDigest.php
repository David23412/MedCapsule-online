<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyPaymentDigest extends Notification
{
    use Queueable;

    /**
     * The number of pending payments.
     *
     * @var int
     */
    protected $pendingCount;

    /**
     * The number of high priority payments.
     *
     * @var int
     */
    protected $highPriorityCount;

    /**
     * Create a new notification instance.
     *
     * @param int $pendingCount
     * @param int $highPriorityCount
     * @return void
     */
    public function __construct(int $pendingCount, int $highPriorityCount)
    {
        $this->pendingCount = $pendingCount;
        $this->highPriorityCount = $highPriorityCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('admin.payment-history', ['status' => 'pending_verification']);

        return (new MailMessage)
            ->subject('Daily Payment Digest')
            ->line('Here is your daily payment verification digest.')
            ->line("There are {$this->pendingCount} payments awaiting verification.")
            ->lineIf($this->highPriorityCount > 0, "Of these, {$this->highPriorityCount} are high priority.")
            ->action('View Pending Payments', $url)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'daily_digest',
            'pending_count' => $this->pendingCount,
            'high_priority_count' => $this->highPriorityCount,
            'date' => now()->toDateString(),
            'url' => route('admin.payment-history', ['status' => 'pending_verification']),
        ];
    }
} 