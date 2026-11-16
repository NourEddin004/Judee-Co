<?php

namespace Tests\Feature\Storefront;

use App\Http\Middleware\SetLocale;
use App\Models\RackItem;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'home' => ['/', 'JUDE Boutique | Elegant. Comfy. Modest.'],
            'shop' => ['/shop', 'Shop All | JUDE Boutique'],
            'sets' => ['/sets', 'Clothing Sets | JUDE Boutique'],
            'hijabs' => ['/hijabs', 'Hijabs | JUDE Boutique'],
            'bundles' => ['/bundles', 'Bundles | JUDE Boutique'],
            'product' => ['/product/wrap-set', 'Wrap Set | JUDE Boutique'],
            'cart' => ['/cart', 'Shopping Bag | JUDE Boutique'],
            'checkout' => ['/checkout', 'Checkout | JUDE Boutique'],
            'contact' => ['/contact', 'Contact + FAQs | JUDE Boutique'],
            'terms' => ['/terms', 'Terms | JUDE Boutique'],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function req_str_18_and_d_6_every_page_opens_in_english_with_its_tab_title(string $url, string $title): void
    {
        $this->get($url)->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee('<title>'.e($title).'</title>', false)
            ->assertSee('Skip to content')
            ->assertSee('data-toast', false)
            ->assertSee('data-back-to-top', false);
    }

    #[Test]
    #[DataProvider('pages')]
    public function req_str_19_21_and_req_ux_51_every_page_opens_in_arabic_right_to_left(string $url, string $title): void
    {
        $this->inArabic()->get($url)->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
    }

    #[Test]
    #[DataProvider('pages')]
    public function req_str_20_and_req_dmo_84_the_language_button_switches_the_language_on_the_same_address(string $url, string $title): void
    {
        $this->get($url)->assertSee('href="'.url($url).'?lang=ar" class="ds-text-btn ds-header__desktop-only" lang="ar"', false);
        $this->inArabic()->get($url)->assertSee('href="'.url($url).'?lang=en"', false)->assertSee('English');

        // Pressing it saves the choice and comes straight back to the same address.
        $this->get($url.'?lang=ar')->assertStatus(302)->assertRedirect(url($url))->assertPlainCookie(SetLocale::COOKIE, 'ar');
        $this->get($url.'?lang=en')->assertRedirect(url($url))->assertPlainCookie(SetLocale::COOKIE, 'en');
    }

    #[Test]
    public function req_str_20_the_saved_language_stays_until_the_customer_switches_again(): void
    {
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'ar')->get('/contact')->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertHeader('Vary');
        $this->assertContains('Cookie', $this->get('/contact')->baseResponse->getVary());
        // Anything else in the cookie, or no cookie, is English.
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'fr')->get('/contact')->assertSee('<html lang="en" dir="ltr"', false);
        // An unknown ?lang= changes nothing and still drops from the address.
        $this->get('/shop?lang=fr')->assertRedirect(url('/shop'))->assertCookieMissing(SetLocale::COOKIE);
    }

    #[Test]
    public function req_dmo_84_the_switch_keeps_the_query_string(): void
    {
        $this->get('/sets?q=wrap&page=2')->assertSee('href="'.e(url('/sets?q=wrap&page=2&lang=ar')).'"', false);
        $this->get('/sets?q=wrap&lang=ar&page=2')->assertRedirect(url('/sets?q=wrap&page=2'));
    }

    #[Test]
    public function req_str_18_old_arabic_addresses_open_the_same_page_in_arabic(): void
    {
        $this->get('/ar')->assertStatus(302)->assertRedirect(url('/'))->assertPlainCookie(SetLocale::COOKIE, 'ar');
        $this->get('/ar/')->assertRedirect(url('/'));
        $this->get('/ar/product/wrap-set?color=navy')->assertRedirect(url('/product/wrap-set?color=navy'))
            ->assertPlainCookie(SetLocale::COOKIE, 'ar');
        $this->get('/ar/hijabs?shade=brown&page=2')->assertRedirect(url('/hijabs?shade=brown&page=2'));
        // An old address that leads nowhere still ends on the Not Found page.
        $this->get('/ar/nothing-here')->assertRedirect(url('/nothing-here'));
    }

    #[Test]
    #[DataProvider('pages')]
    public function req_db_23_no_storefront_page_runs_more_than_15_queries(string $url, string $title): void
    {
        $this->get($url); // first visit fills the memory cache
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $this->assertLessThanOrEqual(15, count(DB::getQueryLog()), $url);
    }

    #[Test]
    public function d_6_10_unknown_addresses_show_the_not_found_page_in_the_right_language(): void
    {
        $this->get('/nothing-here')->assertNotFound()->assertSee('This page is not here')->assertSee('Page not found | JUDE Boutique', false);
        $this->inArabic()->get('/nothing-here')->assertNotFound()->assertSee('<html lang="ar" dir="rtl"', false);
        $this->get('/nothing-here')->assertSee('href="'.url('/nothing-here').'?lang=ar"', false);
        $this->get('/nothing-here?lang=ar')->assertRedirect(url('/nothing-here'))->assertPlainCookie(SetLocale::COOKIE, 'ar');
    }

    #[Test]
    public function d_6_11_demo_addresses_redirect_permanently(): void
    {
        $this->get('/index.html')->assertRedirect('/')->assertStatus(301);
        $this->get('/hijabs.html?shade=brown')->assertStatus(301)->assertRedirect('/hijabs?shade=brown');
        $this->get('/product.html?slug=Black-Wrap-Set')->assertStatus(301)->assertRedirect('/product/black-wrap-set');
        // ...which is now the black colour of the wrap set (REQ-DMO-103).
        $this->get('/product/black-wrap-set')->assertStatus(301)->assertRedirect(url('/product/wrap-set?color=black'));
        $this->get('/search?q=olive')->assertStatus(301)->assertRedirect('/shop?q=olive');
        $this->get('/confirmation.html')->assertStatus(301)->assertRedirect('/shop');
    }

    #[Test]
    public function req_dmo_79_and_80_the_rack_shows_up_to_6_garments_in_order_linking_to_products(): void
    {
        $first = RackItem::query()->orderBy('position')->first()->load('product.colors.color');
        RackItem::query()->update(['position' => 9]);
        $first->update(['position' => 0]);
        foreach (range(1, 3) as $i) {
            RackItem::create(['name_en' => "Extra {$i}", 'image_path' => $first->image_path, 'position' => 10 + $i, 'is_visible' => true]);
        }

        $html = $this->get('/')->getContent();

        $this->assertSame(6, substr_count($html, 'class="ds-rack__item"'));
        // The garment opens its product on the garment's colour.
        $this->assertStringContainsString('href="'.e(url('/product/wrap-set?color=beige')).'"', $html);
        $this->assertSame(url('/product/wrap-set?color=beige'), $first->url());
        $this->assertLessThan(strpos($html, 'Extra 1'), strpos($html, e($first->name_en)));
    }

    #[Test]
    public function req_dmo_81_reviews_show_only_when_one_is_published(): void
    {
        $this->get('/')->assertDontSee('ds-testimonials', false);

        Review::create(['author' => 'Lina', 'country_code' => 'JO', 'body_en' => 'Lovely set', 'is_published' => true]);

        $this->get('/')->assertSee('ds-testimonials', false)->assertSee('Lina');
    }

    #[Test]
    public function req_dmo_82_and_req_ux_37_photos_below_the_first_screen_load_lazily_and_req_prf_02_the_first_row_at_once(): void
    {
        $html = $this->get('/shop')->getContent();

        preg_match_all('/<img class="ds-card__image ds-card__image--primary"[^>]*>/', $html, $photos);
        $this->assertGreaterThan(4, count($photos[0]));
        // The first row on a phone (2 cards) loads at once, with priority.
        foreach ($photos[0] as $i => $img) {
            $i < 2 ? $this->assertStringNotContainsString('loading="lazy"', $img) : $this->assertStringContainsString('loading="lazy"', $img);
            $i < 2 ? $this->assertStringContainsString('fetchpriority="high"', $img) : $this->assertStringNotContainsString('fetchpriority', $img);
        }

        // The second photo of each card shows only on hover.
        preg_match_all('/<img class="ds-card__image ds-card__image--secondary"[^>]*>/', $html, $hovers);
        $this->assertNotEmpty($hovers[0]);
        foreach ($hovers[0] as $img) {
            $this->assertStringContainsString('loading="lazy"', $img);
        }

        // Related products sit below the product itself.
        preg_match_all('/<img class="ds-card__image[^>]*>/', $this->get('/product/rosemary-hijab')->getContent(), $related);
        $this->assertNotEmpty($related[0]);
        foreach ($related[0] as $img) {
            $this->assertStringContainsString('loading="lazy"', $img);
        }
    }

    #[Test]
    public function d_6_8_contact_page_has_whatsapp_as_its_only_primary_button(): void
    {
        $html = $this->get('/contact')->assertSee('Where does JUDE ship?')->assertSee('https://wa.me/962790442964', false)->getContent();
        $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));

        $this->assertSame(1, substr_count($main, 'ds-btn--primary'));
    }

    #[Test]
    public function d_6_9_terms_page_shows_the_sections(): void
    {
        $this->get('/terms')->assertSee('Terms and Conditions')->assertSee('Orders and payment')->assertSee('id="delivery"', false);
    }

    #[Test]
    public function d_6_6_checkout_lead_reads_the_fees_from_the_delivery_list(): void
    {
        $this->get('/checkout')->assertSee('Delivery is 3 JD across Jordan and 25 JD to the Gulf countries.');
        $this->get('/cart')->assertSee('3 JD across Jordan · 25 JD to Gulf countries');
    }

    #[Test]
    public function req_dmo_58_d_8_3_the_phone_list_offers_28_countries_with_jordan_first(): void
    {
        $html = $this->get('/checkout')->getContent();
        preg_match_all('/<option value="([A-Z]{2})"[^>]*data-dial/', $html, $codes);

        $this->assertSame(['JO', 'PS', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'IQ', 'LB', 'SY', 'EG', 'TR', 'GB', 'US', 'DE', 'YE', 'LY', 'TN', 'DZ', 'MA', 'SD', 'FR', 'IT', 'ES', 'NL', 'SE', 'AU'], $codes[1]);
    }

    #[Test]
    public function req_chk_22_req_dmo_70_each_phone_country_carries_its_digits_and_its_message(): void
    {
        $this->get('/checkout')
            ->assertSee('data-dial="962" data-digits="9" data-placeholder="7 9012 3456" data-invalid="Please enter 9 digits after +962."', false)
            ->assertSee('data-dial="961" data-digits="7 8" data-placeholder="71 123 456" data-invalid="Please enter 7 or 8 digits after +961."', false)
            ->assertSee('data-dial="49" data-digits="10 11" data-placeholder="1512 3456789" data-invalid="Please enter 10 or 11 digits after +49."', false);
        $this->inArabic()->get('/checkout')
            ->assertSee('data-digits="9" data-placeholder="7 9012 3456" data-invalid="يرجى إدخال 9 أرقام بعد '."\u{2066}+962\u{2069}".'."', false);
    }

    #[Test]
    public function req_ux_45_46_47_checkout_fields_use_the_right_types_and_autocomplete(): void
    {
        $this->get('/checkout')
            ->assertSee('name="phone" type="tel"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('name="email" type="email"', false)
            ->assertSee('autocomplete="name"', false)
            ->assertSee('autocomplete="tel-national"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('autocomplete="address-level2"', false)
            ->assertSee('autocomplete="street-address"', false);
    }

    #[Test]
    public function req_dmo_101_the_bag_drawer_offers_checkout_and_continue_shopping_which_closes_it(): void
    {
        // data-modal-close: ui.js closes the drawer and gives focus back to the button that opened it.
        $this->get('/shop')
            ->assertSeeInOrder(['data-bag-checkout', 'Proceed to checkout', 'data-modal-close>Continue shopping</button>'], false)
            ->assertDontSee('Go to cart');
        $this->inArabic()->get('/shop')->assertSee('data-modal-close>متابعة التسوق</button>', false);
    }

    #[Test]
    public function req_ux_52_directional_icons_are_marked_for_mirroring(): void
    {
        $this->inArabic()->get('/product/wrap-set')->assertSee('ds-icon ds-icon--dir', false);
    }

    #[Test]
    public function req_sec_16_text_is_escaped(): void
    {
        $this->get('/shop?q=%3Cscript%3Ealert(1)%3C/script%3E')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
