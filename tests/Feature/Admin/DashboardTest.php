<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\OrderFigures;
use App\Domain\Orders\OrderStatus;
use App\Domain\Orders\SetOrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrderStatuses;
use App\Filament\Widgets\StoreSummary;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/** The dashboard: order statuses, new orders and two cards, worked out by counting queries (REQ-DB-28). */
class DashboardTest extends TestCase
{
    use ActsAsAdmin, DemoPieces, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-24 12:00', 'Asia/Amman'));
        $this->asOwner();

        $order = fn (string $status, string $total, string $placed) => Order::factory()->withItems()->create([
            'status' => OrderStatus::from($status), 'total' => $total, 'placed_at' => Carbon::parse($placed, 'Asia/Amman'),
        ]);
        $order('new', '19.00', '2026-10-24 09:00');           // today
        $order('new', '37.00', '2026-10-23 20:00');
        $order('confirmed', '52.00', '2026-10-18 00:30');     // 7th day back, counts
        $order('cancelled', '100.00', '2026-10-22 10:00');    // cancelled: never a sale
        $order('delivered', '40.00', '2026-10-17 23:59');     // 8 days back: this month only
        $order('delivered', '75.00', '2026-10-01 00:00');     // first minute of the month
        $order('out_for_delivery', '20.00', '2026-09-30 23:59');  // last month
    }

    #[Test]
    public function req_ord_26_new_orders_waiting_for_confirmation(): void
    {
        $this->assertSame(2, app(OrderFigures::class)->waitingForConfirmation());
        Livewire::test(StoreSummary::class)->assertSeeInOrder(['New orders', '2', 'Waiting for you to confirm']);
    }

    #[Test]
    public function req_ord_29_the_number_of_orders_in_each_status_across_the_top(): void
    {
        $this->assertSame(
            ['new' => 2, 'confirmed' => 1, 'out_for_delivery' => 1, 'delivered' => 2, 'cancelled' => 1],
            app(OrderFigures::class)->countsByStatus(),
        );
        Livewire::test(OrderStatuses::class)
            ->assertSeeInOrder(['New', '2', 'Confirmed', '1', 'Out for delivery', '1', 'Delivered', '2', 'Cancelled', '1'])
            ->assertSeeHtml('/admin/orders?tab=confirmed');
    }

    #[Test]
    public function only_new_orders_are_on_the_dashboard_and_a_confirmed_one_leaves_it(): void
    {
        $new = Order::query()->where('status', OrderStatus::New)->orderByDesc('placed_at')->get();
        $others = Order::query()->where('status', '!=', OrderStatus::New)->get();

        Livewire::test(LatestOrders::class)
            ->assertCanSeeTableRecords($new, inOrder: true)
            ->assertCanNotSeeTableRecords($others);

        app(SetOrderStatus::class)($new->first(), OrderStatus::Confirmed);

        Livewire::test(LatestOrders::class)
            ->assertCanNotSeeTableRecords($new->take(1))
            ->assertCanSeeTableRecords($new->skip(1));
        Livewire::test(ListOrders::class, ['activeTab' => 'confirmed'])
            ->assertCanSeeTableRecords($new->take(1));
    }

    #[Test]
    public function req_ord_31_req_ord_32_products_on_the_site_and_out_of_stock(): void
    {
        $this->piece('rosemary-hijab')->update(['in_stock' => false]);
        $this->piece('black-hijab')->update(['in_stock' => false]);
        // A colour of a hidden product is not on the site, so it is not counted.
        $box = Product::withoutGlobalScopes()->where('slug', 'hijab-box')->firstOrFail();
        $box->update(['is_published' => false]);
        $box->colors()->update(['in_stock' => false]);
        $live = Product::withoutGlobalScopes()->where('is_published', true)->count();

        Livewire::test(StoreSummary::class)->assertSeeInOrder(['Products on the site', (string) $live, '2 colors out of stock']);
    }

    #[Test]
    public function req_db_28_figures_are_counted_in_the_database_never_by_loading_orders(): void
    {
        DB::enableQueryLog();
        Livewire::test(StoreSummary::class);
        Livewire::test(OrderStatuses::class);

        $orderQueries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql) => preg_match('/from [`"]?orders[`"]?/i', $sql));

        $this->assertNotEmpty($orderQueries);
        foreach ($orderQueries as $sql) {
            $this->assertMatchesRegularExpression('/count\(|sum\(/i', $sql, "Loads orders instead of counting: {$sql}");
        }
    }

    #[Test]
    public function the_dashboard_shows_the_statuses_and_only_two_cards(): void
    {
        $this->get('/admin')->assertOk()
            ->assertSee(['New orders', 'Products on the site', 'Out for delivery', 'View store'])
            ->assertDontSee(['Sales, last 7 days', 'On the rack']);
    }
}
