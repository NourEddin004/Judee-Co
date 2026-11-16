<?php

namespace App\Domain\Rack;

use App\Domain\Media\ImageStore;
use App\Models\RackItem;
use Illuminate\Http\UploadedFile;

/** Swaps a rack item's image for a new one, fitted the same way (REQ-RCK-04). */
class ReplaceRackImageAction
{
    public function __construct(
        private readonly FitRackImage $fit,
        private readonly RackItemRepository $items,
        private readonly ImageStore $images,
    ) {}

    public function execute(RackItem $item, UploadedFile|string $file): RackItem
    {
        $image = ($this->fit)($file);
        $old = $item->image_path;

        $item->image_path = $image['path'];
        $item->hadTransparentBackground = $image['transparent'];
        $this->items->save($item);
        $this->images->delete($old);

        return $item;
    }
}
