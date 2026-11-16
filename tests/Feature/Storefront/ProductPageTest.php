<?php

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\CatalogData;
use App\Domain\Catalog\RelatedProducts;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use DemoPieces, RefreshDatabase;

    #[Test]
    public function req_str_09_10_11_12_the_page_shows_gallery_price_colours_and_stock(): void
    {
        $this->get('/product/wrap-set')->assertOk()
            ->assertSee('<title>Wrap Set | JUDE Boutique</title>', false)
            ->assertSee('<h1 class="ds-pdp__title">Wrap Set</h1>', false)
            ->assertSee('data-gallery-thumb', false)
            ->assertSee('35 JD')
            ->assertSee('Available colors')
            ->assertSee('<strong data-color-name>Black</strong>', false)
            ->assertSee('In stock');
    }

    #[Test]
    public function req_str_09_the_gallery_shows_the_photos_of_the_selected_colour(): void
    {
        $navy = $this->piece('navy-wrap-set');
        $html = $this->get('/product/wrap-set?color=navy')->assertOk()->getContent();
        $live = substr($html, strpos($html, 'data-gallery>'), 2000);

        $this->assertStringContainsString($navy->photos()->where('is_main', true)->value('path'), $live);
        $this->assertStringContainsString('alt="Navy Wrap Set product and styling view"', $live);
        // Every colour's photos are ready in the page, so choosing a colour needs no reload (REQ-DMO-99).
        $this->assertSame(4, substr_count($html, '<template data-gallery-for='));
    }

    #[Test]
    public function req_str_25_the_product_page_offers_each_colour_in_the_owners_order(): void
    {
        $page = $this->get('/product/wrap-set')->assertOk();

        $page->assertSeeInOrder(['data-color-slug="black"', 'data-color-slug="navy"', 'data-color-slug="chocolate-brown"', 'data-color-slug="beige"'], false)
            ->assertSee('data-item-name="Navy Wrap Set"', false)
            ->assertSee('<span class="ds-visually-hidden">Navy</span>', false);
        // A product with one colour has no colour choice.
        $this->get('/product/rosemary-hijab')->assertOk()->assertDontSee('Available colors')->assertDontSee('name="color"', false);
    }

    #[Test]
    public function req_dmo_98_the_page_opens_on_the_colour_in_the_address_or_on_the_first_one(): void
    {
        $navy = $this->piece('navy-wrap-set');
        $black = $this->piece('black-wrap-set');

        $this->get('/product/wrap-set?color=navy')
            ->assertSee('data-buy="'.$navy->id.'" data-name="Navy Wrap Set"', false)
            ->assertSee('value="'.$navy->id.'" checked', false)
            ->assertSee('<strong data-color-name>Navy</strong>', false)
            // Both languages keep the colour; search engines get the product's own address.
            ->assertSee('href="'.e(url('/product/wrap-set?color=navy&lang=ar')).'"', false)
            ->assertSee('<link rel="canonical" href="'.url('/product/wrap-set').'">', false);

        foreach (['/product/wrap-set', '/product/wrap-set?color=lilac', '/product/wrap-set?color[]=navy'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-buy="'.$black->id.'"', false);
        }
    }

    #[Test]
    public function req_dmo_99_each_colour_carries_its_photos_name_and_stock_so_choosing_one_needs_no_reload(): void
    {
        $navy = $this->piece('navy-wrap-set');
        $navy->update(['in_stock' => false]);
        $html = $this->get('/product/wrap-set')->assertOk()->getContent();

        // The navy photos wait in a template, which loads no image until product.js puts it in the gallery.
        $this->assertSame(1, preg_match('#<template data-gallery-for="'.$navy->id.'">(.*?)</template>#s', $html, $photos));
        $this->assertStringContainsString($navy->photos()->where('is_main', true)->value('path'), $photos[1]);
        // The navy choice carries what the page then shows and the ?color= it puts in the address.
        $this->assertMatchesRegularExpression('/name="color" value="'.$navy->id.'"\s+data-color-slug="navy" data-color-label="Navy" data-item-name="Navy Wrap Set" data-in-stock="0">/', $html);
        foreach (['aria-live="polite" data-color-facts', '<strong data-color-name>Black</strong>', 'data-stock-in>', 'data-stock-out>'] as $part) {
            $this->assertStringContainsString($part, $html);
        }
        $this->inArabic()->get('/product/wrap-set')->assertSee('data-color-slug="navy" data-color-label="كحلي"', false);
    }

    #[Test]
    public function req_str_12_req_dmo_100_an_out_of_stock_colour_can_be_chosen_but_not_added(): void
    {
        $this->piece('navy-wrap-set')->update(['in_stock' => false]);

        $page = $this->get('/product/wrap-set?color=navy');
        $html = $page->getContent();
        $page->assertSee('<span class="ds-pdp__stock ds-pdp__stock--out"  data-stock-out>Out of stock</span>', false)
            ->assertSee('title="Navy, out of stock"', false)
            ->assertSee('<span class="ds-visually-hidden">Navy, out of stock</span>', false)
            ->assertSeeInOrder(['ds-btn ds-btn--primary ds-btn--block', 'disabled', 'Add to bag'], false);
        $this->assertStringContainsString('ds-color-picker__option is-out', $html);
        $this->assertStringNotContainsString('name="color" value="'.$this->piece('navy-wrap-set')->id.'" disabled', $html);

        // The other colours are still in stock and can be added.
        $this->get('/product/wrap-set')->assertSee('data-stock-in>', false)
            ->assertDontSee('data-add-button disabled', false);
    }

    #[Test]
    public function req_dmo_103_a_demo_product_that_is_now_one_colour_redirects_to_it(): void
    {
        $this->get('/product/navy-wrap-set')->assertStatus(301)->assertRedirect(url('/product/wrap-set?color=navy'));
        $this->inArabic()->get('/product/navy-wrap-set')->assertStatus(301)->assertRedirect(url('/product/wrap-set?color=navy'));
        $this->get('/product/black-hijab')->assertStatus(301)->assertRedirect(url('/product/plain-hijab?color=black'));
        // A one-colour demo product kept its own address.
        $this->get('/product/rosemary-hijab')->assertOk();
    }

    #[Test]
    public function req_str_19_the_arabic_page_names_the_colour_after_the_product(): void
    {
        $this->inArabic()->get('/product/wrap-set?color=navy')->assertOk()
            ->assertSee('data-item-name="طقم بربطة جانبية كحلي"', false)
            ->assertSee('<strong data-color-name>كحلي</strong>', false);
    }

    #[Test]
    public function req_str_14_and_15_sized_products_require_a_size_and_show_the_size_guide(): void
    {
        $this->get('/product/wrap-set')
            ->assertSee('name="size" value="M" required', false)
            ->assertSee('Size guide')
            ->assertSee('Body measurements in cm. Between two sizes? Pick the larger one.');

        $this->get('/product/olive-noir-hijab')->assertDontSee('name="size"', false)->assertDontSee('id="size-guide"', false);
    }

    #[Test]
    public function req_dmo_24_and_26_related_products_are_up_to_6_from_the_same_category(): void
    {
        $product = Product::query()->where('slug', 'olive-noir-hijab')->firstOrFail();
        $related = app(RelatedProducts::class)->for($product, $product->colors()->firstOrFail(), []);

        $this->assertCount(6, $related);
        $this->assertNotContains($product->id, $related->pluck('id'));
        $this->assertTrue($related->every(fn ($item) => $item->category_id === $product->category_id));
    }

    #[Test]
    public function req_dmo_25_related_products_are_ranked_by_score(): void
    {
        $product = Product::query()->where('slug', 'olive-noir-hijab')->firstOrFail();
        $shown = $product->colors()->firstOrFail();
        $familyColors = Color::query()->where('shade_id', app(CatalogData::class)->shadeOfColor($shown->color_id)->id)->pluck('id');
        $others = Product::query()->where('category_id', $product->category_id)->whereKeyNot($product->id)
            ->whereDoesntHave('colors', fn ($query) => $query->whereIn('color_id', $familyColors))->orderByDesc('display_order')->get();
        [$featured, $shared] = [$others[0], $others[1]];
        Product::query()->where('category_id', $product->category_id)->update(['is_featured' => false]);
        $featured->update(['is_featured' => true]);
        $collection = Collection::create(['name_en' => 'Test', 'slug' => 'test']);
        $collection->products()->attach([$product->id, $shared->id]);

        $ids = app(RelatedProducts::class)->for($product, $shown, [$collection->id])->pluck('id');

        // A colour of the same family (+4) beats featured (+2), which beats one shared collection (+1).
        $this->assertTrue(Product::query()->findOrFail($ids->first())->colors()->whereIn('color_id', $familyColors)->exists());
        $this->assertLessThan($ids->search($shared->id), $ids->search($featured->id));
    }

    #[Test]
    public function req_dmo_83_the_first_photo_loads_with_high_priority(): void
    {
        $html = $this->get('/product/wrap-set')->getContent();

        $this->assertSame(1, substr_count($html, 'fetchpriority="high"'));    // only the photo on screen, not the other colours'
    }

    #[Test]
    public function d_6_3_an_unknown_product_shows_not_found_with_404(): void
    {
        $this->get('/product/no-such-set')->assertNotFound()->assertSee('This page is not here');
    }
}
