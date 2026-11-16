<?php

namespace App\Domain\Media;

use GdImage;

/** Small GD helpers shared by the image store and the rack pipeline. */
final class Gd
{
    /** A blank, fully transparent true-colour canvas. */
    public static function canvas(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        return $image;
    }

    /** Copies the image onto a new canvas of the given size, keeping transparency. */
    public static function resize(GdImage $image, int $width, int $height): GdImage
    {
        $resized = self::canvas($width, $height);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /** Scales down so neither side is larger than the limits; never scales up. */
    public static function fit(GdImage $image, int $maxWidth, int $maxHeight): GdImage
    {
        $ratio = min($maxWidth / imagesx($image), $maxHeight / imagesy($image), 1);

        return $ratio < 1
            ? self::resize($image, (int) round(imagesx($image) * $ratio), (int) round(imagesy($image) * $ratio))
            : $image;
    }

    /** GD alpha runs from 0 (opaque) to 127 (fully transparent). */
    public static function alphaAt(GdImage $image, int $x, int $y): int
    {
        return (imagecolorat($image, $x, $y) >> 24) & 0x7F;
    }

    public static function webp(GdImage $image, int $quality): string
    {
        imagesavealpha($image, true);
        ob_start();
        imagewebp($image, null, $quality);

        return (string) ob_get_clean();
    }
}
