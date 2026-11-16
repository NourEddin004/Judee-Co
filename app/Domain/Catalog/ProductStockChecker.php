<?php

namespace App\Domain\Catalog;

use App\Domain\Checkout\StockChecker;
use App\Models\ProductColor;
use App\Models\Size;

/**
 * A piece can be ordered when its product is on the site and priced and its colour is in stock.
 * A product with sizes needs one of its own sizes; a product without sizes takes none (REQ-CHK-16, REQ-STR-14).
 */
class ProductStockChecker implements StockChecker
{
    public function isOrderable(ProductColor $color, ?Size $size): bool
    {
        $product = $color->product;

        if ($product === null || ! $product->is_published || ! $color->in_stock || $product->price === null) {
            return false;
        }

        $sizes = $product->sizes;

        return $sizes->isEmpty() ? $size === null : $size !== null && $sizes->contains('id', $size->id);
    }
}
