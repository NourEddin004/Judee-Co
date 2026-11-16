<?php

namespace App\Notifications;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Every new order, sent to the owner's order email through the queue (REQ-CHK-18, REQ-DB-13). */
class NewOrderForOwner extends Notification implements ShouldQueue
{
    use Queueable;

    /** The first try plus 3 retries (REQ-DB-14). */
    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly Order $order)
    {
        // The admin is in English, whatever language the customer used.
        $this->locale('en');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order #{$this->order->number}")
            ->view(['mail.new-order', 'mail.new-order-text'], [
                'order' => $this->order,
                'adminUrl' => OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'),
            ]);
    }
}
