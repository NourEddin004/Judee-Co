<?php

namespace App\Domain\Catalog;

use App\Models\Product;

/**
 * After the photos are saved, for each color of the product: exactly one main photo (the first
 * one ticked, else the first photo) and at most one hover photo that is not the main one
 * (REQ-CMS-11, REQ-CMS-12).
 */
class TidyProductPhotos
{
    public function __invoke(Product $product): void
    {
        foreach ($product->colors()->with('photos')->get() as $color) {
            $photos = $color->photos;

            if ($photos->isEmpty()) {
                continue;
            }

            $main = $photos->firstWhere('is_main', true) ?? $photos->first();
            $hover = $photos->first(fn ($photo) => $photo->is_hover && ! $photo->is($main));

            foreach ($photos as $photo) {
                $photo->is_main = $photo->is($main);
                $photo->is_hover = $hover !== null && $photo->is($hover);

                if ($photo->isDirty()) {
                    $photo->save();
                }
            }
        }
    }
}
