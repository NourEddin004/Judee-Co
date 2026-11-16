<?php

namespace App\Domain\Auth;

use App\Domain\Activity\ActivityLogger;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

/**
 * Five wrong passwords in a row lock an admin account for 15 minutes (REQ-SEC-02).
 * A successful login clears the count and is written to the activity log (C.7).
 */
class LockAfterFailedLogins
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function failed(Failed $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->isLocked()) {
            return;
        }

        $user->failed_logins++;

        if ($user->failed_logins >= User::MAX_FAILED_LOGINS) {
            $user->failed_logins = 0;
            $user->locked_until = now()->addMinutes(User::LOCK_MINUTES);
        }

        $user->saveQuietly();
    }

    public function succeeded(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $user->forceFill(['failed_logins' => 0, 'locked_until' => null])->saveQuietly();
        $this->logger->record('logged_in', $user);
    }
}
