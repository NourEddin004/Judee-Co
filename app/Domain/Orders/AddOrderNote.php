<?php

namespace App\Domain\Orders;

use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;

/** A note the owner adds to an order; customers never see it (REQ-ORD-20). */
class AddOrderNote
{
    public const MAX_LENGTH = 1000;

    public function __invoke(Order $order, User $author, string $body): OrderNote
    {
        return $order->notes()->create(['user_id' => $author->id, 'body' => trim($body)]);
    }
}
