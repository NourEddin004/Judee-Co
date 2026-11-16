<?php

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

class BagEndpointsTest extends TestCase
{
    use DemoPieces, RefreshDatabase;

    private function check(array $ids)
    {
        return $this->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson('/bag/check', ['ids' => $ids]);
    }

    #[Test]
    public function req_chk_01_the_bag_check_returns_current_product_data_only(): void
    {
        $black = $this->piece('black-wrap-set');
        $navy = $this->piece('navy-wrap-set');

        $response = $this->check([$black->id, $navy->id])->assertOk();

        $response->assertExactJson(['products' => [[
            'id' => $black->id,
            'name' => 'Black Wrap Set',
            'url' => url('/product/wrap-set'),
            'image' => Storage::disk('public')->url('products/set-wrap-black-1-400.webp'),
            'price' => 35.0,
            'purchasable' => true,
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
        ], [
            'id' => $navy->id,
            'name' => 'Navy Wrap Set',
            'url' => url('/product/wrap-set?color=navy'),
            'image' => Storage::disk('public')->url('products/set-wrap-navy-1-400.webp'),
            'price' => 35.0,
            'purchasable' => true,
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
        ]]]);
    }

    #[Test]
    public function req_chk_16_req_cms_08_only_the_color_that_is_out_of_stock_cannot_be_bought(): void
    {
        $black = $this->piece('black-wrap-set');
        $navy = $this->piece('navy-wrap-set');
        $navy->update(['in_stock' => false]);

        $products = collect($this->check([$black->id, $navy->id])->assertOk()->json('products'))->pluck('purchasable', 'id');

        $this->assertSame([$black->id => true, $navy->id => false], $products->all());
    }

    #[Test]
    public function d_8_1_a_product_with_one_color_keeps_its_own_name_in_the_bag(): void
    {
        $rosemary = $this->piece('rosemary-hijab');

        $this->check([$rosemary->id])->assertOk()
            ->assertJsonPath('products.0.name', 'Rosemary Hijab')
            ->assertJsonPath('products.0.url', url('/product/rosemary-hijab'));
    }

    #[Test]
    public function req_sec_36_37_and_req_db_31_unpublished_products_are_left_out(): void
    {
        $black = $this->piece('black-wrap-set');
        $black->product->update(['is_published' => false]);

        $this->check([$black->id, $this->piece('navy-wrap-set')->id])->assertOk()->assertExactJson(['products' => []]);
    }

    #[Test]
    public function req_sec_35_input_is_validated(): void
    {
        $this->check(['abc'])->assertUnprocessable();
        $this->check(range(1, 51))->assertUnprocessable();
    }

    #[Test]
    public function req_sec_05_the_bag_check_sits_behind_csrf_protection(): void
    {
        // Laravel skips the CSRF check while tests run, so this checks the route is in the protected group.
        $route = app('router')->getRoutes()->getByName('bag.check');

        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertSame(['POST'], $route->methods());
    }

    #[Test]
    public function req_sec_34_public_endpoints_allow_60_requests_a_minute(): void
    {
        foreach (range(1, 60) as $i) {
            $this->check([1])->assertOk();
        }
        $this->check([1])->assertStatus(429);
    }
}
