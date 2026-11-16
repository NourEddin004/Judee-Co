<?php

namespace App\Domain\Rack\Image;

use App\Domain\Media\Gd;

/**
 * Copies the pixels onto a clean canvas (REQ-SEC-31) and brings very large photos down
 * to twice the rack height first, so the later steps stay fast.
 */
class ReencodeStep implements ImageStep
{
    public function __construct(private readonly int $rackHeight) {}

    public function apply(RackImage $image): RackImage
    {
        $limit = $this->rackHeight * 2;
        $source = Gd::fit($image->image, $limit, $limit);
        $clean = Gd::canvas(imagesx($source), imagesy($source));
        imagecopy($clean, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));

        return new RackImage($clean, $image->variants);
    }
}
