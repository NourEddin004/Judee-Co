<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\EditProfile;
use App\Models\User;
use App\Notifications\SignInDetailsChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** "My account": the owner changes her sign-in email and password safely (8 Nov 2026). */
class MyAccountTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    private const STRONG = 'Jude-Store-2026!';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->owner = $this->asOwner();
        $this->owner->forceFill(['password' => Hash::make('Old-Password-1!')])->save();
        RateLimiter::clear('admin:profile:current-password:'.$this->owner->id);
    }

    #[Test]
    public function the_page_is_in_the_menu_with_the_email_and_password_fields(): void
    {
        $this->get('/admin')->assertOk()->assertSee('My account');
        $this->get('/admin/profile')->assertOk()
            ->assertSee(['Your details', 'Email address', 'Change password', 'New password', 'Type the new password again', 'Current password']);
    }

    #[Test]
    public function a_new_password_needs_the_current_password(): void
    {
        Livewire::test(EditProfile::class)
            ->fillForm(['password' => self::STRONG, 'passwordConfirmation' => self::STRONG])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => self::STRONG, 'passwordConfirmation' => self::STRONG, 'currentPassword' => 'wrong'])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('Old-Password-1!', $this->owner->refresh()->password));
    }

    #[Test]
    public function a_weak_password_is_refused(): void
    {
        foreach (['short1!A', 'alllowercase-123!', 'ALLUPPERCASE-123!', 'NoNumbersHere!!', 'NoSymbols12345'] as $weak) {
            Livewire::test(EditProfile::class)
                ->fillForm(['password' => $weak, 'passwordConfirmation' => $weak, 'currentPassword' => 'Old-Password-1!'])
                ->call('save')
                ->assertHasFormErrors(['password']);
            $this->travel(13)->seconds();   // Filament allows 5 saves a minute
        }

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => self::STRONG, 'passwordConfirmation' => 'Something-Else-1!', 'currentPassword' => 'Old-Password-1!'])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }

    #[Test]
    public function a_strong_password_is_saved_hashed_and_both_addresses_are_told(): void
    {
        Livewire::test(EditProfile::class)
            ->fillForm(['password' => self::STRONG, 'passwordConfirmation' => self::STRONG, 'currentPassword' => 'Old-Password-1!'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->owner->refresh();
        $this->assertTrue(Hash::check(self::STRONG, $this->owner->password));
        $this->assertStringNotContainsString(self::STRONG, $this->owner->getRawOriginal('password'));
        Notification::assertSentOnDemand(SignInDetailsChanged::class, fn ($notification, $channels, $notifiable) => $notification->passwordChanged
            && in_array($this->owner->email, (array) $notifiable->routes['mail'], true));
    }

    #[Test]
    public function a_new_email_needs_the_current_password_and_the_old_address_is_told(): void
    {
        $old = $this->owner->email;

        Livewire::test(EditProfile::class)
            ->fillForm(['email' => 'jude.new@example.com'])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);
        $this->assertSame($old, $this->owner->refresh()->email);

        Livewire::test(EditProfile::class)
            ->fillForm(['email' => 'jude.new@example.com', 'currentPassword' => 'Old-Password-1!'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('jude.new@example.com', $this->owner->refresh()->email);
        Notification::assertSentOnDemand(SignInDetailsChanged::class, fn ($notification, $channels, $notifiable) => $notification->newEmail === 'jude.new@example.com'
            && in_array($old, (array) $notifiable->routes['mail'], true));
    }

    #[Test]
    public function the_name_alone_is_saved_without_the_current_password(): void
    {
        Livewire::test(EditProfile::class)->fillForm(['name' => 'Jude A.'])->call('save')->assertHasNoFormErrors();

        $this->assertSame('Jude A.', $this->owner->refresh()->name);
        Notification::assertNothingSent();
    }

    #[Test]
    public function five_wrong_current_passwords_sign_out_and_lock_the_account(): void
    {
        for ($try = 1; $try <= EditProfile::MAX_WRONG_CURRENT_PASSWORDS; $try++) {
            $page = Livewire::test(EditProfile::class)
                ->fillForm(['password' => self::STRONG, 'passwordConfirmation' => self::STRONG, 'currentPassword' => "guess-{$try}"])
                ->call('save');
            // Filament also allows only 5 saves a minute, so the clock moves on between guesses.
            $this->travel(61)->seconds();
        }

        $page->assertRedirect('/admin/login');
        $this->assertGuest();
        $this->assertTrue($this->owner->refresh()->isLocked());
        $this->assertTrue(Hash::check('Old-Password-1!', $this->owner->password));
    }

    #[Test]
    public function the_email_must_be_a_real_address_and_sql_text_is_just_text(): void
    {
        Livewire::test(EditProfile::class)
            ->fillForm(['name' => "Jude'; DROP TABLE users; --", 'email' => "x' OR '1'='1", 'currentPassword' => 'Old-Password-1!'])
            ->call('save')
            ->assertHasFormErrors(['email']);

        Livewire::test(EditProfile::class)->fillForm(['name' => "Jude'; DROP TABLE users; --"])->call('save')->assertHasNoFormErrors();

        $this->assertSame("Jude'; DROP TABLE users; --", $this->owner->refresh()->name);
        $this->assertSame(1, User::query()->whereKey($this->owner->id)->count());
    }
}
