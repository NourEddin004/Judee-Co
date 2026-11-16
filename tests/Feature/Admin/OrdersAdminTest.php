<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\ActivityLog;
use App\Models\Order;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** Orders in the admin, run by the owner (SRS 3.4). */
class OrdersAdminTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    private function order(array $attributes = []): Order
    {
        return Order::factory()->withItems()->create($attributes);
    }

    #[Test]
    public function req_ord_01_all_orders_are_listed_newest_first(): void
    {
        $this->asOwner();
        $old = $this->order(['placed_at' => now()->subDays(3)]);
        $new = $this->order(['placed_at' => now()]);
        $middle = $this->order(['placed_at' => now()->subDay(), 'status' => OrderStatus::Cancelled]);

        Livewire::test(ListOrders::class)->assertCanSeeTableRecords([$new, $middle, $old], inOrder: true);
    }

    #[Test]
    public function req_ord_01_an_arabic_order_reads_in_the_right_order_in_the_list(): void
    {
        $this->asOwner();
        $order = $this->order(['customer_name' => 'لينا خليل', 'city_name' => 'إربد', 'language' => 'ar']);

        // The name and the city are isolated, so the date after an Arabic city is not pulled right to left.
        Livewire::test(ListOrders::class)
            ->assertSee("#{$order->number} · \u{2068}لينا خليل\u{2069}", false)
            ->assertSee("\u{2068}إربد\u{2069} · ", false);
    }

    #[Test]
    public function the_orders_page_has_no_search_filters_or_csv_export(): void
    {
        $this->asOwner();
        $table = Livewire::test(ListOrders::class)->instance()->getTable();

        $this->assertSame([], $table->getFilters());
        $this->assertFalse($table->isSearchable());
        $this->get('/admin/orders')->assertOk()->assertDontSee(['Export CSV', 'Search orders', 'fi-global-search'], false);
    }

    #[Test]
    public function req_ord_04_filter_by_status_with_a_count_on_each_chip(): void
    {
        $this->asOwner();
        $new = $this->order();
        $confirmed = $this->order(['status' => OrderStatus::Confirmed]);
        $delivered = $this->order(['status' => OrderStatus::Delivered]);

        Livewire::test(ListOrders::class)
            ->set('activeTab', 'confirmed')
            ->assertCanSeeTableRecords([$confirmed])
            ->assertCanNotSeeTableRecords([$new, $delivered])
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$new, $confirmed, $delivered]);

        $tabs = Livewire::test(ListOrders::class)->instance()->getTabs();
        $this->assertSame([3, 1, 1, 0, 1, 0], array_values(array_map(fn ($tab) => (int) $tab->getBadge(), $tabs)));
    }

    #[Test]
    public function req_ord_10_req_ord_12_to_15_the_order_view_shows_customer_items_fee_and_total(): void
    {
        $this->asOwner();
        $order = Order::factory()->create([
            'customer_name' => 'Lina Khalil', 'phone' => '+962 791234567', 'address' => 'Wasfi Al-Tal St, building 12',
            'area' => 'Khalda', 'note' => 'Please call before delivery', 'subtotal' => '69.00', 'delivery_fee' => '2.00', 'total' => '71.00',
        ]);
        $order->items()->createMany([
            ['product_name' => 'Black Wrap Set', 'color_name' => 'Black', 'size' => 'M', 'unit_price' => '35.00', 'quantity' => 1],
            ['product_name' => 'Rosemary Hijab', 'color_name' => 'Rosemary', 'size' => null, 'unit_price' => '17.00', 'quantity' => 2],
        ]);

        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSeeInOrder([
            'Order #'.$order->number,
            'Lina Khalil', '+962 791234567', 'Wasfi Al-Tal St, building 12, Khalda, Amman', 'Please call before delivery', 'Cash on delivery',
            'Black Wrap Set', 'Size M', 'Qty 1', '35 JD',
            'Rosemary Hijab', 'Qty 2', '17 JD each', '34 JD',
            'Subtotal', '69 JD', 'Delivery to Amman', '2 JD', 'Total', '71 JD',
        ]);
    }

    #[Test]
    public function req_ord_16_req_ord_18_set_any_of_the_five_statuses_from_the_order_view(): void
    {
        $user = $this->asOwner();
        $order = $this->order();

        foreach ([OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::Cancelled, OrderStatus::New] as $status) {
            Livewire::test(ViewOrder::class, ['record' => $order->id])
                ->callAction(TestAction::make('status_'.$status->value)->schemaComponent('status', schema: 'infolist'))
                ->assertNotified('Marked as '.$status->label());

            $this->assertSame($status, $order->fresh()->status);
        }

        // Each change is in the activity log with who made it (REQ-USR-11).
        $this->assertSame(5, ActivityLog::query()->where('subject_type', 'Order')->where('user_id', $user->id)->where('action', 'updated')->count());
    }

    #[Test]
    public function req_ord_20_add_notes_to_an_order(): void
    {
        $user = $this->asOwner();
        $order = $this->order();

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction(TestAction::make('addNote')->schemaComponent('notes', schema: 'infolist'), ['body' => 'Called her, delivering on Sunday'])
            ->assertHasNoFormErrors()
            ->assertNotified('Note added')
            ->assertSee('Called her, delivering on Sunday')
            ->assertSee($user->name);

        $this->assertDatabaseHas('order_notes', ['order_id' => $order->id, 'user_id' => $user->id, 'body' => 'Called her, delivering on Sunday']);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction(TestAction::make('addNote')->schemaComponent('notes', schema: 'infolist'), ['body' => ''])
            ->assertHasFormErrors(['body' => 'required']);
    }

    #[Test]
    public function req_ord_22_print_a_packing_slip(): void
    {
        $this->asOwner();
        $order = $this->order(['customer_name' => 'Lina Khalil', 'note' => 'Please call before delivery']);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->assertActionHasUrl('packingSlip', route('filament.admin.orders.packing-slip', $order))
            ->assertActionShouldOpenUrlInNewTab('packingSlip');

        $this->get(route('filament.admin.orders.packing-slip', $order))
            ->assertOk()
            ->assertSee('Packing slip')
            ->assertSee('Order #'.$order->number)
            ->assertSee('Lina Khalil')
            ->assertSee('Please call before delivery')
            ->assertSee('Rosemary Hijab')
            ->assertSee('data-print', false)
            ->assertDontSee($order->link_token);
    }

    #[Test]
    public function req_sec_38_the_packing_slip_needs_an_admin_session(): void
    {
        $order = $this->order();

        $this->get(route('filament.admin.orders.packing-slip', $order))->assertRedirect('/admin/login');
    }

    #[Test]
    public function orders_are_never_created_or_deleted_in_the_admin(): void
    {
        $this->asOwner();
        $order = $this->order();

        $this->get('/admin/orders/create')->assertNotFound();
        $this->assertFalse(auth()->user()->can('delete', $order));
        $this->assertFalse(auth()->user()->can('create', Order::class));
    }

    #[Test]
    public function req_db_27_req_db_19_the_list_loads_25_rows_with_a_fixed_number_of_queries(): void
    {
        $this->asOwner();
        Order::factory()->withItems()->count(30)->create();

        DB::enableQueryLog();
        Livewire::test(ListOrders::class)->assertCountTableRecords(30)->assertCanSeeTableRecords(Order::query()->latest('placed_at')->latest('id')->limit(25)->get());
        $orderQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'order'))->count();

        $this->assertLessThan(15, $orderQueries);
    }

    #[Test]
    public function the_menu_shows_how_many_orders_wait_for_confirmation(): void
    {
        $this->asOwner();
        $this->order();
        $this->order();
        $this->order(['status' => OrderStatus::Confirmed]);

        $this->assertSame('2', OrderResource::getNavigationBadge());
        $html = $this->get('/admin/orders')->assertOk()->getContent();
        $this->assertStringContainsString('/admin/orders', substr($html, strpos($html, 'class="jude-bottom-nav"')));   // phone menu (preview)
    }

    /** Runs the export and returns the file it streams. */
    private function exportedCsv(\Closure $export): string
    {
        $component = $export();
        $download = $component->effects['download'] ?? null;
        $this->assertNotNull($download, 'The export did not start a download.');
        $this->assertStringStartsWith('jude-orders-', $download['name']);

        return base64_decode($download['content']);
    }
}
