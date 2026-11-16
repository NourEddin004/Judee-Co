<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\RackItems\Pages\ManageRackItems;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\RackItem;
use App\Models\User;
use Database\Seeders\LocalAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** The owner is the only admin account (SRS 3.6, AS-4, C.1), and every admin change is logged. */
class AdminAccessTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    public static function adminPages(): array
    {
        return [
            ['/admin'], ['/admin/orders'], ['/admin/products'], ['/admin/products/create'], ['/admin/categories'],
            ['/admin/colors'], ['/admin/size-guides'], ['/admin/hero-rack'],
        ];
    }

    /** Taken out of the admin to keep it simple (8 Nov 2026). The data stays and the site still shows it. */
    public static function removedPages(): array
    {
        return [
            ['/admin/collections'], ['/admin/sizes'], ['/admin/site-texts'], ['/admin/reviews'], ['/admin/faqs'],
            ['/admin/terms-page'], ['/admin/delivery'], ['/admin/settings'], ['/admin/activity'],
        ];
    }

    #[Test]
    #[DataProvider('removedPages')]
    public function the_removed_sections_are_gone_from_the_admin(string $url): void
    {
        $this->asOwner();

        $this->get($url)->assertNotFound();
        $this->get('/admin')->assertDontSee(['Home &amp; FAQ', 'Site texts', 'Collections', 'Activity log', 'Store settings'], false);
    }

    #[Test]
    #[DataProvider('adminPages')]
    public function the_owner_opens_every_admin_page(string $url): void
    {
        $this->asOwner();

        $this->get($url)->assertOk();
    }

    #[Test]
    #[DataProvider('adminPages')]
    public function req_sec_39_an_account_that_is_not_the_owner_is_refused_every_admin_page(string $url): void
    {
        $this->signIn($this->formerStaff());

        $this->get($url)->assertForbidden();
    }

    #[Test]
    public function req_sec_39_an_account_that_is_not_the_owner_cannot_run_admin_actions_either(): void
    {
        $this->signIn($this->formerStaff());
        $item = RackItem::query()->first();
        $product = Product::query()->first();

        Livewire::test(ManageRackItems::class)->assertForbidden();
        Livewire::test(ListOrders::class)->assertForbidden();
        Livewire::test(EditProduct::class, ['record' => $product->slug])->assertForbidden();
        $this->assertTrue($item->refresh()->is_visible);
    }

    #[Test]
    public function as_4_there_is_no_team_page_and_the_local_seed_adds_only_the_owner(): void
    {
        $this->asOwner();

        $this->get('/admin/team')->assertNotFound();
        $this->get('/admin')->assertDontSee('Team');

        $this->seed(LocalAdminSeeder::class);
        $this->assertSame(['owner@jude.test'], User::query()->where('email', 'like', '%@jude.test')->pluck('email')->all());
    }

    #[Test]
    public function req_usr_11_req_usr_12_each_admin_change_records_who_and_when(): void
    {
        $owner = $this->asOwner();
        $this->travelTo(now()->setTime(10, 30));
        $product = Product::query()->where('slug', 'wrap-set')->firstOrFail();

        Livewire::test(EditProduct::class, ['record' => $product->slug])
            ->fillForm(['price' => 39])
            ->call('save')
            ->assertHasNoFormErrors();

        $entry = ActivityLog::query()->where('subject_type', 'Product')->where('subject_id', $product->id)->latest('id')->firstOrFail();
        $this->assertSame($owner->id, $entry->user_id);
        $this->assertSame('updated', $entry->action);
        $this->assertEquals(39, $entry->changes['price']);
        $this->assertTrue($entry->created_at->equalTo(now()));
    }
}
