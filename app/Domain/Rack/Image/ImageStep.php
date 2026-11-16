<?php

namespace App\Domain\Rack\Image;

/** One step of the rack pipeline. New steps are new classes; the pipeline never changes (C.12, C.13). */
interface ImageStep
{
    public function apply(RackImage $image): RackImage;
}
