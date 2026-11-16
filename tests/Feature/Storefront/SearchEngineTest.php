<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleXMLElement;
use Tests\TestCase;

/**
 * Search-engine setup (SRS 6.1, M3 task K; it has no requirement ID of its own): the sitemap,
 * robots.txt, and the address on every public page. Both languages share one address per page.
 */
class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'shop' => ['/shop'],
            'sets' => ['/sets'],
            'hijabs' => ['/hijabs'],
            'bundles' => ['/bundles'],
            'product' => ['/product/wrap-set'],
            'contact' => ['/contact'],
            'terms' => ['/terms'],
        ];
    }

    public static function privatePages(): array
    {
        return [
            'bag' => ['/cart'], 'checkout' => ['/checkout'],
            'arabic bag' => ['ar:/cart'], 'not found' => ['/no-such-page'],
        ];
    }

    #[Test]
    public function the_sitemap_lists_every_public_page_once(): void
    {
        $category = Category::query()->create([
            'slug' => 'abayas', 'name_en' => 'Abayas', 'name_ar' => 'عبايات', 'page_title_en' => 'Abayas', 'page_title_ar' => 'عبايات',
            'empty_title_en' => 'Nothing here yet', 'empty_title_ar' => 'لا شيء هنا بعد', 'empty_body_en' => '', 'empty_body_ar' => '', 'position' => 9,
        ]);
        $hidden = Product::query()->where('slug', 'hijab-box')->firstOrFail();
        $hidden->update(['is_published' => false]);

        $urls = $this->sitemap();

        foreach (self::publicPages() as [$page]) {
            $this->assertArrayHasKey(url($page), $urls);
        }
        $this->assertArrayHasKey(url('/'.$category->slug), $urls, 'a category the owner adds');
        $this->assertEmpty(array_filter(array_keys($urls), fn ($url) => str_contains($url, '/ar')), 'no Arabic addresses any more');
        $this->assertCount(Product::query()->count(), array_filter(array_keys($urls), fn ($url) => str_contains($url, '/product/')));
        $this->assertArrayNotHasKey(url('/product/hijab-box'), $urls, 'a hidden product');
        foreach (['/cart', '/checkout', '/order', '/admin'] as $private) {
            $this->assertEmpty(array_filter(array_keys($urls), fn ($url) => str_contains($url, $private)), $private);
        }
    }

    #[Test]
    public function each_sitemap_address_is_one_page_with_its_date(): void
    {
        $urls = $this->sitemap();

        $product = Product::query()->where('slug', 'wrap-set')->firstOrFail();
        $this->assertSame($product->updated_at->toAtomString(), $urls[url('/product/wrap-set')]['lastmod']);
        // One address per product: its colours share it (REQ-DMO-98).
        $this->assertEmpty(array_filter(array_keys($urls), fn ($url) => str_contains($url, '?color=')));
    }

    #[Test]
    public function a_change_to_one_color_moves_the_products_sitemap_date(): void
    {
        $this->travelTo(now()->addDay()->startOfSecond());

        Product::query()->where('slug', 'wrap-set')->firstOrFail()->colors()->firstOrFail()->update(['in_stock' => false]);

        $this->assertSame(now()->toAtomString(), $this->sitemap()[url('/product/wrap-set')]['lastmod']);
    }

    #[Test]
    public function robots_txt_keeps_search_engines_out_of_private_pages_and_points_at_the_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $lines = explode("\n", trim($response->getContent()));

        $this->assertSame('User-agent: *', $lines[0]);
        foreach (['/admin', '/cart', '/checkout', '/order/', '/bag/'] as $path) {
            $this->assertContains('Disallow: '.$path, $lines);
        }
        $this->assertNotContains('Disallow: /', $lines, 'the store itself stays open to search engines');
        $this->assertContains('Sitemap: '.url('/sitemap.xml'), $lines);
        // A file in public/ would be sent by the web server instead of this list.
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    #[Test]
    #[DataProvider('publicPages')]
    public function public_pages_name_their_one_address_in_either_language(string $page): void
    {
        foreach ([$page, 'ar:'.$page] as $visit) {
            $this->get($visit)->assertOk()
                ->assertSee('<link rel="canonical" href="'.url($page).'">', false)
                ->assertDontSee('hreflang', false)
                ->assertDontSee('noindex', false);
        }
    }

    #[Test]
    public function searched_sorted_and_filtered_lists_point_at_the_plain_list_and_keep_the_page_number(): void
    {
        $this->get('/sets?q=wrap&sort=price_low')->assertSee('<link rel="canonical" href="'.url('/sets').'">', false);
        $this->inArabic()->get('/shop?category=sets&color=black')->assertSee('<link rel="canonical" href="'.url('/shop').'">', false);
        $this->get('/shop?page=2')
            ->assertSee('<link rel="canonical" href="'.e(url('/shop?page=2')).'">', false);
    }

    #[Test]
    #[DataProvider('privatePages')]
    public function private_pages_stay_out_of_search_results(string $page): void
    {
        $this->get($page)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<meta name="referrer" content="same-origin">', false)
            ->assertDontSee('rel="canonical"', false);
    }

    #[Test]
    public function the_order_confirmation_page_stays_out_of_search_results_and_never_passes_on_its_link(): void
    {
        $order = Order::factory()->withItems()->create();

        $this->get('/order/'.$order->link_token)->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<meta name="referrer" content="same-origin">', false)
            ->assertDontSee('rel="canonical"', false);
    }

    /** @return array<string, array{lastmod: ?string}> */
    private function sitemap(): array
    {
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = new SimpleXMLElement($response->getContent());
        $urls = [];

        foreach ($xml->url as $entry) {
            $this->assertArrayNotHasKey((string) $entry->loc, $urls, 'each address once');
            $urls[(string) $entry->loc] = ['lastmod' => isset($entry->lastmod) ? (string) $entry->lastmod : null];
        }

        return $urls;
    }
}
