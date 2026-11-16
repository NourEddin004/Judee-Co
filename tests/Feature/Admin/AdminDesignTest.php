<?php

namespace Tests\Feature\Admin;

use App\Support\DesignTokens;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** The admin panel wears the storefront's colours (REQ-UX-01, REQ-UX-02, SRS 4.1). */
class AdminDesignTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    #[Test]
    public function req_ux_02_the_admin_uses_the_storefront_colour_tokens(): void
    {
        $primary = Filament::getPanel('admin')->getColors()['primary'];
        $this->assertSame(Color::convertToOklch(DesignTokens::color('rose')), $primary[600]);
        $this->assertSame(Color::convertToOklch(DesignTokens::color('rose-strong')), $primary[700]);
        $this->assertSame('#a24f68', DesignTokens::color('rose'));
    }

    #[Test]
    public function req_ux_01_the_admin_stylesheet_takes_every_colour_and_size_from_tokens(): void
    {
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/admin.css')));

        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b|rgba?\(|hsla?\(|oklch\(/', $css);
        $this->assertDoesNotMatchRegularExpression('/:\s*[^;{}]*\b\d+(\.\d+)?(px|rem|em|ms|s)\b/', preg_replace('/@media[^{]+/', '', $css));
    }

    #[Test]
    public function the_phone_bottom_menu_is_on_admin_pages(): void
    {
        $this->asOwner();

        $html = $this->get('/admin/products')->assertOk()->getContent();
        $menu = substr($html, strpos($html, 'class="jude-bottom-nav"'));

        foreach (['Home', '/admin/hero-rack', 'Products', 'Orders', 'More'] as $item) {
            $this->assertStringContainsString($item, $menu);
        }
    }
}
