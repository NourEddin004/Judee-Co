<?php

namespace App\Domain\Rack\Image;

use GdImage;

interface TransparencyChecker
{
    public function hasTransparentBackground(GdImage $image): bool;
}
