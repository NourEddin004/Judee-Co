<?php

namespace Tests\Support;

use App\Domain\Media\Gd;

/** Small garment pictures drawn with GD for the image tests. */
final class TestImages
{
    /**
     * A garment on a see-through background: a hook at (hookX, top) above a body, with empty
     * space all around, so trimming, scaling and hook alignment all have work to do.
     */
    public static function garmentPng(int $width = 900, int $height = 1300, int $hookX = 300, int $top = 120): string
    {
        $image = Gd::canvas($width, $height);
        imagealphablending($image, true);
        $wine = imagecolorallocate($image, 140, 68, 89);
        imagefilledrectangle($image, $hookX - 6, $top, $hookX + 6, $top + 60, $wine);            // hook
        imagefilledrectangle($image, $hookX - 140, $top + 60, $hookX + 260, $height - 150, $wine);  // garment, wider on the right

        return self::png($image);
    }

    /** A photo with a solid background, like a JPG straight from a phone. */
    public static function solidJpg(int $width = 800, int $height = 1000): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 250, 250, 250));
        imagefilledrectangle($image, 200, 100, 600, 900, imagecolorallocate($image, 30, 30, 30));
        ob_start();
        imagejpeg($image, null, 90);

        return (string) ob_get_clean();
    }

    private static function png(\GdImage $image): string
    {
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
