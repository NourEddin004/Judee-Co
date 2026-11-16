<?php

namespace Tests\Feature\Admin;

use App\Domain\Media\ImageStore;
use App\Domain\Rack\ReplaceRackImageAction;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\RackItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\TestImages;
use Tests\TestCase;

/** A replaced or deleted image leaves nothing behind on the disk (REQ-CST-09). The logo: req_cms_29 in DeliveryAndSettingsAdminTest. */
class ImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @return list<string> the image and every resized copy on the disk */
    private function filesOf(string $path): array
    {
        $base = Str::beforeLast($path, '.');

        return array_values(array_filter(Storage::disk('public')->allFiles(dirname($path)), fn ($file) => $file === $path || str_starts_with($file, "{$base}-")));
    }

    private function photo(): string
    {
        return app(ImageStore::class)->store(UploadedFile::fake()->createWithContent('photo.jpg', TestImages::solidJpg(1800, 2200)), 'products');
    }

    #[Test]
    public function req_cst_09_a_replaced_product_photo_is_deleted_with_its_copies(): void
    {
        $color = ProductColor::query()->firstOrFail();
        $photo = $color->photos()->create(['path' => $old = $this->photo(), 'position' => 99]);
        $this->assertCount(5, $this->filesOf($old));   // the photo, plus the 200, 400, 600 and 800 px copies

        $photo->update(['path' => $new = $this->photo()]);

        $this->assertSame([], $this->filesOf($old));
        $this->assertCount(5, $this->filesOf($new));
    }

    #[Test]
    public function req_cst_09_a_deleted_product_takes_its_photo_files_with_it(): void
    {
        $product = Product::withoutGlobalScopes()->has('colors', '>', 1)->firstOrFail();
        [$first, $second] = $product->colors()->take(2)->get()->all();
        $paths = [$this->photo(), $this->photo()];
        $first->photos()->create(['path' => $paths[0], 'position' => 90]);
        $second->photos()->create(['path' => $paths[1], 'position' => 91]);

        $product->delete();

        foreach ($paths as $path) {
            $this->assertSame([], $this->filesOf($path));
        }
        $this->assertDatabaseMissing('product_colors', ['product_id' => $product->id]);
    }

    #[Test]
    public function req_cst_09_req_cms_44_a_removed_color_takes_its_photo_files_with_it(): void
    {
        $color = ProductColor::query()->firstOrFail();
        $color->photos()->create(['path' => $path = $this->photo(), 'position' => 90]);
        $other = ProductColor::query()->whereKeyNot($color->id)->firstOrFail();
        $other->photos()->create(['path' => $kept = $this->photo(), 'position' => 90]);

        $color->delete();

        $this->assertSame([], $this->filesOf($path));
        $this->assertCount(5, $this->filesOf($kept));    // another color's photos stay
    }

    #[Test]
    public function req_cst_09_a_replaced_rack_image_is_deleted_with_its_copies(): void
    {
        $item = RackItem::query()->firstOrFail();
        app(ReplaceRackImageAction::class)->execute($item, UploadedFile::fake()->createWithContent('a.png', TestImages::garmentPng()));
        $old = $item->refresh()->image_path;
        $this->assertNotSame([], $this->filesOf($old));

        app(ReplaceRackImageAction::class)->execute($item, UploadedFile::fake()->createWithContent('b.png', TestImages::garmentPng()));

        $this->assertSame([], $this->filesOf($old));
        $this->assertNotSame([], $this->filesOf($item->refresh()->image_path));
    }
}
