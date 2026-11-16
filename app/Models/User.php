<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SensitiveParameter;

/**
 * An admin account. Only the owner signs in (SRS 3.6). Staff accounts were removed in version 1.2;
 * any left from before stay for the activity log, but cannot sign in.
 */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /** The one account that may open the admin (assumption AS-4). */
    public const OWNER = 'owner';

    /** Wrong passwords in a row before the account locks, and for how long (REQ-SEC-02). */
    public const MAX_FAILED_LOGINS = 5;

    public const LOCK_MINUTES = 15;

    protected $fillable = ['name', 'email', 'password'];

    /** Column defaults, so a new account behaves the same before and after it is reloaded. */
    protected $attributes = [
        'role' => 'staff',                  // the column's default: an account that cannot sign in
        'failed_logins' => 0,
        'locked_until' => null,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',                       // REQ-SEC-03
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === self::OWNER;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /** Only the owner opens the admin (REQ-USR-01), and not while her account is locked (REQ-SEC-02). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isOwner() && ! $this->isLocked();
    }

    public function knownDevices(): HasMany
    {
        return $this->hasMany(KnownDevice::class);
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->two_factor_secret = $secret;
        $this->two_factor_confirmed_at = $secret ? now() : null;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->two_factor_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->two_factor_recovery_codes = $codes;
        $this->save();
    }

    /** Fields never copied into the activity log. */
    public function activityLogHidden(): array
    {
        return ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'failed_logins', 'locked_until'];
    }
}
