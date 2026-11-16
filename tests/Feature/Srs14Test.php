<?php

namespace Tests\Feature;

use App\Domain\Orders\OrderSearch;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Category;
use App\Models\DeliveryCity;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/** The client's SRS 1.4 changes: categories, photos, delivery fees, Gulf delivery, phone countries, order lookup. */
class Srs14Test extends TestCase
{
    use ActsAsAdmin, DemoPieces, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        RateLimiter::clear('orders:127.0.0.1');
    }

    private function gulfOrder(array $changes = []): array
    {
        return array_replace([
            'full_name' => 'Sara Al-Otaibi',
            'country' => 'SA',
            'phone' => '51 234 5678',
            'email' => '',
            'destination' => 'saudi-arabia',
            'gulf_city' => 'Riyadh',
            'gulf_area' => 'Olaya',
            'address' => 'King Fahd Rd, building 7',
            'note' => '',
            'website' => '',
            'lines' => [['id' => $this->piece('rosemary-hijab')->id, 'size' => null, 'qty' => 1]],
        ], $changes);
    }

    private function order(array $form)
    {
        return $this->postJson('/checkout', $form, ['Origin' => config('app.url'), 'Sec-Fetch-Site' => 'same-origin']);
    }

    #[Test]
    public function req_str_29_req_cms_47_the_four_new_categories_exist_in_both_languages(): void
    {
        foreach (['jackets' => 'جاكيتات', 'shirts' => 'قمصان', 'hijab-basics' => 'أساسيات الحجاب', 'dresses' => 'فساتين'] as $slug => $arabic) {
            $this->assertSame($arabic, Category::query()->where('slug', $slug)->value('name_ar'));
        }
        $this->assertSame(7, Category::query()->count());

        foreach (['/jackets' => 'Jackets', '/shirts' => 'Shirts', '/hijab-basics' => 'Hijab Basics', '/dresses' => 'Dresses'] as $url => $name) {
            $this->get($url)->assertOk()->assertSee($name);
        }
        $this->get('/shop')->assertSee('href="'.category_url('hijab-basics').'"', false);
    }

    #[Test]
    public function req_str_30_each_color_holds_up_to_4_photos(): void
    {
        $this->assertSame(4, config('store.max_photos_per_color'));
    }

    #[Test]
    public function req_chk_26_every_jordan_city_costs_3_jd_and_every_gulf_country_25_jd(): void
    {
        $jordan = DeliveryCity::query()->where('country', 'JO')->pluck('fee')->unique()->values()->all();
        $gulf = DeliveryCity::query()->where('country', '!=', 'JO')->orderBy('position')->pluck('fee', 'country')->all();

        $this->assertEquals(['3.00'], $jordan);
        $this->assertEquals(['SA' => '25.00', 'AE' => '25.00', 'KW' => '25.00', 'QA' => '25.00', 'BH' => '25.00', 'OM' => '25.00'], $gulf);
    }

    #[Test]
    public function req_chk_27_req_chk_31_a_gulf_order_takes_the_typed_city_and_the_gulf_fee(): void
    {
        $this->order($this->gulfOrder())->assertCreated();

        $order = Order::query()->sole();
        $this->assertSame(['SA', 'Riyadh, Saudi Arabia', 'Olaya', '25.00', '42.00'], [$order->country, $order->city_name, $order->area, $order->delivery_fee, $order->total]);
        $this->assertSame('+966 512345678', $order->phone);
    }

    #[Test]
    public function req_chk_28_a_gulf_order_needs_its_city_and_area(): void
    {
        $this->order($this->gulfOrder(['gulf_city' => '', 'gulf_area' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['gulf_city', 'gulf_area']);

        $this->order($this->gulfOrder(['destination' => 'atlantis']))
            ->assertUnprocessable()->assertJsonValidationErrors(['destination']);

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function req_chk_29_a_switched_off_gulf_country_takes_no_orders(): void
    {
        DeliveryCity::query()->where('key', 'saudi-arabia')->update(['is_active' => false]);

        $this->order($this->gulfOrder())->assertUnprocessable();
        $this->get('/checkout')->assertDontSee('value="saudi-arabia"', false)->assertSee('value="kuwait"', false);
    }

    #[Test]
    public function d_8_3_28_phone_countries_are_offered(): void
    {
        $this->assertCount(28, config('store.phone_countries'));
        $this->assertSame(28, collect(config('store.phone_countries'))->pluck('code')->unique()->count());
    }

    #[Test]
    public function req_chk_33_the_confirmation_page_tells_the_customer_to_keep_the_number(): void
    {
        $this->order($this->gulfOrder())->assertCreated();
        $order = Order::query()->sole();

        $this->get('/order/'.$order->link_token)->assertSee('#'.$order->number)
            ->assertSee('Keep this number. Quote it if you contact us about your order.');
    }

    #[Test]
    public function req_ord_34_a_full_order_number_finds_only_that_order(): void
    {
        $wanted = Order::factory()->create(['number' => 'K7M2QX']);
        Order::factory()->create(['number' => 'K7M2QA']);
        Order::factory()->create(['number' => 'ZZZZZZ', 'customer_name' => 'K7M2QX Lover']);

        foreach (['K7M2QX', '#k7m2qx', ' #K7M2QX '] as $typed) {
            $this->assertSame([$wanted->id], OrderSearch::apply(Order::query(), $typed)->pluck('id')->all(), $typed);
        }
        // A part of a number still lists every match, names included, as before (REQ-ORD-02).
        $this->assertCount(3, OrderSearch::apply(Order::query(), 'K7M2Q')->get());
    }

    #[Test]
    public function req_ord_35_find_order_opens_the_order(): void
    {
        $this->asOwner();
        $order = Order::factory()->create(['number' => 'K7M2QX']);

        Livewire::test(Dashboard::class)
            ->callAction('findOrder', ['number' => '#k7m2qx'])
            ->assertRedirect(OrderResource::getUrl('view', ['record' => $order]));

        Livewire::test(Dashboard::class)
            ->callAction('findOrder', ['number' => 'AAAAAA'])
            ->assertNoRedirect()
            ->assertNotified('No order with this number');
    }
}
