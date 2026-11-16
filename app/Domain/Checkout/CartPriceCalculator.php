<?php

namespace App\Domain\Checkout;

use App\Support\Money;

/** Unit price times quantity for each line, plus the delivery fee, in exact JOD (REQ-DB-08, REQ-DMO-45). */
class CartPriceCalculator implements PriceCalculator
{
    public function subtotal(Cart $cart): Money
    {
        return array_reduce(
            $cart->lines,
            fn (Money $sum, CartLine $line) => $sum->plus(Money::of($line->product->price)->times($line->quantity)),
            Money::zero(),
        );
    }

    public function total(Cart $cart, Money $fee): Money
    {
        return $this->subtotal($cart)->plus($fee);
    }
}
