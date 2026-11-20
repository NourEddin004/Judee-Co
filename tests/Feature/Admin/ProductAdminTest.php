<?php

namespace Tests\Feature\Admin;

use App\Domain\Catalog\TidyProductPhotos;
use App\Domain\Media\ImageStore;
use App\Filament\Resources\Colors\Pages\ManageColors;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductPhoto;
use App\Models\Shade;
use App\Models\Size;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\Support\TestImages;
use Tests\TestCase;

/**
 * Products in the admin, each holding its colors in one form (REQ-CMS-01 to REQ-CMS-13,
 * REQ-CMS-41 to REQ-CMS-46, REQ-DB-27, REQ-DB-37).
 */
class ProductAdminTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        foreach (ProductPhoto::query()->pluck('path') as $path) {
            Storage::disk('public')->put($path, 'image');      // the seeded photos, present on the fake disk
        }
        $this->asOwner();
    }

    private function product(string $slug): Product
    {
        return Product::withoutGlobalScopes()->where('slug', $slug)->firstOrFail();
    }

    private function colorOf(string $slug, string $color): ProductColor
    {
        return $this->product($slug)->colors()->whereRelation('color', 'name_en', $color)->firstOrFail();
    }

    private function colorId(string $name): int
    {
        return Color::query()->where('name_en', $name)->value('id');
    }

    /** The product's colors by name, in the owner's order. */
    private function colorNames(Product $product): array
    {
        return $product->colors()->with('color')->get()->map(fn (ProductColor $color) => $color->color->name_en)->all();
    }

    #[Test]
    public function req_cms_01_search_products_by_name(): void
    {
        Livewire::test(ListProducts::class)
            ->searchTable('Rosemary')
            ->assertCanSeeTableRecords([$this->product('rosemary-hijab')])
            ->assertCanNotSeeTableRecords([$this->product('wrap-set')]);
    }

    #[Test]
    public function req_cms_01_search_products_by_color(): void
    {
        // Navy is one color of the three sets; "Navy Blue" is one of the plain hijab's colors.
        $navy = Product::withoutGlobalScopes()->whereHas('colors.color', fn ($query) => $query->where('name_en', 'like', '%Navy%'))->get();
        $this->assertEqualsCanonicalizing(['wrap-set', 'belted-set', 'hooded-set', 'plain-hijab'], $navy->pluck('slug')->all());

        Livewire::test(ListProducts::class)
            ->searchTable('Navy')
            ->assertCanSeeTableRecords($navy)
            ->assertCanNotSeeTableRecords([$this->product('rosemary-hijab')]);
    }

    #[Test]
    public function req_cms_02_filter_products_by_category(): void
    {
        $sets = Category::query()->where('slug', 'sets')->firstOrFail();

        Livewire::test(ListProducts::class)
            ->filterTable('category', $sets->id)
            ->assertCanSeeTableRecords([$this->product('wrap-set')])
            ->assertCanNotSeeTableRecords([$this->product('rosemary-hijab')]);
    }

    #[Test]
    public function req_db_27_admin_lists_load_25_rows_per_page(): void
    {
        $hijabs = Category::query()->where('slug', 'hijabs')->value('id');
        foreach (range(1, 12) as $n) {
            Product::withoutGlobalScopes()->create(['category_id' => $hijabs, 'slug' => "extra-{$n}", 'name_en' => "Extra {$n}", 'price' => 10])
                ->colors()->create(['color_id' => $this->colorId('Black'), 'position' => 1]);
        }
        $this->assertGreaterThan(25, Product::withoutGlobalScopes()->count());

        Livewire::test(ListProducts::class)->assertCountTableRecords(Product::withoutGlobalScopes()->count())
            ->tap(fn ($page) => $this->assertCount(25, $page->instance()->getTableRecords()));
    }

    #[Test]
    public function req_cms_03_req_cms_10_req_cms_11_req_cms_43_create_a_product_with_two_colors_each_with_photos_and_stock(): void
    {
        $undo = Repeater::fake();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'Lilac Hijab',
                'name_ar' => 'حجاب ليلكي',
                'slug' => 'lilac-hijab',
                'category_id' => Category::query()->where('slug', 'hijabs')->value('id'),
                'price' => 18.5,
                'colors' => [
                    ['color_id' => $this->colorId('Violet Ice'), 'in_stock' => true, 'photos' => [
                        ['path' => [UploadedFile::fake()->image('front.jpg', 900, 1200)], 'is_main' => false, 'is_hover' => false],
                        ['path' => [UploadedFile::fake()->image('back.png', 900, 1200)], 'is_main' => true, 'is_hover' => false],
                    ]],
                    ['color_id' => $this->colorId('Black'), 'in_stock' => false, 'photos' => [
                        ['path' => [UploadedFile::fake()->image('black.webp', 900, 1200)], 'is_main' => true, 'is_hover' => false],
                    ]],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $undo();

        $product = $this->product('lilac-hijab');
        $this->assertSame('حجاب ليلكي', $product->name_ar);
        $this->assertEquals(18.5, $product->price);
        $this->assertSame(['Violet Ice', 'Black'], $this->colorNames($product));

        [$violet, $black] = $product->colors()->with('photos')->get()->all();
        $this->assertSame([true, false], [$violet->in_stock, $black->in_stock]);
        $this->assertCount(2, $violet->photos);
        $this->assertSame([false, true], $violet->photos->pluck('is_main')->all());
        $this->assertCount(1, $black->photos);
        foreach ([...$violet->photos, ...$black->photos] as $photo) {
            Storage::disk('public')->assertExists($photo->path);
        }
    }

    #[Test]
    public function req_cms_43_a_product_needs_at_least_one_color(): void
    {
        $undo = Repeater::fake();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'No Color', 'slug' => 'no-color', 'price' => 10,
                'category_id' => Category::query()->value('id'),
                'colors' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['colors' => 'min']);
        $undo();

        $this->assertNull(Product::withoutGlobalScopes()->where('slug', 'no-color')->first());
    }

    #[Test]
    public function req_cms_04_edit_name_category_price_description_and_sizes(): void
    {
        $product = $this->product('wrap-set');
        $sizes = Size::query()->whereIn('label', ['S', 'M'])->pluck('id')->all();

        Livewire::test(EditProduct::class, ['record' => $product->slug])
            ->fillForm([
                'name_en' => 'Midnight Wrap Set',
                'price' => 42,
                'description_en' => 'A new description.',
                'sizes' => $sizes,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('Midnight Wrap Set', $product->name_en);
        $this->assertEquals(42, $product->price);
        $this->assertSame('A new description.', $product->description_en);
        $this->assertEqualsCanonicalizing($sizes, $product->sizes()->pluck('sizes.id')->all());
        $this->assertSame(['Black', 'Navy', 'Chocolate Brown', 'Beige'], $this->colorNames($product));   // untouched
    }

    #[Test]
    public function req_cms_04_req_cms_44_add_a_color_and_remove_one_in_the_same_form(): void
    {
        $product = $this->product('wrap-set');
        $brown = $this->colorOf('wrap-set', 'Chocolate Brown')->load('photos');
        $undo = Repeater::fake();

        $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);
        $colors = $page->get('data.colors');
        unset($colors["record-{$brown->id}"]);
        $page->set('data.colors', $colors)                      // remove a color
            ->fillForm(['colors' => ['new' => ['color_id' => $this->colorId('Olive'), 'in_stock' => true, 'photos' => [
                ['path' => [UploadedFile::fake()->image('olive.jpg', 900, 1200)], 'is_main' => true, 'is_hover' => false],
            ]]]])                                                // and add one
            ->call('save')
            ->assertHasNoFormErrors();
        $undo();

        $this->assertSame(['Black', 'Navy', 'Beige', 'Olive'], $this->colorNames($product));
        $this->assertModelMissing($brown);
        foreach ($brown->photos as $photo) {
            Storage::disk('public')->assertMissing($photo->path);    // a removed color takes its photos with it
        }
        $this->assertCount(1, $this->colorOf('wrap-set', 'Olive')->photos);
    }

    #[Test]
    public function req_cms_41_removing_a_color_asks_first(): void
    {
        $product = $this->product('wrap-set');

        Livewire::test(EditProduct::class, ['record' => $product->slug])
            ->assertFormComponentExists('colors', fn (Repeater $colors) => $colors->getAction($colors->getDeleteActionName())->isConfirmationRequired());
    }

    #[Test]
    public function req_cms_45_reorder_colors_and_the_first_one_shows_first(): void
    {
        $product = $this->product('wrap-set');
        $undo = Repeater::fake();

        $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);
        $page->set('data.colors', array_reverse($page->get('data.colors'), true))->call('save')->assertHasNoFormErrors();
        $undo();

        $this->assertSame(['Beige', 'Chocolate Brown', 'Navy', 'Black'], $this->colorNames($product));
        $this->get('/product/wrap-set')->assertOk()->assertSee('<strong data-color-name>Beige</strong>', false);
    }

    #[Test]
    public function req_db_37_a_product_cannot_have_the_same_color_twice(): void
    {
        $product = $this->product('wrap-set');
        $black = $this->colorOf('wrap-set', 'Black');
        $undo = Repeater::fake();

        $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);
        $colors = $page->get('data.colors');
        $colors['again'] = ['color_id' => $black->color_id, 'in_stock' => true, 'photos' => []];
        $page->set('data.colors', $colors)->call('save')->assertHasFormErrors(['colors.again.color_id']);
        $this->assertContains('This color is already in the list.', $page->errors()->get('data.colors.again.color_id'));
        $undo();

        $this->assertSame(4, $product->colors()->count());
        $this->expectException(UniqueConstraintViolationException::class);
        ProductColor::query()->create(['product_id' => $product->id, 'color_id' => $black->color_id, 'position' => 9]);
    }

    #[Test]
    public function req_db_37_two_saved_colors_trading_places_get_a_message_not_an_error_page(): void
    {
        $product = $this->product('wrap-set');
        $black = $this->colorOf('wrap-set', 'Black');
        $navy = $this->colorOf('wrap-set', 'Navy');

        Livewire::test(EditProduct::class, ['record' => $product->slug])
            ->set("data.colors.record-{$black->id}.color_id", $navy->color_id)
            ->set("data.colors.record-{$navy->id}.color_id", $black->color_id)
            ->call('save')
            ->assertHasFormErrors(["colors.record-{$black->id}.color_id"]);

        $this->assertSame(['Black', 'Navy', 'Chocolate Brown', 'Beige'], $this->colorNames($product));
    }

    #[Test]
    public function req_cms_05_duplicate_a_product_with_its_colors_and_photos(): void
    {
        $product = $this->product('wrap-set');

        Livewire::test(ListProducts::class)->callAction(TestAction::make('duplicate')->table($product));

        $copy = $this->product('wrap-set-copy');
        $this->assertSame('Wrap Set (copy)', $copy->name_en);
        $this->assertFalse($copy->is_published);
        $this->assertEquals($product->price, $copy->price);
        $this->assertSame($product->sizes()->count(), $copy->sizes()->count());
        $this->assertSame($this->colorNames($product), $this->colorNames($copy));
        $original = $product->colors()->withCount('photos')->get();
        $copied = $copy->colors()->withCount('photos')->get();
        $this->assertSame($original->pluck('photos_count')->all(), $copied->pluck('photos_count')->all());
        $this->assertNotSame($original->first()->photos()->first()->path, $copied->first()->photos()->first()->path);
        $this->assertSame([null], $copied->pluck('legacy_slug')->unique()->values()->all());   // old demo addresses stay with the original
    }

    #[Test]
    public function req_cms_06_hide_a_product_without_deleting_it(): void
    {
        $product = $this->product('wrap-set');

        Livewire::test(ListProducts::class)->call('updateTableColumnState', 'is_published', (string) $product->getKey(), false);

        $this->assertModelExists($product);
        $this->assertFalse($product->refresh()->is_published);
        $this->get('/product/wrap-set')->assertNotFound();
        $this->get('/product/navy-wrap-set')->assertNotFound();     // its colors' old addresses too
    }

    #[Test]
    public function req_cms_07_req_cms_41_delete_a_product_after_confirming(): void
    {
        $product = $this->product('rosemary-hijab');
        $photo = ProductPhoto::query()->whereIn('product_color_id', $product->colors()->select('id'))->firstOrFail();

        Livewire::test(ListProducts::class)
            ->mountAction(TestAction::make('delete')->table($product))
            ->assertActionMounted(TestAction::make('delete')->table($product))   // the confirmation is open
            ->callMountedAction();

        $this->assertNull(Product::withoutGlobalScopes()->find($product->id));
        $this->assertSame(0, ProductColor::query()->where('product_id', $product->id)->count());
        Storage::disk('public')->assertMissing($photo->path);
    }

    #[Test]
    public function req_cms_42_past_orders_keep_the_details_of_deleted_products(): void
    {
        $product = $this->product('rosemary-hijab');
        $order = Order::query()->create([
            'number' => '100001', 'link_token' => str_repeat('a', 64), 'customer_name' => 'Lina', 'phone' => '+962 791234567',
            'delivery_city_id' => 1, 'city_name' => 'Amman', 'area' => 'Khalda', 'address' => 'Street 1',
            'subtotal' => 17, 'delivery_fee' => 2, 'total' => 19, 'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_color_id' => $product->colors()->value('id'),
            'product_name' => 'Rosemary Hijab', 'color_name' => 'Rosemary', 'unit_price' => 17, 'quantity' => 1,
        ]);

        Livewire::test(ListProducts::class)->callAction(TestAction::make('delete')->table($product));

        $item = $order->items()->first();
        $this->assertNull($item->product_id);
        $this->assertNull($item->product_color_id);
        $this->assertSame(['Rosemary Hijab', 'Rosemary'], [$item->product_name, $item->color_name]);
        $this->assertEquals(17, $item->unit_price);
    }

    #[Test]
    public function req_cms_08_mark_one_color_out_of_stock(): void
    {
        $product = $this->product('wrap-set');
        $navy = $this->colorOf('wrap-set', 'Navy');

        Livewire::test(EditProduct::class, ['record' => $product->slug])
            ->set("data.colors.record-{$navy->id}.in_stock", false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Black' => true, 'Navy' => false, 'Chocolate Brown' => true, 'Beige' => true],
            $product->colors()->with('color')->get()->mapWithKeys(fn (ProductColor $color) => [$color->color->name_en => $color->in_stock])->all());

        // The list can show the products with a color out of stock.
        Livewire::test(ListProducts::class)
            ->filterTable('stock', false)
            ->assertCanSeeTableRecords([$product])
            ->assertCanNotSeeTableRecords([$this->product('rosemary-hijab')]);
    }

    #[Test]
    public function req_cms_09_the_featured_switch_is_on_the_product_form(): void
    {
        // The three home page looks stay as they are: Site texts left the admin on 8 Nov 2026.
        Livewire::test(EditProduct::class, ['record' => 'olive-noir-hijab'])
            ->assertFormFieldExists('is_featured', fn ($field) => $field->getLabel() === 'Featured')
            ->assertFormFieldDoesNotExist('collections');
    }

    #[Test]
    public function req_cms_11_req_cms_12_one_main_and_one_separate_hover_photo_for_each_color(): void
    {
        $product = $this->product('wrap-set');
        $black = $this->colorOf('wrap-set', 'Black');
        $photos = $black->photos;
        $this->assertGreaterThanOrEqual(2, $photos->count());

        $photos[0]->update(['is_main' => true, 'is_hover' => true]);
        $photos[1]->update(['is_main' => true, 'is_hover' => true]);

        app(TidyProductPhotos::class)($product);

        $photos = $black->photos()->get();
        $this->assertSame(1, $photos->where('is_main', true)->count());
        $this->assertSame(1, $photos->where('is_hover', true)->count());
        $this->assertTrue($photos[0]->is_main);
        $this->assertTrue($photos[1]->is_hover);
        // The other colors keep their own main photo.
        $this->assertSame(4, ProductPhoto::query()->whereIn('product_color_id', $product->colors()->select('id'))->where('is_main', true)->count());
    }

    #[Test]
    public function req_cms_13_reorder_a_colors_photos(): void
    {
        $product = $this->product('wrap-set');
        $black = $this->colorOf('wrap-set', 'Black');
        [$first, $second] = $black->photos->all();
        $undo = Repeater::fake();

        $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);
        $path = "data.colors.record-{$black->id}.photos";
        $page->set($path, array_reverse($page->get($path), true))->call('save')->assertHasNoFormErrors();
        $undo();

        $this->assertSame([$second->id, $first->id], $black->photos()->pluck('id')->all());
    }

    #[Test]
    public function req_cms_46_set_the_swatch_of_a_color(): void
    {
        $navy = Color::query()->where('name_en', 'Navy')->firstOrFail();

        Livewire::test(ManageColors::class)
            ->callAction(TestAction::make('edit')->table($navy), data: ['swatch' => '#102040'])
            ->assertHasNoFormErrors();

        $this->assertSame('#102040', $navy->refresh()->swatch);
        $this->get('/product/wrap-set')->assertSee('fill="#102040"', false);

        // A color without its own swatch shows its family's.
        $navy->update(['swatch' => null]);
        $family = Shade::query()->find($navy->shade_id)->swatch;
        $this->get('/product/wrap-set')->assertSee('fill="'.$family.'"', false);

        Livewire::test(ManageColors::class)
            ->callAction(TestAction::make('edit')->table($navy), data: ['swatch' => 'navy'])
            ->assertHasFormErrors(['swatch']);
    }

    #[Test]
    public function req_prf_03_a_photo_in_the_form_previews_from_its_400_px_copy(): void
    {
        $path = $this->colorOf('wrap-set', 'Navy')->photos()->orderBy('position')->value('path');
        Storage::disk('public')->put($path, TestImages::solidJpg(1200, 1600));
        Storage::disk('public')->put(ImageStore::variantPath($path, 400), TestImages::solidJpg(400, 533));

        $form = Livewire::test(EditProduct::class, ['record' => 'wrap-set'])->instance()->form;
        $upload = collect($form->getFlatComponents(withActions: false))
            ->first(fn ($component) => $component instanceof FileUpload && in_array($path, (array) $component->getRawState(), true));

        // The preview downloads the small copy; the field still holds the full photo.
        $this->assertSame(Storage::disk('public')->url(ImageStore::variantPath($path, 400)), array_values($upload->getUploadedFiles())[0]['url']);
        $this->assertSame([$path], array_values($upload->getRawState()));
    }

    #[Test]
    public function req_sec_19_uploads_accept_only_jpg_png_webp_of_5_mb_or_less(): void
    {
        $undo = Repeater::fake();
        $base = [
            'name_en' => 'Test', 'slug' => 'test-upload', 'price' => 10,
            'category_id' => Category::query()->value('id'),
        ];

        foreach ([
            UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('anim.gif'),
            UploadedFile::fake()->image('huge.jpg')->size(5121),
        ] as $file) {
            Livewire::test(CreateProduct::class)
                ->fillForm([...$base, 'colors' => [[
                    'color_id' => $this->colorId('Black'), 'in_stock' => true,
                    'photos' => [['path' => [$file], 'is_main' => true, 'is_hover' => false]],
                ]]])
                ->call('create')
                ->assertHasFormErrors();
        }
        $undo();

        $this->assertNull(Product::withoutGlobalScopes()->where('slug', 'test-upload')->first());
    }
    #[Test]
    public function a_photo_picked_but_not_saved_yet_stays_on_screen_when_the_form_redraws(): void
    {
        $page = Livewire::test(CreateProduct::class);
        $colorKey = array_key_first($page->get('data.colors'));
        $page->call('mountAction', 'add', [], ['schemaComponent' => "form.colors.{$colorKey}.photos"])->callMountedAction();
        $photoKey = array_key_first($page->get("data.colors.{$colorKey}.photos"));
        $field = "data.colors.{$colorKey}.photos.{$photoKey}.path";
        $page->set($field, [UploadedFile::fake()->image('front.jpg', 900, 1200)]);

        // Adding another color redraws the form; the photo must still be there, with a preview address.
        $page->call('mountAction', 'add', [], ['schemaComponent' => 'form.colors'])->callMountedAction();
        $this->assertCount(2, $page->get('data.colors'));

        $files = $page->instance()->callSchemaComponentMethod("form.colors.{$colorKey}.photos.{$photoKey}.path", 'getUploadedFiles');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('preview-file', (string) collect($files)->first()['url'] ?? '');
    }
}
