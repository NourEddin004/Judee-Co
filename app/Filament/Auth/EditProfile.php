<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Notifications\SignInDetailsChanged;
use Closure;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use SensitiveParameter;

/**
 * "My account": the owner's name, sign-in email and password.
 *
 * - A new email or password is saved only with the current password.
 * - 5 wrong current passwords in 15 minutes sign the owner out and lock the account for
 *   15 minutes, like the login page, so a stolen open session cannot guess its way in.
 * - A new password must be strong (Password::defaults in AppServiceProvider).
 * - After a new password, every other signed-in browser is signed out.
 * - The old email address is told about every change, so a change she did not make is noticed.
 */
class EditProfile extends BaseEditProfile
{
    public const MAX_WRONG_CURRENT_PASSWORDS = 5;

    public const WRONG_CURRENT_PASSWORD_MINUTES = 15;

    /** The email before this save, to tell that address about a change. */
    private ?string $previousEmail = null;

    public function getTitle(): string|Htmlable
    {
        return __('My account');
    }

    public static function getLabel(): string
    {
        return __('My account');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->inlineLabel(false)->components([
            Section::make(__('Your details'))->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent()->label(__('Email address (you sign in with it)')),
            ]),
            Section::make(__('Change password'))
                ->description(__('Leave empty to keep your current password.'))
                ->schema([
                    $this->getPasswordFormComponent()->label(__('New password'))
                        ->maxLength(64)
                        ->helperText(__('At least 12 characters, with a capital letter, a small letter, a number and a symbol.')),
                    $this->getPasswordConfirmationFormComponent()->label(__('Type the new password again'))
                        ->visible(true)
                        ->required(fn (Get $get): bool => filled($get('password'))),
                ]),
            Section::make(__('Confirm it is you'))
                ->description(__('To save a new email or password, type your current password.'))
                ->schema([$this->getCurrentPasswordFormComponent()]),
        ]);
    }

    protected function getNameFormComponent(): Component
    {
        return parent::getNameFormComponent()->autofocus(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label(__('Current password'))
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required(fn (Get $get): bool => $this->changesSignInDetails($get))
            ->rule(fn (): Closure => $this->checkCurrentPassword(...))
            ->dehydrated(false);
    }

    private function changesSignInDetails(Get $get): bool
    {
        return filled($get('password')) || $get('email') !== $this->getUser()->getAttributeValue('email');
    }

    private function checkCurrentPassword(string $attribute, #[SensitiveParameter] mixed $value, Closure $fail): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $key = 'admin:profile:current-password:'.$user->getKey();

        if (Hash::check((string) $value, $user->password)) {
            RateLimiter::clear($key);

            return;
        }

        RateLimiter::hit($key, self::WRONG_CURRENT_PASSWORD_MINUTES * 60);

        if (RateLimiter::tooManyAttempts($key, self::MAX_WRONG_CURRENT_PASSWORDS)) {
            RateLimiter::clear($key);
            $user->forceFill(['locked_until' => now()->addMinutes(User::LOCK_MINUTES)])->saveQuietly();

            Filament::auth()->logout();

            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            $this->redirect(Filament::getLoginUrl());
        }

        $fail(__('The current password is not right.'));
    }

    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        $this->previousEmail = $this->getUser()->getAttributeValue('email');

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $changedEmail = $user->wasChanged('email');
        $changedPassword = $user->wasChanged('password');

        if (! $changedEmail && ! $changedPassword) {
            return;
        }

        if ($changedPassword) {
            $this->signOutOtherBrowsers($user);
        }

        // Told at the old address too, so the real owner notices a change she did not make.
        $addresses = array_unique(array_filter([$this->previousEmail, $user->email]));
        Notification::route('mail', $addresses)
            ->notify(new SignInDetailsChanged($user->name, $changedEmail ? $user->email : null, $changedPassword));

        $this->data['currentPassword'] = null;
    }

    /** Every other session of this account ends; this browser gets a fresh session id. */
    private function signOutOtherBrowsers(User $user): void
    {
        if (! request()->hasSession()) {
            return;
        }

        $session = request()->session();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $session->getId())
                ->delete();
        }

        $session->migrate(true);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('Saved');
    }
}
