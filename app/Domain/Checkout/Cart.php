<?php

namespace App\Domain\Checkout;

/** The bag the customer sends at checkout, joined with the current products. */
final class Cart
{
    /** @param  list<CartLine>  $lines */
    public function __construct(public readonly array $lines) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }
}
