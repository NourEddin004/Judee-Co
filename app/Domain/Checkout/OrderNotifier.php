<?php

namespace App\Domain\Checkout;

use App\Models\Order;

/** Tells the owner, and the customer when they gave an email address, about a new order. */
interface OrderNotifier
{
    public function orderPlaced(Order $order): void;
}
