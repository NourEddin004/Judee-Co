<?php

namespace App\Domain\Rack\Image;

use App\Domain\Media\Gd;
use GdImage;

/**
 * A garment photo has a transparent background when nearly all of its edge pixels are
 * see-through. A JPG, or a PNG with a white background, fails and the owner is warned (REQ-RCK-12).
 */
class AlphaChannelChecker implements TransparencyChecker
{
    /** GD alpha of 120 or more out of 127 counts as see-through. */
    private const TRANSPARENT_ALPHA = 120;

    private const MIN_SHARE = 0.9;

    private const SAMPLES_PER_EDGE = 200;

    public function hasTransparentBackground(GdImage $image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $points = [];

        for ($i = 0; $i < self::SAMPLES_PER_EDGE; $i++) {
            $x = (int) floor($i * ($width - 1) / (self::SAMPLES_PER_EDGE - 1));
            $y = (int) floor($i * ($height - 1) / (self::SAMPLES_PER_EDGE - 1));
            array_push($points, [$x, 0], [$x, $height - 1], [0, $y], [$width - 1, $y]);
        }

        $transparent = count(array_filter($points, fn ($p) => Gd::alphaAt($image, $p[0], $p[1]) >= self::TRANSPARENT_ALPHA));

        return $transparent / count($points) >= self::MIN_SHARE;
    }
}
