<?php

namespace App\Domain\Delivery;

use App\Domain\Checkout\Cart;
use App\Domain\Checkout\DeliveryFeeCalculator;
use App\Models\DeliveryCity;
use App\Support\Money;

/** Each city has one fee, whatever is in the bag (REQ-CHK-10, D.8.2). */
class CityFlatFee implements DeliveryFeeCalculator
{
    public function feeFor(DeliveryCity $city, Cart $cart): Money
    {
        return Money::of($city->fee);
    }
}
