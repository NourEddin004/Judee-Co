<?php

namespace App\Domain\Delivery;

use App\Domain\Checkout\Cart;
use App\Domain\Checkout\DeliveryFeeCalculator;
use App\Domain\Checkout\PriceCalculator;
use App\Models\DeliveryCity;
use App\Support\Money;

/**
 * Each city has one fee, whatever is in the bag (REQ-CHK-10, D.8.2), except that delivery inside
 * Jordan is free once the bag reaches store.free_delivery_from JD (2026-11-20).
 */
class CityFlatFee implements DeliveryFeeCalculator
{
    public function __construct(private readonly PriceCalculator $prices) {}

    public function feeFor(DeliveryCity $city, Cart $cart): Money
    {
        if ($city->isJordan() && self::reachesFreeDelivery($this->prices->subtotal($cart))) {
            return Money::zero();
        }

        return Money::of($city->fee);
    }

    public static function reachesFreeDelivery(Money $subtotal): bool
    {
        $from = config('store.free_delivery_from');

        return $from !== null && ! $subtotal->isLessThan(Money::of($from));
    }
}
