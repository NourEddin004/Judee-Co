<?php

namespace App\Domain\Rack\Image;

use GdImage;

/** A garment image moving through the rack pipeline, plus the smaller copies made from it. */
final class RackImage
{
    /** @param  array<int, GdImage>  $variants  keyed by height in pixels */
    public function __construct(public GdImage $image, public array $variants = []) {}

    public function width(): int
    {
        return imagesx($this->image);
    }

    public function height(): int
    {
        return imagesy($this->image);
    }
}
