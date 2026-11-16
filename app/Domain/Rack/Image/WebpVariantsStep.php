<?php

namespace App\Domain\Rack\Image;

use App\Domain\Media\Gd;

/** Adds smaller copies for phones; the image store writes them as WebP files (REQ-DB-15). */
class WebpVariantsStep implements ImageStep
{
    /** @param  list<int>  $heights */
    public function __construct(private readonly array $heights) {}

    public function apply(RackImage $image): RackImage
    {
        $variants = $image->variants;

        foreach ($this->heights as $height) {
            if ($height < $image->height()) {
                $width = max(1, (int) round($image->width() * $height / $image->height()));
                $variants[$height] = Gd::resize($image->image, $width, $height);
            }
        }

        return new RackImage($image->image, $variants);
    }
}
