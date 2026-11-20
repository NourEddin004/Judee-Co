<?php

namespace Tests\Feature\Storefront;

use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/** Branded error pages in both languages, for the store and the admin, with no error details shown. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);

        Route::middleware('web')->get('/_error/{code}', function (int $code) {
            if ($code === 500) {
                throw new RuntimeException('secret-detail-from-the-code');
            }
            abort($code);
        });
        Route::middleware('web')->get('/admin/_error/{code}', function (int $code) {
            if ($code === 500) {
                throw new RuntimeException('secret-detail-from-the-code');
            }
            abort($code);
        });
    }

    public static function codes(): array
    {
        return [
            '403' => [403, 'This page is private', 'هذه الصفحة خاصة'],
            '404' => [404, 'This page is not here', 'هذه الصفحة غير موجودة'],
            '419' => [419, 'This page timed out', 'انتهت مهلة هذه الصفحة'],
            '429' => [429, 'Too many tries', 'محاولات كثيرة'],
            '500' => [500, 'Something went wrong on our side', 'حدث خطأ من جهتنا'],
            '503' => [503, 'We will be right back', 'سنعود بعد قليل'],
            '401 (Laravel page)' => [401, 'Something did not go through', 'لم تكتمل العملية'],
        ];
    }

    #[Test]
    #[DataProvider('codes')]
    public function a_store_error_page_is_branded_in_english_and_arabic(int $code, string $english, string $arabic): void
    {
        $this->get("/_error/{$code}")->assertStatus($code)
            ->assertSee($english)->assertSee('RafaqTech')->assertSee('Error '.$code)
            ->assertDontSee('secret-detail-from-the-code')->assertDontSee('Stack trace')
            // Mistakes the visitor can fix keep the store's header and footer; server errors use the stand-alone page.
            ->assertSee($code < 500 ? 'ds-header' : 'jude-err--store', false);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'ar')->get("/_error/{$code}")->assertStatus($code)
            ->assertSee($arabic)->assertSee('dir="rtl"', false)
            ->assertDontSee('secret-detail-from-the-code');
    }

    #[Test]
    #[DataProvider('codes')]
    public function an_admin_error_page_uses_the_admin_look_and_language(int $code, string $english, string $arabic): void
    {
        $this->get("/admin/_error/{$code}")->assertStatus($code)
            ->assertSee('JUDE admin')->assertSee('RafaqTech')
            ->assertDontSee('secret-detail-from-the-code');

        $this->withCookie(AdminLocale::COOKIE, 'ar')->get("/admin/_error/{$code}")->assertStatus($code)
            ->assertSee('لوحة تحكم JUDE')->assertSee('dir="rtl"', false);
    }

    #[Test]
    public function an_unknown_admin_address_is_the_admin_not_found_page_in_the_admins_language(): void
    {
        $this->withCookie(AdminLocale::COOKIE, 'ar')->get('/admin/no-such-page-here')->assertNotFound()
            ->assertSee('لوحة تحكم JUDE')->assertSee('العودة إلى لوحة التحكم');
    }

    #[Test]
    public function the_live_store_turns_error_details_off_even_if_debug_was_left_on(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertFalse(config('app.debug'));
    }
}
