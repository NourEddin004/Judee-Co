<?php

namespace App\Domain\Orders;

use App\Models\Order;

interface OrderRepository
{
    /** Saves a new order with its items in one transaction and gives it its number and link token. */
    public function saveWithItems(Order $order): Order;

    public function findByToken(string $token): ?Order;
}
