<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\RackItems\Pages\ManageRackItems;
use App\Models\Product;
use App\Models\RackItem;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\Concerns\DemoPieces;
use Tests\Support\TestImages;
use Tests\TestCase;

/** Managing the hero rack (REQ-RCK-01 to REQ-RCK-16). */
class HeroRackAdminTest extends TestCase
{
    use ActsAsAdmin, DemoPieces, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        foreach (RackItem::query()->pluck('image_path') as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $this->asOwner();
    }

    private function items(): array
    {
        return RackItem::query()->orderBy('position')->pluck('id')->all();
    }

    private function product(string $slug): Product
    {
        return Product::query()->where('slug', $slug)->firstOrFail();
    }

    #[Test]
    public function req_rck_01_req_rck_02_add_a_garment_by_uploading_an_image_and_link_it(): void
    {
        $navy = $this->piece('navy-wrap-set');

        Livewire::test(ManageRackItems::class)
            ->callAction('create', [
                'image_path' => [UploadedFile::fake()->createWithContent('navy.png', TestImages::garmentPng())],
                'name_en' => 'Navy Wrap Set',
                'name_ar' => 'طقم كحلي',
                'product_id' => $navy->product_id,
                'product_color_id' => $navy->id,
                'is_visible' => true,
            ])
            ->assertHasNoFormErrors()
            ->assertNotNotified('This image has no see-through background');

        $item = RackItem::query()->where('name_en', 'Navy Wrap Set')->firstOrFail();
        $this->assertSame($navy->product_id, $item->product_id);
        $this->assertSame($navy->id, $item->product_color_id);
        $this->assertSame(max($this->positions()), $item->position);      // hung at the end of the rail
        Storage::disk('public')->assertExists($item->image_path);
        $this->assertSame(760, getimagesizefromstring(Storage::disk('public')->get($item->image_path))[1]);
        // Tapping it opens the product on the navy colour.
        $this->get('/')->assertSee('href="'.e(url('/product/wrap-set?color=navy')).'"', false);
    }

    #[Test]
    public function picking_a_product_fills_an_empty_name_and_the_form_stays_short(): void
    {
        $product = $this->product('wrap-set');

        Livewire::test(ManageRackItems::class)
            ->mountAction('create')
            ->fillForm(['product_id' => $product->id])
            ->assertSchemaStateSet(['name_en' => $product->name_en, 'name_ar' => $product->name_ar])
            ->assertFormFieldDoesNotExist('size_adjust')
            ->assertFormFieldDoesNotExist('alt_en');
    }

    #[Test]
    public function req_rck_02_the_color_choice_shows_only_for_a_product_with_several_colors(): void
    {
        $page = Livewire::test(ManageRackItems::class)->mountAction('create');

        $page->assertFormFieldHidden('product_color_id')
            ->fillForm(['product_id' => $this->product('rosemary-hijab')->id])
            ->assertFormFieldHidden('product_color_id')
            ->fillForm(['product_id' => $this->product('wrap-set')->id])
            ->assertFormFieldVisible('product_color_id');
    }

    #[Test]
    public function req_rck_02_a_color_of_another_product_is_refused(): void
    {
        Livewire::test(ManageRackItems::class)
            ->callAction('create', [
                'image_path' => [UploadedFile::fake()->createWithContent('navy.png', TestImages::garmentPng())],
                'name_en' => 'Mixed Up',
                'product_id' => $this->product('wrap-set')->id,
                'product_color_id' => $this->piece('black-hijab')->id,
                'size_adjust' => 1,
                'is_visible' => true,
            ])
            ->assertHasFormErrors(['product_color_id']);

        $this->assertDatabaseMissing('rack_items', ['name_en' => 'Mixed Up']);
    }

    private function positions(): array
    {
        return RackItem::query()->pluck('position')->all();
    }

    #[Test]
    public function req_rck_12_the_owner_is_warned_when_the_image_has_no_transparent_background(): void
    {
        Livewire::test(ManageRackItems::class)
            ->callAction('create', [
                'image_path' => [UploadedFile::fake()->createWithContent('photo.jpg', TestImages::solidJpg())],
                'name_en' => 'Photo Set',
                'size_adjust' => 1,
                'is_visible' => true,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('This image has no see-through background');
    }

    #[Test]
    public function req_rck_03_tapping_a_garment_on_the_home_page_opens_its_product(): void
    {
        $item = RackItem::query()->with('product.colors.color')->whereNotNull('product_id')->firstOrFail();

        $this->get('/')->assertSee('href="'.e($item->url()).'"', false);
        // A garment with no product opens the shop.
        $item->update(['product_id' => null, 'product_color_id' => null]);
        $this->get('/')->assertSee('href="'.store_route('shop').'"', false);
    }

    #[Test]
    public function req_rck_04_replace_a_garments_image(): void
    {
        $item = RackItem::query()->first();
        $old = $item->image_path;

        Livewire::test(ManageRackItems::class)
            ->callAction(TestAction::make('edit')->table($item), [
                'new_image' => [UploadedFile::fake()->createWithContent('new.png', TestImages::garmentPng())],
            ])
            ->assertHasNoFormErrors();

        $this->assertNotSame($old, $item->refresh()->image_path);
        Storage::disk('public')->assertExists($item->image_path);
        Storage::disk('public')->assertMissing($old);
    }

    #[Test]
    public function req_rck_05_req_rck_06_rename_a_garment_and_change_its_product(): void
    {
        $item = RackItem::query()->whereNotNull('product_color_id')->firstOrFail();
        $navy = $this->piece('navy-wrap-set');

        Livewire::test(ManageRackItems::class)
            ->callAction(TestAction::make('edit')->table($item), ['name_en' => 'Weekend Set', 'product_id' => $navy->product_id, 'product_color_id' => $navy->id])
            ->assertHasNoFormErrors();

        $item->refresh();
        $this->assertSame('Weekend Set', $item->name_en);
        $this->assertSame($navy->product_id, $item->product_id);
        $this->assertSame($navy->id, $item->product_color_id);
        $this->get('/')->assertSee('Weekend Set');

        // Choosing a one-colour product forgets the old product's colour.
        Livewire::test(ManageRackItems::class)
            ->callAction(TestAction::make('edit')->table($item), ['product_id' => $this->product('rosemary-hijab')->id])
            ->assertHasNoFormErrors();
        $this->assertNull($item->refresh()->product_color_id);
    }

    #[Test]
    public function req_rck_08_reorder_with_move_up_and_move_down_buttons(): void
    {
        [$first, $second, $third] = $this->items();

        Livewire::test(ManageRackItems::class)
            ->callAction(TestAction::make('move_up')->table(RackItem::find($second)))
            ->callAction(TestAction::make('move_down')->table(RackItem::find($second)))
            ->callAction(TestAction::make('move_down')->table(RackItem::find($second)));

        $this->assertSame([$first, $third, $second], array_slice($this->items(), 0, 3));
    }

    #[Test]
    public function req_rck_09_hide_a_garment_without_deleting_it(): void
    {
        $item = RackItem::query()->where('name_en', 'Pink Belted Set')->firstOrFail();

        Livewire::test(ManageRackItems::class)->call('updateTableColumnState', 'is_visible', (string) $item->id, false);

        $this->assertModelExists($item);
        $this->get('/')->assertDontSee('Pink Belted Set');
    }

    #[Test]
    public function req_rck_10_req_cms_41_delete_a_garment_after_confirming(): void
    {
        $item = RackItem::query()->first();

        Livewire::test(ManageRackItems::class)
            ->mountAction(TestAction::make('delete')->table($item))
            ->assertActionMounted(TestAction::make('delete')->table($item))
            ->callMountedAction();

        $this->assertModelMissing($item);
        Storage::disk('public')->assertMissing($item->image_path);
    }

    #[Test]
    public function req_rck_11_the_live_preview_shows_the_rack_and_follows_changes(): void
    {
        $item = RackItem::query()->where('name_en', 'Pink Belted Set')->firstOrFail();

        Livewire::test(ManageRackItems::class)
            ->assertSeeHtml('class="ds-rack__items"')
            ->assertSeeHtml('<span class="ds-rack__tag">Pink Belted Set</span>')
            ->call('updateTableColumnState', 'is_visible', (string) $item->id, false)
            ->assertDontSeeHtml('<span class="ds-rack__tag">Pink Belted Set</span>');
    }

    #[Test]
    public function req_rck_16_adjust_a_garments_size_with_the_smaller_and_bigger_buttons(): void
    {
        $item = RackItem::query()->first();

        Livewire::test(ManageRackItems::class)
            ->callAction(TestAction::make('bigger')->table($item))
            ->callAction(TestAction::make('bigger')->table($item));
        $this->assertEquals(1.10, $item->refresh()->size_adjust);

        Livewire::test(ManageRackItems::class)
            ->callAction(TestAction::make('smaller')->table($item))
            ->callAction(TestAction::make('smaller')->table($item))
            ->callAction(TestAction::make('smaller')->table($item));
        $this->assertEquals(0.95, $item->refresh()->size_adjust);
        $this->get('/')->assertSee('[data-rack-garment="'.$item->id.'"]{--rack-adjust:0.95}', false);
    }
}
