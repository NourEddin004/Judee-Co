<?php

namespace Tests\Feature\Storefront;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/**
 * A disabled control says why, right below it, unless the same reason is already written in
 * the same card or panel (REQ-UX-27, as decided on 2026-10-24). The checkout button is covered
 * in CheckoutTest.
 */
class DisabledReasonsTest extends TestCase
{
    use ActsAsAdmin, DemoPieces, RefreshDatabase;

    private function soldOut(string $slug): void
    {
        $this->piece($slug)->update(['in_stock' => false]);
    }

    #[Test]
    public function req_ux_27_req_chk_16_a_sold_out_product_page_adds_no_line_because_out_of_stock_is_already_written(): void
    {
        $this->soldOut('rosemary-hijab');

        $html = $this->get('/product/rosemary-hijab')->assertOk()->assertSee('Out of stock')->getContent();

        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*disabled[^>]*data-add-button>/', $html);
        $this->assertStringNotContainsString('id="add-reason"', $html);
    }

    #[Test]
    public function req_ux_27_req_dmo_55_the_quantity_plus_says_why_it_stops_at_9(): void
    {
        $this->get('/product/rosemary-hijab')->assertOk()
            ->assertSee('aria-describedby="quantity-reason" data-stepper-step="1"', false)
            ->assertSee('<p class="ds-disabled-reason" id="quantity-reason" hidden data-stepper-reason>You can add up to 9 of one piece.</p>', false);
        $this->inArabic()->get('/product/rosemary-hijab')->assertOk()->assertSee('data-stepper-reason', false);
    }

    #[Test]
    public function req_ux_27_req_chk_16_a_sold_out_card_says_out_of_stock_on_its_button(): void
    {
        $this->soldOut('rosemary-hijab');

        $this->assertMatchesRegularExpression(
            '/data-add-to-bag="\d+"\s+data-name="Rosemary Hijab"\s+disabled\s*>\s*Out of stock\s*<\/button>/',
            $this->get('/hijabs')->assertOk()->getContent(),
        );
        $this->assertMatchesRegularExpression(
            '/data-name="Olive Noir Hijab"\s+aria-label="Add to cart: Olive Noir Hijab"\s*>\s*Add to cart\s*<\/button>/',
            $this->get('/hijabs')->getContent(),
            'an item in stock keeps the demo button',
        );
        $this->assertMatchesRegularExpression('/disabled\s*>\s*غير متوفر\s*<\/button>/', $this->inArabic()->get('/hijabs')->getContent());
    }

    #[Test]
    public function req_ux_27_each_bag_line_has_a_place_for_the_reason_its_plus_stopped(): void
    {
        $this->get('/cart')->assertOk()->assertSee('<p class="ds-disabled-reason ds-cart-line__reason" hidden data-line-reason></p>', false);
        $this->get('/cart')->assertSee('"maxQuantity":"You can add up to :max of one piece."', false);
    }

    #[Test]
    public function req_chk_17_a_paused_store_still_lets_shoppers_fill_their_bag_and_only_checkout_closes(): void
    {
        Setting::query()->updateOrCreate(['key' => 'store_paused'], ['value' => '1']);

        $product = $this->get('/product/rosemary-hijab')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<button type="submit" class="ds-btn ds-btn--primary ds-btn--block"\s+data-add-button>/', $product);
        $this->get('/hijabs')->assertSee('aria-label="Add to cart: Rosemary Hijab"', false);
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*data-place-order[^>]*disabled/', $this->get('/checkout')->getContent());
    }

    #[Test]
    public function req_ux_27_the_admin_hover_photo_switch_says_why_it_is_off_for_the_main_photo(): void
    {
        Storage::fake('public');
        foreach (ProductPhoto::query()->pluck('path') as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $this->asOwner();
        $product = Product::query()->where('slug', 'wrap-set')->firstOrFail();

        // Each colour has its own photos, and its own main and hover photo.
        $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);
        $color = array_key_first($page->get('data.colors'));
        $photos = collect($page->get("data.colors.{$color}.photos"));
        $main = $photos->filter(fn (array $photo) => $photo['is_main'])->keys()->first();
        $other = $photos->reject(fn (array $photo) => $photo['is_main'])->keys()->first();

        $page->assertSee('The main photo can\'t also be the hover photo.')
            ->assertFormFieldDisabled("colors.{$color}.photos.{$main}.is_hover")
            ->assertFormFieldEnabled("colors.{$color}.photos.{$other}.is_hover");
    }
}
