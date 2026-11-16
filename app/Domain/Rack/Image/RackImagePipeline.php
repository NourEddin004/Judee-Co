<?php

namespace App\Domain\Rack\Image;

use GdImage;

/** Fits an uploaded garment image to the rack, one small step after another (C.12). */
class RackImagePipeline
{
    /** @param  list<ImageStep>  $steps */
    public function __construct(private readonly array $steps) {}

    public function process(GdImage $image): RackImage
    {
        return array_reduce($this->steps, fn (RackImage $image, ImageStep $step) => $step->apply($image), new RackImage($image));
    }
}
