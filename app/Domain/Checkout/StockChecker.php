<?php

namespace App\Domain\Checkout;

use App\Models\ProductColor;
use App\Models\Size;

/** Whether one colour of a product, in a size, can be ordered now (REQ-CHK-16). */
interface StockChecker
{
    public function isOrderable(ProductColor $color, ?Size $size): bool;
}
