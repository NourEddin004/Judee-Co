<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductPhoto;
use Illuminate\Support\Collection;

/**
 * What product cards need besides the product rows, in two batch queries however many cards
 * there are (REQ-DB-19): each product's colors, for the dots, and the main and hover photos of
 * the color the card shows (D.5.6).
 */
class ProductCards
{
    /**
     * @param  Collection<int, Product>  $products
     * @param  list<int>  $matchingColorIds  colors an active color filter or search matches: a card shows its first one (REQ-DMO-97)
     */
    public function load(Collection $products, array $matchingColorIds = []): Collection
    {
        if ($products->isEmpty()) {
            return $products;
        }

        $colors = ProductColor::query()
            ->whereIn('product_id', $products->modelKeys())
            ->orderBy('position')->orderBy('id')
            ->get(['id', 'product_id', 'color_id', 'in_stock', 'position'])
            ->groupBy('product_id');

        $shown = [];
        foreach ($products as $product) {
            $own = $colors->get($product->id, collect())->values();
            $color = ($matchingColorIds === [] ? null : $own->first(fn (ProductColor $color) => in_array($color->color_id, $matchingColorIds, true)))
                ?? $own->first();

            $own->each->setRelation('product', $product);
            $product->setRelation('colors', $own);
            $product->setRelation('shownColor', $color);

            if ($color !== null) {
                $shown[$color->id] = $color;
            }
        }

        $photos = ProductPhoto::query()
            ->whereIn('product_color_id', array_keys($shown))
            ->where(fn ($query) => $query->where('is_main', true)->orWhere('is_hover', true))
            ->orderBy('position')
            ->get(['id', 'product_color_id', 'path', 'is_main', 'is_hover', 'position'])
            ->groupBy('product_color_id');

        foreach ($shown as $id => $color) {
            $color->setRelation('photos', $photos->get($id, collect())->values());
        }

        return $products;
    }
}
