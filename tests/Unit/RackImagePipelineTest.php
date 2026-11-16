<?php

namespace Tests\Unit;

use App\Domain\Media\Gd;
use App\Domain\Media\ImageStore;
use App\Domain\Rack\Image\AlignHookStep;
use App\Domain\Rack\Image\AlphaChannelChecker;
use App\Domain\Rack\Image\RackImage;
use App\Domain\Rack\Image\RackImagePipeline;
use App\Domain\Rack\Image\ReencodeStep;
use App\Domain\Rack\Image\ScaleToRackHeightStep;
use App\Domain\Rack\Image\TrimTransparentStep;
use App\Domain\Rack\Image\WebpVariantsStep;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestImages;

/** The hero rack image pipeline (C.12, REQ-RCK-12 to REQ-RCK-15). */
class RackImagePipelineTest extends TestCase
{
    private function decode(string $contents): \GdImage
    {
        return (new ImageStore($this->createStub(Filesystem::class)))->decode($contents);
    }

    private function pipeline(): RackImagePipeline
    {
        return new RackImagePipeline([
            new ReencodeStep(760), new TrimTransparentStep, new ScaleToRackHeightStep(760), new AlignHookStep, new WebpVariantsStep([380]),
        ]);
    }

    #[Test]
    public function req_rck_12_a_see_through_background_is_recognised(): void
    {
        $checker = new AlphaChannelChecker;

        $this->assertTrue($checker->hasTransparentBackground($this->decode(TestImages::garmentPng())));
        $this->assertFalse($checker->hasTransparentBackground($this->decode(TestImages::solidJpg())));
    }

    #[Test]
    public function req_rck_13_empty_transparent_space_is_trimmed(): void
    {
        $trimmed = (new TrimTransparentStep)->apply(new RackImage($this->decode(TestImages::garmentPng(900, 1300, 300, 120))));

        // Garment spans x 160..560 and y 120..1150 in the 900 x 1300 source.
        $this->assertSame(401, $trimmed->width());
        $this->assertSame(1031, $trimmed->height());
        $this->assertLessThan(127, Gd::alphaAt($trimmed->image, (int) ($trimmed->width() * 140 / 400), 0));   // the hook now touches the top edge
    }

    #[Test]
    public function req_rck_14_every_garment_is_scaled_to_the_rack_height(): void
    {
        foreach ([[900, 1300], [500, 600], [1600, 2400]] as [$width, $height]) {
            $fitted = $this->pipeline()->process($this->decode(TestImages::garmentPng($width, $height, (int) ($width / 3), 40)));

            $this->assertSame(760, $fitted->height());
        }
    }

    #[Test]
    public function req_rck_15_the_hanger_hook_sits_at_the_centre_like_the_other_garments(): void
    {
        $fitted = $this->pipeline()->process($this->decode(TestImages::garmentPng()));

        $hook = (new AlignHookStep)->hookCentre($fitted);
        $this->assertEqualsWithDelta($fitted->width() / 2, $hook, 2);
    }

    #[Test]
    public function req_db_15_rack_images_get_a_smaller_webp_copy(): void
    {
        $fitted = $this->pipeline()->process($this->decode(TestImages::garmentPng()));

        $this->assertArrayHasKey(380, $fitted->variants);
        $this->assertSame(380, imagesy($fitted->variants[380]));
    }
}
