<?php

namespace Tests\Feature\Checkout;

use App\Domain\Orders\OrderReceipt;
use App\Models\Order;
use App\Models\Setting;
use App\Support\StoreCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The PDF receipt behind "Download receipt" on the confirmation page (REQ-CHK-23 to 25, REQ-SEC-43, D.6.12). */
class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $language = 'en', string $number = 'K7P2QX'): Order
    {
        $arabic = $language === 'ar';
        $order = Order::factory()->create([
            'number' => $number,
            'language' => $language,
            'customer_name' => $arabic ? 'لينا الحسن' : 'Lina Haddad',
            'phone' => '+962 791234567',
            'address' => $arabic ? 'شارع وصفي التل، عمارة 12' : 'Wasfi Al-Tal Street, building 12',
            'area' => $arabic ? 'خلدا' : 'Khalda',
            'city_name' => $arabic ? 'عمّان' : 'Amman',
            'subtotal' => '87.00',
            'delivery_fee' => '2.00',
            'total' => '89.00',
            'placed_at' => Carbon::parse('2026-10-25 14:30', 'Asia/Amman'),
        ]);
        $order->items()->createMany([
            ['product_name' => $arabic ? 'طقم بربطة جانبية كحلي' : 'Navy Wrap Set', 'color_name' => $arabic ? 'كحلي' : 'Navy', 'size' => 'M', 'unit_price' => '35.00', 'quantity' => 2],
            ['product_name' => $arabic ? 'حجاب روزماري' : 'Rosemary Hijab', 'color_name' => $arabic ? 'روزماري' : 'Rosemary', 'size' => null, 'unit_price' => '17.00', 'quantity' => 1],
        ]);

        return $order->load('items');
    }

    #[Test]
    public function req_chk_23_the_confirmation_page_offers_download_receipt_above_continue_shopping(): void
    {
        $order = $this->order();

        $this->get('/order/'.$order->link_token)->assertOk()
            ->assertSee('href="'.url('/order/'.$order->link_token.'/receipt').'" class="ds-btn ds-btn--primary ds-receipt-callout__btn" download data-receipt>', false)
            ->assertSeeInOrder(['Save your receipt to track your order', 'Download receipt', 'ds-icon', 'Continue shopping'], false);
        $this->inArabic()->get('/order/'.$order->link_token)->assertOk()
            ->assertSee('href="'.url('/order/'.$order->link_token.'/receipt').'"', false)
            ->assertSee('تنزيل الإيصال');
    }

    #[Test]
    public function req_dmo_102_req_sec_43_the_receipt_downloads_as_a_pdf_named_after_the_order_and_is_never_cached(): void
    {
        $order = $this->order();

        $response = $this->get('/order/'.$order->link_token.'/receipt')->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename=JUDE-order-K7P2QX.pdf')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Title (', $response->getContent());
    }

    #[Test]
    public function req_sec_43_only_the_orders_random_link_token_opens_the_receipt(): void
    {
        $order = $this->order();

        $this->get('/order/'.strrev($order->link_token).'/receipt')->assertNotFound();
        $this->get('/order/'.substr($order->link_token, 0, 10).'/receipt')->assertNotFound();
        $this->get('/order/'.$order->number.'/receipt')->assertNotFound();
        $this->get('/order/'.$order->link_token.'/receipt')->assertOk();
    }

    #[Test]
    public function req_sec_34_making_receipts_is_rate_limited_like_the_other_public_endpoints(): void
    {
        $route = app('router')->getRoutes()->getByName('receipt');

        $this->assertContains('throttle:storefront', $route->gatherMiddleware());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
    }

    #[Test]
    public function req_chk_24_the_receipt_shows_the_order_its_items_and_the_amounts(): void
    {
        $html = app(OrderReceipt::class)->html($this->order());

        foreach ([
            'Order receipt', 'Order #K7P2QX', '25 October 2026, 14:30', 'Cash on delivery',
            'Lina Haddad', 'Wasfi Al-Tal Street, building 12, Khalda, Amman', '+962 791234567',
            'Navy Wrap Set', '35 JD', '70 JD', 'Rosemary Hijab', '17 JD',
            'Subtotal', '87 JD', 'Delivery', '2 JD', 'Total', '89 JD',
            'You pay <strong>89 JD</strong> in cash when your order arrives.',
            'Questions about your order? Call or WhatsApp +962 79 044 2964.', 'Instagram @judee.co', 'Thank you for shopping with JUDE.',
        ] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertMatchesRegularExpression('#<td class="middle">M</td>\s*<td class="middle">2</td>#', $html);
        $this->assertMatchesRegularExpression('#<td class="middle">—</td>\s*<td class="middle">1</td>#', $html, 'a piece without a size');
    }

    #[Test]
    public function req_chk_25_the_receipt_is_in_the_orders_language_right_to_left_for_arabic(): void
    {
        $order = $this->order('ar');
        $html = app(OrderReceipt::class)->html($order);

        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $html);
        foreach (['إيصال الطلب', 'التاريخ', '25 تشرين الأول 2026، 14:30', 'الدفع عند الاستلام', 'لينا الحسن', 'طقم بربطة جانبية كحلي', 'المجموع الفرعي'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        // Numbers, amounts and the phone read left to right inside the Arabic text.
        $this->assertStringContainsString("\u{2066}+962 791234567\u{2069}", $html);
        $this->assertStringContainsString("\u{2066}89 JD\u{2069}", $html);

        // The language of the order, not of the address it was opened from; set in IBM Plex Sans Arabic.
        $pdf = $this->get('/order/'.$order->link_token.'/receipt')->assertOk()->getContent();
        $this->assertStringContainsString('IBMPlexSansArabic', $pdf);
        $this->assertStringNotContainsString('Montserrat', $pdf);

        $english = $this->inArabic()->get('/order/'.$this->order('en', 'M4R8TW')->link_token.'/receipt')->assertOk()->getContent();
        $this->assertStringContainsString('Montserrat', $english);
        $this->assertStringContainsString('Merriweather', $english);
    }

    #[Test]
    public function d_6_12_the_receipt_shows_the_logo_or_else_the_store_name(): void
    {
        $order = $this->order();
        $this->assertStringContainsString('<img src="var:logo"', app(OrderReceipt::class)->html($order));

        Setting::query()->where('key', 'logo')->update(['value' => null]);
        app(StoreCache::class)->forget('settings');
        app()->forgetScopedInstances();

        $html = app(OrderReceipt::class)->html($order);
        $this->assertStringNotContainsString('var:logo', $html);
        $this->assertStringContainsString('<span class="wordmark">JUDE</span>', $html);
        $this->assertStringStartsWith('%PDF-', app(OrderReceipt::class)->pdf($order));
    }
}
