<?php

namespace App\Domain\Auth;

use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Cookie\QueueingFactory as Cookies;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Each browser gets a long-lived random device cookie. When the owner's account logs in
 * from a browser it has not seen before, she gets an email (REQ-SEC-23).
 */
class AlertOwnerOfNewDevice
{
    public const COOKIE = 'jude_device';

    private const COOKIE_MINUTES = 60 * 24 * 365 * 2;

    public function __construct(private readonly Request $request, private readonly Cookies $cookies) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $token = (string) $this->request->cookie(self::COOKIE);

        if (strlen($token) !== 40) {
            $token = Str::random(40);
            $this->cookies->queue(self::COOKIE, $token, self::COOKIE_MINUTES);
        }

        $device = $user->knownDevices()->firstOrNew(['token_hash' => hash('sha256', $token)]);
        $isNew = ! $device->exists;

        $device->fill([
            'user_agent' => Str::limit((string) $this->request->userAgent(), 250),
            'ip_address' => $this->request->ip(),
            'last_seen_at' => now(),
        ])->save();

        if ($isNew && $user->isOwner()) {
            $user->notify(new NewDeviceLogin($device));
        }
    }
}
