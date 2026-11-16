<?php

namespace App\Domain\Rack\Image;

use App\Domain\Media\Gd;

/** Cuts away the empty see-through space around the garment (REQ-RCK-13). */
class TrimTransparentStep implements ImageStep
{
    /** Pixels at least this see-through (out of 127) count as empty. */
    private const EMPTY_ALPHA = 120;

    public function apply(RackImage $image): RackImage
    {
        $width = $image->width();
        $height = $image->height();
        $filledRow = fn (int $y) => $this->any($width, fn (int $x) => $this->filled($image, $x, $y));

        for ($top = 0; $top < $height && ! $filledRow($top); $top++);

        if ($top === $height) {
            return $image;                       // nothing but empty space: leave it for the owner to see
        }

        for ($bottom = $height - 1; $bottom > $top && ! $filledRow($bottom); $bottom--);

        $filledColumn = fn (int $x) => $this->any($bottom - $top + 1, fn (int $i) => $this->filled($image, $x, $top + $i));

        for ($left = 0; $left < $width && ! $filledColumn($left); $left++);
        for ($right = $width - 1; $right > $left && ! $filledColumn($right); $right--);

        if ($left === 0 && $top === 0 && $right === $width - 1 && $bottom === $height - 1) {
            return $image;
        }

        $trimmed = Gd::canvas($right - $left + 1, $bottom - $top + 1);
        imagecopy($trimmed, $image->image, 0, 0, $left, $top, $right - $left + 1, $bottom - $top + 1);

        return new RackImage($trimmed, $image->variants);
    }

    private function filled(RackImage $image, int $x, int $y): bool
    {
        return Gd::alphaAt($image->image, $x, $y) < self::EMPTY_ALPHA;
    }

    private function any(int $count, \Closure $test): bool
    {
        for ($i = 0; $i < $count; $i++) {
            if ($test($i)) {
                return true;
            }
        }

        return false;
    }
}
