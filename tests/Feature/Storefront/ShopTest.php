<?php

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\ShopQuery;
use App\Domain\Catalog\ShopResult;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use DemoPieces, RefreshDatabase;

    private function slugsOn(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('result')->products->pluck('slug')->all();
    }

    /** The colour each card on the page shows, by product slug. */
    private function shownOn(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('result')->products
            ->mapWithKeys(fn (Product $product) => [$product->slug => $product->shownColor->color_id])->all();
    }

    /** The demo's hooded set without XXL, so the size filter has something to leave out. */
    private function hoodedSetWithoutXxl(): void
    {
        Product::query()->where('slug', 'hooded-set')->firstOrFail()->sizes()->detach(Size::query()->where('label', 'XXL')->value('id'));
    }

    #[Test]
    public function req_str_03_the_shop_shows_only_published_products(): void
    {
        Product::query()->where('slug', 'olive-noir-hijab')->update(['is_published' => false]);

        $this->get('/hijabs')->assertOk()->assertDontSee('Olive Noir Hijab');
        $this->get('/product/olive-noir-hijab')->assertNotFound();
    }

    #[Test]
    public function req_str_04_category_pages_show_only_their_category(): void
    {
        $hijabs = Category::query()->where('slug', 'hijabs')->value('id');
        $result = $this->get('/hijabs?page=1')->viewData('result');

        $this->assertTrue($result->products->every(fn ($product) => $product->category_id === $hijabs));
        $this->assertSame(12, $result->total);      // 11 printed hijabs and the plain hijab in 11 colours
    }

    #[Test]
    public function req_str_07_and_req_dmo_07_a_page_shows_at_most_6_products(): void
    {
        $result = $this->get('/hijabs')->viewData('result');

        $this->assertCount(ShopQuery::PER_PAGE, $result->products);
        $this->assertSame(6, ShopQuery::PER_PAGE);
        $this->assertSame(2, $result->pageCount);
    }

    #[Test]
    public function req_dmo_08_products_show_in_display_order_then_name_and_there_is_no_sort_menu(): void
    {
        $expected = Product::query()->whereHas('category', fn ($q) => $q->where('slug', 'hijabs'))
            ->orderBy('display_order')->orderBy('name_en')->limit(6)->pluck('slug')->all();

        $this->assertSame($expected, $this->slugsOn('/hijabs'));
        $this->get('/hijabs')->assertDontSee('name="sort"', false)->assertDontSee('ds-sort', false);
    }

    #[Test]
    public function d_6_2_a_sort_value_left_in_an_address_is_ignored(): void
    {
        foreach (['/hijabs?sort=name-desc', '/hijabs?sort=new', '/hijabs?sort=cheapest'] as $url) {
            $this->assertSame($this->slugsOn('/hijabs'), $this->slugsOn($url));
        }
    }

    #[Test]
    public function req_str_08_and_req_dmo_12_search_matches_name_ignoring_case(): void
    {
        $this->assertContains('olive-noir-hijab', $this->slugsOn('/shop?q=OLIVE'));
    }

    #[Test]
    public function req_dmo_12_search_matches_colour_and_category_names(): void
    {
        $this->assertNotEmpty($this->slugsOn('/shop?q=burgundy'));
        $this->assertSame(3, $this->get('/shop?q=sets')->viewData('result')->total);
        // "Violet Ice" is only one of the plain hijab's colours.
        $this->assertSame(['plain-hijab'], $this->slugsOn('/shop?q=violet'));
    }

    #[Test]
    public function req_dmo_13_search_matches_arabic_names_on_arabic_pages(): void
    {
        $arabic = Product::query()->where('slug', 'olive-noir-hijab')->value('name_ar');
        $word = explode(' ', $arabic)[1];

        $this->assertContains('olive-noir-hijab', $this->slugsOn('ar:/shop?q='.urlencode($word)));
    }

    #[Test]
    public function req_dmo_14_the_colour_filter_lists_only_colours_in_the_category(): void
    {
        $bundles = $this->get('/bundles')->viewData('result');
        $present = ProductColor::query()->whereHas('product.category', fn ($q) => $q->where('slug', 'bundles'))->with('color')->get()
            ->pluck('color.shade_id')->unique()->sort()->values()->all();

        $this->assertSame($present, $bundles->availableShades->pluck('id')->sort()->values()->all());
    }

    #[Test]
    public function req_str_05_and_req_dmo_15_selecting_colours_shows_any_selected_colour(): void
    {
        $anyOf = fn (array $shades) => Product::query()->whereHas('colors.color.shade', fn ($q) => $q->whereIn('key', $shades))->count();
        $both = $this->get('/shop?shade=black,brown')->viewData('result');

        $this->assertSame($anyOf(['black']), $this->get('/shop?shade=black')->viewData('result')->total);
        $this->assertSame($anyOf(['black', 'brown']), $both->total);
        $this->assertSame(['black', 'brown'], $both->selectedShades);
    }

    #[Test]
    public function req_str_24_and_req_dmo_96_each_product_is_one_card_with_a_dot_for_each_colour(): void
    {
        $page = $this->get('/sets')->assertOk();

        $this->assertSame(['wrap-set', 'belted-set', 'hooded-set'], $page->viewData('result')->products->pluck('slug')->all());
        $html = $page->getContent();
        $this->assertSame(3, substr_count($html, '<article class="ds-card'));
        // Wrap Set: 4 dots. Belted and Hooded Sets: 6 colours, so 5 dots and "+1" (D.5.6).
        $this->assertSame(4 + 5 + 5, substr_count($html, 'ds-card__dot'));
        $this->assertSame(2, substr_count($html, '<span class="ds-card__more">+1</span>'));
        $page->assertSee('aria-label="Available in 4 colors"', false)->assertSee('aria-label="Available in 6 colors"', false);
        // A product with one colour shows no dots.
        $this->get('/product/rosemary-hijab')->assertOk();
        $this->assertStringNotContainsString('ds-card__colors', $this->get('/shop?q=rosemary')->getContent());
    }

    #[Test]
    public function req_str_24_arabic_cards_say_how_many_colours_in_arabic(): void
    {
        $this->inArabic()->get('/sets')->assertOk()
            ->assertSee('aria-label="متوفر بـ 4 ألوان"', false)
            ->assertSee('aria-label="متوفر بـ 6 ألوان"', false);
        $this->inArabic()->get('/hijabs?page=2')->assertSee('aria-label="متوفر بـ 11 لونًا"', false);
    }

    #[Test]
    public function req_dmo_97_a_card_shows_its_first_colour_or_the_first_one_the_filter_or_search_matches(): void
    {
        $black = $this->piece('black-wrap-set');
        $navy = $this->piece('navy-wrap-set');

        $this->assertSame($black->color_id, $this->shownOn('/sets')['wrap-set']);
        $this->assertSame($navy->color_id, $this->shownOn('/sets?shade=blue')['wrap-set']);
        $this->assertSame($navy->color_id, $this->shownOn('/sets?q=navy')['wrap-set']);

        // The card opens the product on the colour it shows, with that colour's photo.
        $this->get('/sets?shade=blue')
            ->assertSee('href="'.e(url('/product/wrap-set?color=navy')).'"', false)
            ->assertSee($navy->photos()->where('is_main', true)->value('path'), false);
        $this->get('/sets')->assertSee('href="'.e(url('/product/wrap-set')).'"', false);
    }

    #[Test]
    public function d_5_6_card_buttons_choose_size_choose_colour_or_add_to_cart(): void
    {
        $rosemary = $this->piece('rosemary-hijab');

        // A sized set: "Choose size". One colour, no sizes: "Add to cart" with that colour.
        $this->get('/sets')->assertOk()->assertSee('Choose size')->assertDontSee('data-add-to-bag', false);
        $this->get('/shop?q=rosemary')->assertSee('data-add-to-bag="'.$rosemary->id.'"', false)->assertSee('Add to cart');
        // Several colours, no sizes: "Choose color", opening the product page.
        $this->get('/hijabs?page=2')
            ->assertSee('Choose color')
            ->assertSee('aria-label="Choose color for Plain Hijab"', false);
    }

    #[Test]
    public function req_str_23_and_req_dmo_93_selecting_sizes_shows_products_in_any_selected_size(): void
    {
        $this->hoodedSetWithoutXxl();

        $this->assertSame(['wrap-set', 'belted-set'], $this->slugsOn('/sets?size=xxl'));
        $this->assertSame(['wrap-set', 'belted-set', 'hooded-set'], $this->slugsOn('/sets?size=xxl,s'));
        $this->assertSame(['wrap-set', 'belted-set'], $this->slugsOn('/shop?size=XXL'));     // either case
        $this->assertSame(['XXL'], $this->get('/sets?size=xxl')->viewData('result')->selectedSizes);
    }

    #[Test]
    public function req_dmo_92_the_size_filter_lists_only_the_sizes_in_the_category_in_size_order(): void
    {
        $this->assertSame(['S', 'M', 'L', 'XL', 'XXL'], $this->get('/sets')->viewData('result')->availableSizes->pluck('label')->all());
        $this->get('/sets')->assertSee('All sizes')->assertSee('name="size[]" value="xxl"', false);

        // No hijab has sizes, so the Hijabs page has no Size section, and an unknown size changes nothing.
        $hijabs = $this->get('/hijabs')->assertDontSee('All sizes');
        $this->assertSame([], $hijabs->viewData('result')->availableSizes->all());
        $this->assertSame($this->slugsOn('/hijabs'), $this->slugsOn('/hijabs?size=m'));
    }

    #[Test]
    public function req_dmo_95_colour_and_size_together_show_only_products_that_match_both(): void
    {
        $this->hoodedSetWithoutXxl();

        $this->assertSame(['belted-set'], $this->slugsOn('/sets?shade=pink&size=xxl'));
        $this->assertSame(['wrap-set', 'belted-set'], $this->slugsOn('/sets?shade=black&size=xxl'));
    }

    #[Test]
    public function d_5_7_the_filters_button_counts_the_colours_and_sizes_chosen(): void
    {
        $this->get('/sets?shade=black&size=m,l')->assertSee('<span class="ds-results__filters-count">3</span>', false);
        $this->get('/sets')->assertDontSee('ds-results__filters-count', false);
    }

    #[Test]
    public function req_dmo_21_and_22_category_tabs_keep_the_search_and_drop_colours_and_sizes(): void
    {
        $this->get('/shop?q=wrap&shade=black&size=m')
            ->assertSee('href="'.e(url('/sets?q=wrap')).'"', false)
            ->assertDontSee(e(url('/sets?q=wrap&shade=black')), false)
            ->assertDontSee('size=m&amp;page', false);
    }

    #[Test]
    public function req_dmo_23_pagination_shows_a_window_of_three_then_the_last_page(): void
    {
        $result = fn (int $page) => new ShopResult(null, new Collection, 42, $page, 7, new Collection, [], new Collection, [], '');

        $this->assertSame([1, 2, 3, null, 7], $result(1)->pageNumbers());
        $this->assertSame([2, 3, 4, null, 7], $result(4)->pageNumbers());
        $this->assertSame([5, 6, 7], $result(7)->pageNumbers());
    }

    #[Test]
    public function d_6_2_page_links_keep_the_search_colours_and_sizes(): void
    {
        $result = new ShopResult(null, new Collection, 42, 1, 7, new Collection, ['black'], new Collection, ['M', 'XL'], 'set');

        $this->assertSame(['q' => 'set', 'shade' => 'black', 'size' => 'm,xl', 'page' => 2], $result->query(['page' => 2]));
        $this->assertSame(3, $result->filterCount());
    }

    #[Test]
    public function d_6_2_a_page_outside_the_range_shows_the_nearest_valid_page(): void
    {
        $result = $this->get('/hijabs?page=99')->assertOk()->viewData('result');

        $this->assertSame($result->pageCount, $result->page);
    }

    #[Test]
    public function d_6_2_no_results_shows_the_empty_state(): void
    {
        $this->get('/shop?q=zzzzzz')->assertOk()->assertSee('No products found')->assertSee('Clear filters');
    }

    #[Test]
    public function d_6_2_a_category_without_products_shows_its_own_empty_state(): void
    {
        Product::query()->whereHas('category', fn ($q) => $q->where('slug', 'bundles'))->update(['is_published' => false]);

        $this->get('/bundles')->assertOk()->assertSee('Shop the collection')->assertSee('ds-shop--empty', false);
    }

    #[Test]
    public function req_sec_35_odd_shop_addresses_show_the_shop_never_an_error(): void
    {
        // Lists where text is expected (and the reverse) count as left out.
        foreach (['/shop?sort[]=new', '/shop?q[]=hijab', '/shop?page[]=2', '/shop?shade[][]=black', '/shop?shade[a]=black', '/hijabs?q[x]=1&sort[y]=2', '/shop?size[]=m', '/shop?size[a][]=m'] as $url) {
            $this->get($url)->assertOk()->assertSee('ds-card', false);
        }

        $this->get('/shop?shade=black')->assertOk();
        // Bytes that are not text at all.
        $this->get('/shop?q=%FF%FE')->assertOk();
        $this->inArabic()->get('/shop?q=%C3%28&shade=%FF&size=%FF')->assertOk();
        $this->assertSame('', ShopQuery::booleanTerms("\xFF\xFE"));
    }
}
