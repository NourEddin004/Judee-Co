<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the old and the new address when the admin's sign-in email or password changes. */
class SignInDetailsChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** The first try plus 3 retries, like the order emails (REQ-DB-14). */
    public int $tries = 4;

    /** @var list<int> seconds before each retry */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $name,
        public readonly ?string $newEmail,
        public readonly bool $passwordChanged,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $what = match (true) {
            $this->newEmail !== null && $this->passwordChanged => 'the sign-in email and the password were',
            $this->newEmail !== null => 'the sign-in email was',
            default => 'the password was',
        };

        $mail = (new MailMessage)
            ->subject('Your JUDE store admin sign-in details changed')
            ->greeting('Hi '.$this->name.',')
            ->line('On your JUDE store admin account, '.$what.' just changed.')
            ->line('When: '.now()->timezone('Asia/Amman')->format('j M Y, H:i').' (Jordan time)');

        if ($this->newEmail !== null) {
            $mail->line('The new sign-in email is '.$this->newEmail.'.');
        }

        return $mail
            ->line('If this was you, there is nothing to do. If it was not, contact your website developer straight away.');
    }
}
