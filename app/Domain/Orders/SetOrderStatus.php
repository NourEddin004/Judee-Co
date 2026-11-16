<?php

namespace App\Domain\Orders;

use App\Models\Order;

/**
 * Moves an order to any of the five statuses, from the order view (REQ-ORD-16, REQ-ORD-18). The change
 * is written to the activity log with who made it (REQ-USR-11).
 */
class SetOrderStatus
{
    public function __invoke(Order $order, OrderStatus $status): void
    {
        if ($order->status !== $status) {
            $order->update(['status' => $status]);
        }
    }
}
