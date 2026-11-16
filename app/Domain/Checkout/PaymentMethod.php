<?php

namespace App\Domain\Checkout;

use App\Models\Order;

/** How the customer pays. Cash on delivery today; a card method later is a new class (C.13). */
interface PaymentMethod
{
    public function key(): string;

    public function settle(Order $order): void;
}
