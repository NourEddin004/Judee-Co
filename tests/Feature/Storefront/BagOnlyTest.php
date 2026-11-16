<?php

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SRS 1.1 took the wishlist out, because customers have no accounts (Appendix D.9). The bag is
 * the only list the store keeps in the customer's browser (REQ-DMO-32).
 */
class BagOnlyTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'home' => ['/'], 'shop' => ['/shop'], 'product' => ['/product/wrap-set'],
            'bag' => ['/cart'], 'arabic shop' => ['ar:/shop'], 'arabic product' => ['ar:/product/rosemary-hijab'],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function d_9_no_page_has_a_heart_button_or_a_wishlist_link(string $page): void
    {
        $html = $this->get($page)->assertOk()->getContent();

        $this->assertStringNotContainsString('data-wishlist', $html);
        $this->assertStringNotContainsString('ds-heart-btn', $html);
        $this->assertStringNotContainsString('/wishlist', $html);
        $this->assertStringNotContainsStringIgnoringCase('wishlist', strip_tags($html));
        $this->assertStringNotContainsString('المفضلة', $html);
    }

    #[Test]
    public function d_9_and_d_6_11_the_wishlist_addresses_are_gone(): void
    {
        $this->get('/wishlist')->assertNotFound();
        $this->inArabic()->get('/wishlist')->assertNotFound();
        $this->get('/wishlist.html')->assertNotFound();
        $this->get('/bag/cards?ids=1')->assertNotFound();
    }

    #[Test]
    public function req_dmo_32_the_bag_is_the_only_list_kept_in_the_browser(): void
    {
        $this->assertSame(['bag' => 'jude.bag.v2', 'currency' => 'jude.currency'], config('store.storage_keys'));
        $this->assertStringNotContainsString('wishlist', file_get_contents(resource_path('js/store/core.js')));
    }

    #[Test]
    public function d_5_4_and_d_5_5_the_menu_and_footer_link_only_to_the_bag(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<div class="ds-menu__utilities">(.*?)<\/div>/s', $html, $utilities);
        $this->assertSame(1, substr_count($utilities[1], 'class="ds-menu__utility"'), 'one cell: Cart');
        $this->assertStringContainsString('href="'.url('/cart').'"', $utilities[1]);

        preg_match('/<h2 class="ds-footer__heading">Your bag<\/h2>\s*<ul class="ds-footer__list">(.*?)<\/ul>/s', $html, $bag);
        $this->assertSame(1, substr_count($bag[1], '<li>'), 'Your bag: Cart');
    }
}
