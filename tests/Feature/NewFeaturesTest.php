<?php

namespace Tests\Feature;

use App\Domain\Catalog\SizeFit;
use App\Domain\Orders\OrderStatus;
use App\Filament\Resources\SizeGuides\Pages\ManageSizeGuides;
use App\Filament\Widgets\OrderStatuses;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/** 2026-11-20: dashboard status links, Track order, Find my size and free delivery from 50 JD. */
class NewFeaturesTest extends TestCase
{
    use ActsAsAdmin, DemoPieces, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        RateLimiter::clear('orders:127.0.0.1');
    }

    private function place(array $lines, string $city = 'amman'): Order
    {
        $this->postJson('/checkout', [
            'full_name' => 'Lina Khalil', 'country' => 'JO', 'phone' => '079 123 4567', 'email' => '',
            'city' => $city, 'area' => 'Khalda', 'area_other' => '', 'address' => 'Street 1', 'note' => '', 'website' => '',
            'lines' => $lines,
        ], ['Origin' => config('app.url'), 'Sec-Fetch-Site' => 'same-origin'])->assertCreated();

        return Order::query()->latest('id')->firstOrFail();
    }

    #[Test]
    public function each_dashboard_status_box_opens_the_orders_of_that_status(): void
    {
        $this->asOwner();
        $out = Order::factory()->withItems()->create(['status' => OrderStatus::OutForDelivery]);
        $new = Order::factory()->withItems()->create(['status' => OrderStatus::New]);

        Livewire::test(OrderStatuses::class)->assertSeeHtml('/admin/orders?tab=out_for_delivery');

        $this->get('/admin/orders?tab=out_for_delivery')->assertOk()
            ->assertSee('#'.$out->number)->assertDontSee('#'.$new->number);
    }

    #[Test]
    public function delivery_in_jordan_is_free_from_50_jd_and_paid_below(): void
    {
        $set = $this->piece('black-wrap-set')->id;   // 35 JD

        $below = $this->place([['id' => $set, 'size' => 'M', 'qty' => 1]]);
        $this->assertSame(['35.00', '3.00', '38.00'], [$below->subtotal, $below->delivery_fee, $below->total]);

        $above = $this->place([['id' => $set, 'size' => 'M', 'qty' => 2]]);
        $this->assertSame(['70.00', '0.00', '70.00'], [$above->subtotal, $above->delivery_fee, $above->total]);
    }

    #[Test]
    public function track_order_shows_the_status_and_never_the_customers_details(): void
    {
        $order = Order::factory()->withItems()->create(['status' => OrderStatus::OutForDelivery, 'customer_name' => 'Lina Khalil', 'address' => 'Secret St 9']);

        $json = $this->getJson('/track?number='.strtolower($order->number))->assertOk()
            ->assertJsonPath('order.status', 'out_for_delivery')
            ->assertJsonPath('order.steps.2.current', true)
            ->assertJsonPath('order.steps.3.done', false);
        $this->assertStringNotContainsString('Lina', $json->getContent());
        $this->assertStringNotContainsString('Secret St', $json->getContent());
        $this->assertStringNotContainsString((string) $order->phone, $json->getContent());

        $this->getJson('/track?number=ZZZZZZ')->assertNotFound()->assertJsonPath('error', 'not_found');
        $this->getJson('/track?number=12')->assertNotFound()->assertJsonPath('error', 'invalid');

        // Without scripts the page does the same, in Arabic too.
        $this->get('/track?number=%23'.$order->number)->assertOk()->assertSee('Out for delivery')->assertDontSee('Lina');
        $this->inArabic()->get('/track?number='.$order->number)->assertOk()->assertSee('في الطريق إليك');
    }

    #[Test]
    public function track_order_is_rate_limited(): void
    {
        foreach (range(1, 15) as $i) {
            $this->getJson('/track?number=AAAAA'.($i % 10))->assertNotFound();
        }
        $this->getJson('/track?number=AAAAAA')->assertStatus(429);
    }

    #[Test]
    public function the_menu_offers_track_order_and_the_thank_you_page_asks_to_keep_the_receipt(): void
    {
        $this->get('/')->assertOk()->assertSee('data-modal-open="track-order"', false)->assertSee('Track your order');

        $order = $this->place([['id' => $this->piece('black-wrap-set')->id, 'size' => 'M', 'qty' => 1]]);
        $this->get('/order/'.$order->link_token)->assertOk()
            ->assertSee('Save your receipt to track your order')
            ->assertSee('#'.$order->number);
    }

    #[Test]
    public function find_my_size_picks_the_size_from_height_and_weight(): void
    {
        $rows = SizeFit::defaultsFor(['S', 'M', 'L', 'XL', 'XXL']);

        $this->assertSame(['size' => 'M', 'exact' => true], SizeFit::recommend($rows, 163, 60));
        $this->assertSame('L', SizeFit::recommend($rows, 165, 70)['size']);
        $this->assertSame('XXL', SizeFit::recommend($rows, 170, 120)['size']);   // beyond the chart: the closest
        $this->assertFalse(SizeFit::recommend($rows, 170, 120)['exact']);
        $this->assertSame('M', SizeFit::recommend($rows, 158, 55)['size']);      // on the line between S and M: the larger
    }

    #[Test]
    public function the_product_page_offers_find_my_size_only_where_the_size_guide_has_heights_and_weights(): void
    {
        $this->get('/product/wrap-set')->assertOk()->assertSee('Find my size')->assertSee('data-fit-rows', false);
        $this->get('/product/rosemary-hijab')->assertOk()->assertDontSee('Find my size');

        Category::query()->where('slug', 'sets')->firstOrFail()->sizeGuide->update(['fit' => null]);
        $this->get('/product/wrap-set')->assertOk()->assertDontSee('Find my size');
    }

    #[Test]
    public function the_owner_edits_the_heights_and_weights_of_a_size_guide(): void
    {
        $this->asOwner();
        $guide = Category::query()->where('slug', 'sets')->firstOrFail()->sizeGuide;
        $this->assertSame('M', $guide->fitRows()[1]['size']);

        $this->get('/admin/size-guides')->assertOk();
        Livewire::test(ManageSizeGuides::class)
            ->mountTableAction('edit', $guide)
            ->setTableActionData(['fit' => [['size' => 'S', 'height_min' => 150, 'height_max' => 158, 'weight_min' => 40, 'weight_max' => 52]]])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame([['size' => 'S', 'height_min' => 150, 'height_max' => 158, 'weight_min' => 40, 'weight_max' => 52]], $guide->fresh()->fitRows());
    }
}
