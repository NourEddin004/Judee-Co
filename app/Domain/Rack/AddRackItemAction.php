<?php

namespace App\Domain\Rack;

use App\Models\RackItem;

/**
 * Adds a garment to the hero rack (REQ-RCK-01, C.12). The image is fitted first, then the
 * item is saved at the end of the rail. The model writes the activity log entry itself.
 */
class AddRackItemAction
{
    public function __construct(
        private readonly FitRackImage $fit,
        private readonly RackItemRepository $items,
    ) {}

    public function execute(RackItemData $data): RackItem
    {
        $image = ($this->fit)($data->image);

        $item = new RackItem([
            'product_id' => $data->productId,
            'product_color_id' => $data->productId === null ? null : $data->productColorId,
            'name_en' => $data->nameEn,
            'name_ar' => $data->nameAr,
            'alt_en' => $data->altEn ?: $data->nameEn,
            'alt_ar' => $data->altAr ?: $data->nameAr,
            'image_path' => $image['path'],
            'position' => $this->items->nextPosition(),
            'is_visible' => $data->isVisible,
            'size_adjust' => $data->sizeAdjust,
        ]);
        $item->hadTransparentBackground = $image['transparent'];

        return $this->items->save($item);
    }
}
