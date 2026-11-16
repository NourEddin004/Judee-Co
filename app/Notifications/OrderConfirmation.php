<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The customer's copy of the order, sent by the server through the queue (REQ-CHK-19, REQ-DMO-76),
 * in the language the customer used on the site (REQ-CHK-20), worded as in REQ-DMO-77.
 */
class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    /** The first try plus 3 retries (REQ-DB-14). */
    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly Order $order)
    {
        $this->locale($order->language);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('store.email.subject', ['number' => $this->order->number]))
            ->view(['mail.order-confirmation', 'mail.order-confirmation-text'], ['order' => $this->order]);
    }
}
