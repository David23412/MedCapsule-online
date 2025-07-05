<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Payment;

class PaymentRequiresAttention extends Notification
{
    use Queueable;

    /**
     * The payment instance.
     *
     * @var \App\Models\Payment
     */
    protected $payment;

    /**
     * The reason for attention.
     *
     * @var string
     */
    protected $reason;

    /**
     * The priority level.
     *
     * @var string
     */
    protected $priority;

    /**
     * Additional data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\Payment $payment
     * @param string $reason
     * @param string $priority
     * @param array $data
     * @return void
     */
    public function __construct(Payment $payment, string $reason, string $priority = 'medium', array $data = [])
    {
        $this->payment = $payment;
        $this->reason = $reason;
        $this->priority = $priority;
        $this->data = $data;
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
        $url = route('admin.payment-details', ['payment' => $this->payment->id]);

        return (new MailMessage)
            ->subject('Payment Requires Attention')
            ->line('A payment requires your attention.')
            ->line('Reason: ' . $this->reason)
            ->line('Reference: ' . $this->payment->reference_number)
            ->line('Amount: ' . $this->payment->amount)
            ->action('View Payment', $url)
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
            'payment_id' => $this->payment->id,
            'reference_number' => $this->payment->reference_number,
            'user_id' => $this->payment->user_id,
            'user_name' => $this->payment->user->name ?? 'Unknown User',
            'course_id' => $this->payment->course_id,
            'course_name' => $this->payment->course->name ?? 'Unknown Course',
            'amount' => $this->payment->amount,
            'reason' => $this->reason,
            'priority' => $this->priority,
            'data' => $this->data,
            'url' => route('admin.payment-details', ['payment' => $this->payment->id]),
        ];
    }
} 