<?php

namespace Tests\Feature\Storefront;

use App\Domain\Media\ImageStore;
use App\Models\RackItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\DemoPieces;
use Tests\TestCase;

/** The storefront sends the smallest copy that fills the space, never only the large original. */
class ResponsiveImagesTest extends TestCase
{
    use DemoPieces, RefreshDatabase;

    #[Test]
    public function req_db_15_seeded_demo_images_get_the_same_copies_as_uploads(): void
    {
        $path = $this->piece('rosemary-hijab')->load('photos')->cardPhoto()->path;
        $disk = Storage::disk('public');

        foreach ([200, 400, 600, 800] as $width) {
            $this->assertTrue($disk->exists(ImageStore::variantPath($path, $width)), "{$width} px copy");
        }
        $this->assertTrue($disk->exists(ImageStore::variantPath(RackItem::query()->firstOrFail()->image_path, 380)));
    }

    #[Test]
    public function req_db_15_product_cards_list_their_resized_copies(): void
    {
        $html = $this->get('/shop')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#class="ds-card__image[^"]*" src="[^"]+" srcset="[^"]+-200\.webp 200w, [^"]+-400\.webp 400w, [^"]+-600\.webp 600w, [^"]+-800\.webp 800w, [^"]+\.webp \d+w" sizes="#', $html);
    }

    #[Test]
    public function req_db_15_the_product_page_and_rack_list_their_resized_copies(): void
    {
        $html = $this->get('/product/rosemary-hijab')->assertOk()->assertSee('data-gallery-srcset=', false)->getContent();

        // A phone of average sharpness gets the 600 px copy of the main photo, and the 200 px one for thumbnails.
        $this->assertMatchesRegularExpression('#class="ds-pdp__image" src="[^"]+" srcset="[^"]*-600\.webp 600w[^"]*" sizes="\(min-width: 48\.0625rem\) 600px, calc\(100vw - 2rem\)"#', $html);
        $this->assertMatchesRegularExpression('#class="ds-pdp__thumb-image" src="[^"]+-400\.webp" srcset="[^"]*-200\.webp 200w[^"]*" sizes="4\.75rem"#', $html);

        $this->get('/')->assertOk()->assertSee('-380.webp', false);
    }

    #[Test]
    public function req_db_15_bag_lines_use_the_smallest_copy(): void
    {
        $id = $this->piece('rosemary-hijab')->id;

        $image = $this->withHeader('Origin', config('app.url'))->postJson('/bag/check', ['ids' => [$id]])->assertOk()->json('products.0.image');

        $this->assertStringEndsWith('-400.webp', $image);
    }

    #[Test]
    public function req_db_15_an_image_without_copies_still_works(): void
    {
        Storage::disk('public')->put('products/plain.webp', Storage::disk('public')->get(ImageStore::variantPath(
            $this->piece('rosemary-hijab')->load('photos')->cardPhoto()->path, 400)));

        $this->assertMatchesRegularExpression('#^\S+/products/plain\.webp 400w$#', app(ImageStore::class)->srcset('products/plain.webp'));
        $this->assertSame('', app(ImageStore::class)->srcset(null));
        $this->assertSame('', app(ImageStore::class)->srcset('../.env'));

        Storage::disk('public')->delete('products/plain.webp');
    }
}
