<?php

namespace App\Notifications;

use App\Models\KnownDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the owner when her account logs in from a new device (REQ-SEC-23). */
class NewDeviceLogin extends Notification implements ShouldQueue
{
    use Queueable;

    /** The first try plus 3 retries, like the order emails (REQ-DB-14). */
    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly KnownDevice $device) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New sign-in to the JUDE store admin')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your admin account was just used to sign in from a device we have not seen before.')
            ->line('When: '.$this->device->last_seen_at->timezone('Asia/Amman')->format('j M Y, H:i').' (Jordan time)')
            ->line('Browser: '.($this->device->user_agent ?: 'Unknown'))
            ->line('IP address: '.($this->device->ip_address ?: 'Unknown'))
            ->line('If this was you, there is nothing to do. If it was not, reset your password straight away.')
            ->action('Reset password', url('/admin/password-reset/request'));
    }
}
