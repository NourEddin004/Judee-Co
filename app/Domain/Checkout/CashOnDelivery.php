<?php

namespace App\Domain\Checkout;

use App\Models\Order;

/** The customer pays the courier in cash when the order arrives; nothing happens online. */
class CashOnDelivery implements PaymentMethod
{
    public function key(): string
    {
        return 'cash_on_delivery';
    }

    public function settle(Order $order): void {}
}
