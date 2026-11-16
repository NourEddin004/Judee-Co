<?php

namespace App\Domain\Checkout;

use App\Support\Money;

/** What the bag costs, from current prices (REQ-CHK-12). */
interface PriceCalculator
{
    public function subtotal(Cart $cart): Money;

    public function total(Cart $cart, Money $fee): Money;
}
