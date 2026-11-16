<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the owner when the server's disk is 80% full (REQ-CST-10). */
class DiskAlmostFull extends Notification implements ShouldQueue
{
    use Queueable;

    /** The first try plus 3 retries, like the order emails (REQ-DB-14). */
    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly float $usedPercent, public readonly float $freeBytes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $percent = rtrim(rtrim(number_format($this->usedPercent, 1), '0'), '.');
        $free = number_format($this->freeBytes / 1024 ** 3, 1);

        return (new MailMessage)
            ->subject("The store's server disk is {$percent}% full")
            ->greeting('Hi '.$notifiable->name.',')
            ->line("The disk on the store's server is {$percent}% full. {$free} GB is left.")
            ->line('When it is full, new photos and orders cannot be saved.')
            ->line('Please ask whoever looks after the hosting to free some space or move the server to a bigger plan.');
    }
}
