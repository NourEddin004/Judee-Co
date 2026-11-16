<?php

namespace App\Domain\Checkout;

use App\Models\DeliveryCity;
use App\Support\Money;

/** The delivery fee for a bag sent to a city (REQ-CHK-10, REQ-CHK-13). */
interface DeliveryFeeCalculator
{
    public function feeFor(DeliveryCity $city, Cart $cart): Money;
}
