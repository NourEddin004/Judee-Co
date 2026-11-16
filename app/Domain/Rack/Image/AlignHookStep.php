<?php

namespace App\Domain\Rack\Image;

use App\Domain\Media\Gd;

/**
 * The hanger hook is the top of the garment. This step finds the middle of the hook and
 * widens the canvas so the hook sits at the horizontal centre, the point where the home
 * page hangs every garment on the rail (REQ-RCK-15).
 */
class AlignHookStep implements ImageStep
{
    /** Pixels this opaque or more belong to the garment. */
    private const OPAQUE_ALPHA = 64;

    /** The hook is looked for in the top 3% of the image. */
    private const HOOK_SHARE = 0.03;

    public function apply(RackImage $image): RackImage
    {
        $hook = $this->hookCentre($image);

        if ($hook === null) {
            return $image;
        }

        $width = $image->width();
        $half = max($hook, $width - $hook);
        $newWidth = (int) ceil($half * 2);

        if ($newWidth - $width < 2) {
            return $image;
        }

        $canvas = Gd::canvas($newWidth, $image->height());
        imagecopy($canvas, $image->image, (int) round($half - $hook), 0, 0, 0, $width, $image->height());

        return new RackImage($canvas, $image->variants);
    }

    public function hookCentre(RackImage $image): ?float
    {
        $width = $image->width();
        $height = $image->height();
        $band = max(2, (int) round($height * self::HOOK_SHARE));
        $top = null;
        $left = $width;
        $right = -1;

        for ($y = 0; $y < $height; $y++) {
            if ($top !== null && $y >= $top + $band) {
                break;
            }

            for ($x = 0; $x < $width; $x++) {
                if (Gd::alphaAt($image->image, $x, $y) <= self::OPAQUE_ALPHA) {
                    $top ??= $y;
                    $left = min($left, $x);
                    $right = max($right, $x);
                }
            }
        }

        return $right < 0 ? null : ($left + $right + 1) / 2;
    }
}
