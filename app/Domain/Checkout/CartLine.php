<?php

namespace App\Domain\Checkout;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Size;

/** One bag line: one colour of a product, its size when it has sizes, and how many (REQ-DMO-27). */
final class CartLine
{
    public readonly Product $product;

    public function __construct(
        public readonly ProductColor $color,
        public readonly ?Size $size,
        public readonly int $quantity,
    ) {
        $this->product = $color->product;
    }
}
