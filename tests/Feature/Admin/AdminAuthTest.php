<?php

namespace Tests\Feature\Admin;

use App\Domain\Auth\AlertOwnerOfNewDevice;
use App\Filament\Auth\Login;
use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Database\Factories\UserFactory;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** Admin sign-in and account security (SRS 3.6, 5.3). */
class AdminAuthTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
        Notification::fake();
    }

    private function login(string $email, string $password = 'password')
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => $password])
            ->call('authenticate');
    }

    #[Test]
    public function req_usr_01_an_account_that_is_not_the_owner_cannot_sign_in(): void
    {
        $this->login($this->formerStaff()->email)->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    #[Test]
    public function req_usr_01_req_usr_03_the_owner_logs_in_with_password_then_a_one_time_code(): void
    {
        $owner = $this->owner();

        $page = $this->login($owner->email);
        $this->assertGuest();                      // the password alone is not enough

        $page->set('data.multiFactor.app.code', (new Google2FA)->getCurrentOtp(UserFactory::TEST_TWO_STEP_SECRET))
            ->call('authenticate')
            ->assertRedirect();

        $this->assertAuthenticatedAs($owner);
    }

    #[Test]
    public function req_usr_03_the_owner_must_set_up_two_step_verification_before_using_the_admin(): void
    {
        $this->signIn(User::factory()->owner()->withoutTwoStep()->create());

        $this->get('/admin/products')->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
    }

    #[Test]
    public function sql_injection_in_the_login_form_never_signs_anyone_in(): void
    {
        $owner = $this->owner();
        $attacks = [
            ["' OR '1'='1' -- ", "' OR '1'='1"],
            [$owner->email."' -- ", 'anything'],
            [$owner->email, "' OR '1'='1' -- "],
            [$owner->email, '" OR ""="'],
        ];

        foreach ($attacks as [$email, $password]) {
            RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
            $this->login($email, $password)->assertHasErrors();
            $this->assertGuest();
        }

        $this->assertSame(1, User::query()->whereKey($owner->id)->count());
    }

    #[Test]
    public function req_sec_01_login_allows_at_most_5_attempts_a_minute_from_one_ip(): void
    {
        $owner = $this->owner();

        for ($i = 0; $i < 5; $i++) {
            $this->login($owner->email, 'wrong-password');
        }

        $this->login($owner->email)->assertNotified();
        $this->assertGuest();
    }

    #[Test]
    public function req_sec_02_five_wrong_passwords_lock_the_account_for_15_minutes(): void
    {
        // Before two-step sign-in is set up, so the right password alone signs her in at the end.
        $owner = User::factory()->owner()->withoutTwoStep()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->login($owner->email, 'wrong-password');
        }

        $owner->refresh();
        $this->assertTrue($owner->isLocked());
        $this->assertEqualsWithDelta(15, now()->diffInMinutes($owner->locked_until), 0.1);

        RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
        $this->login($owner->email)->assertHasFormErrors(['email']);
        $this->assertGuest();

        $this->travel(16)->minutes();
        $this->login($owner->email)->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($owner);
        $this->assertSame(0, $owner->refresh()->failed_logins);
    }

    #[Test]
    public function req_sec_03_passwords_are_stored_hashed(): void
    {
        $user = User::factory()->create(['password' => 'a-long-secret-123']);

        $stored = $user->getRawOriginal('password');
        $this->assertNotSame('a-long-secret-123', $stored);
        $this->assertTrue(Hash::isHashed($stored));
        $this->assertTrue(Hash::check('a-long-secret-123', $stored));
    }

    #[Test]
    public function req_sec_22_an_admin_session_ends_after_2_idle_hours(): void
    {
        $this->assertSame(120, config('session.lifetime'));
        $this->assertFalse(config('session.expire_on_close'));

        // No "remember me": nothing keeps a session alive past the idle limit.
        Livewire::test(Login::class)->assertFormFieldDoesNotExist('remember');
    }

    #[Test]
    public function req_sec_23_the_owner_gets_an_email_when_her_account_logs_in_from_a_new_device(): void
    {
        $owner = $this->owner();
        $deviceToken = str_repeat('a', 40);

        $this->loginFrom($owner, null);
        Notification::assertSentTo($owner, NewDeviceLogin::class);
        $this->assertSame(1, $owner->knownDevices()->count());

        Notification::fake();
        $device = $owner->knownDevices()->first();
        $device->update(['token_hash' => hash('sha256', $deviceToken)]);
        $this->loginFrom($owner, $deviceToken);
        Notification::assertNothingSent();                     // same browser: no email

        $this->loginFrom($owner, str_repeat('b', 40));
        Notification::assertSentTo($owner, NewDeviceLogin::class);
    }

    #[Test]
    public function req_sec_23_a_sign_in_by_an_account_that_is_not_the_owner_sends_no_email(): void
    {
        $this->loginFrom($this->formerStaff(), null);

        Notification::assertNothingSent();
    }

    private function loginFrom(User $user, ?string $deviceCookie): void
    {
        $request = Request::create('/admin/login', 'POST', cookies: $deviceCookie ? [AlertOwnerOfNewDevice::COOKIE => $deviceCookie] : []);
        $this->app->instance('request', $request);

        event(new LoginEvent('web', $user, false));
    }

    #[Test]
    public function req_sec_38_admin_pages_refuse_visitors_who_are_not_logged_in(): void
    {
        foreach (['/admin', '/admin/products', '/admin/orders', '/admin/hero-rack', '/admin/profile'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    #[Test]
    public function req_usr_09_the_owner_can_reset_a_forgotten_password_by_email(): void
    {
        $owner = $this->owner();

        Livewire::test(RequestPasswordReset::class)->fillForm(['email' => $owner->email])->call('request');

        Notification::assertSentTo($owner, ResetPassword::class);
    }
}
