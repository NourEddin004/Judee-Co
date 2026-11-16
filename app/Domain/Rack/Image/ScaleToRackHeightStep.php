<?php

namespace App\Domain\Rack\Image;

use App\Domain\Media\Gd;

/** Every garment is scaled to the same height as the others on the rail (REQ-RCK-14). */
class ScaleToRackHeightStep implements ImageStep
{
    public function __construct(private readonly int $height) {}

    public function apply(RackImage $image): RackImage
    {
        if ($image->height() === $this->height) {
            return $image;
        }

        $width = max(1, (int) round($image->width() * $this->height / $image->height()));

        return new RackImage(Gd::resize($image->image, $width, $this->height), $image->variants);
    }
}
