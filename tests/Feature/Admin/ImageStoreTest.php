<?php

namespace Tests\Feature\Admin;

use App\Domain\Media\ImageStore;
use App\Domain\Media\InvalidImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\TestImages;
use Tests\TestCase;

/** How uploaded images are stored (REQ-SEC-20, REQ-SEC-31, REQ-SEC-32, REQ-DB-15). */
class ImageStoreTest extends TestCase
{
    use RefreshDatabase;

    private ImageStore $images;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->images = app(ImageStore::class);
    }

    #[Test]
    public function req_sec_31_every_image_is_re_encoded_dropping_hidden_code(): void
    {
        $payload = '<?php system($_GET["c"]); ?>';
        $file = UploadedFile::fake()->createWithContent('photo.jpg', TestImages::solidJpg().$payload);

        $path = $this->images->store($file, 'products');

        $stored = Storage::disk('public')->get($path);
        $this->assertStringStartsWith('RIFF', $stored);           // a fresh WebP file
        $this->assertStringContainsString('WEBP', substr($stored, 0, 16));
        $this->assertStringNotContainsString('<?php', $stored);
    }

    #[Test]
    public function req_sec_31_a_file_that_is_not_an_image_is_refused(): void
    {
        $this->expectException(InvalidImage::class);

        $this->images->store(UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo 1;'), 'products');
    }

    #[Test]
    public function req_sec_32_every_image_gets_a_new_random_name(): void
    {
        $first = $this->images->store(UploadedFile::fake()->createWithContent('my-photo.jpg', TestImages::solidJpg()), 'products');
        $second = $this->images->store(UploadedFile::fake()->createWithContent('my-photo.jpg', TestImages::solidJpg()), 'products');

        $this->assertMatchesRegularExpression('#^products/[A-Za-z0-9]{40}\.webp$#', $first);
        $this->assertStringNotContainsString('my-photo', $first);
        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function req_db_15_resized_webp_copies_are_made(): void
    {
        $path = $this->images->store(UploadedFile::fake()->createWithContent('big.jpg', TestImages::solidJpg(2000, 2500)), 'products');

        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(1280, $width);       // largest side 1600 px
        foreach ([200, 400, 600, 800] as $size) {
            $copy = ImageStore::variantPath($path, $size);
            Storage::disk('public')->assertExists($copy);
            $this->assertSame($size, getimagesizefromstring(Storage::disk('public')->get($copy))[0]);
        }

        $this->images->delete($path);
        Storage::disk('public')->assertMissing([$path, ...array_map(fn ($size) => ImageStore::variantPath($path, $size), [200, 400, 600, 800])]);
    }

    #[Test]
    public function req_sec_20_images_go_only_into_the_image_folders_which_never_run_code(): void
    {
        $this->assertFalse(ImageStore::isSafePath('../.env'));
        $this->assertFalse(ImageStore::isSafePath('products/../../.env'));
        $this->assertFalse(ImageStore::isSafePath('products/shell.php'));
        $this->assertTrue(ImageStore::isSafePath('products/'.str_repeat('a', 40).'.webp'));

        $nginx = file_get_contents(base_path('deploy/nginx/jude.conf'));
        $this->assertMatchesRegularExpression('#location \^~ /storage/ \{\s*location ~ \\\\\.php\$ \{ return 404; \}#', $nginx);
    }
}
