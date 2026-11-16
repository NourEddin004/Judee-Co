<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\AdminLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** The admin's English / Arabic button (9 Nov 2026). */
class AdminLanguageTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    #[Test]
    public function the_admin_is_in_english_by_default_with_an_arabic_button(): void
    {
        $this->asOwner();

        $this->get('/admin')->assertOk()
            ->assertSee('العربية')
            ->assertSee('dir="ltr"', false)
            ->assertSee('Products');
    }

    #[Test]
    public function the_button_saves_arabic_and_goes_back_to_the_same_page(): void
    {
        $this->asOwner();

        $this->from(url('/admin/orders'))->get('/admin/language/ar')
            ->assertRedirect(url('/admin/orders'))
            ->assertCookie(AdminLocale::COOKIE, 'ar');
    }

    #[Test]
    public function in_arabic_the_admin_reads_right_to_left_with_arabic_labels(): void
    {
        $this->asOwner();

        $this->withCookie(AdminLocale::COOKIE, 'ar')->get('/admin')->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(['English', 'المنتجات', 'الطلبات', 'حسابي']);
    }

    #[Test]
    public function the_sign_in_page_has_the_button_too(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('العربية');
        $this->withCookie(AdminLocale::COOKIE, 'ar')->get('/admin/login')->assertOk()->assertSee('English');
    }

    #[Test]
    public function only_english_and_arabic_are_accepted(): void
    {
        $this->asOwner();

        $this->get('/admin/language/fr')->assertNotFound();
        $this->from('https://evil.example/x')->get('/admin/language/ar')->assertRedirect(url('/admin'));
    }
}
