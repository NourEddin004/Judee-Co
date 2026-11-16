<?php

namespace App\Domain\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

class EloquentOrderRepository implements OrderRepository
{
    /** A clash on a 6-character number is rare (1 in a billion per order); a few tries always find a free one. */
    private const NUMBER_TRIES = 5;

    public function __construct(private readonly ConnectionInterface $db) {}

    /** The order and all its items are saved together, or nothing is (REQ-DB-06). */
    public function saveWithItems(Order $order): Order
    {
        $items = $order->relationLoaded('items') ? $order->getRelation('items') : collect();

        for ($try = 1; ; $try++) {
            try {
                return $this->db->transaction(function () use ($order, $items) {
                    $order->number = OrderNumber::generate();      // REQ-DB-10, REQ-DMO-73
                    $order->link_token = OrderNumber::token();     // REQ-DB-32, REQ-SEC-41
                    $order->save();
                    // Fresh copies each try, so a rolled-back try leaves nothing marked as saved.
                    $order->items()->saveMany($items->map(fn (OrderItem $item) => $item->replicate()));

                    return $order->load('items');
                });
            } catch (UniqueConstraintViolationException $clash) {
                $order->exists = false;
                unset($order->id);

                if ($try >= self::NUMBER_TRIES) {
                    throw $clash;
                }
            }
        }
    }

    public function findByToken(string $token): ?Order
    {
        return strlen($token) === OrderNumber::TOKEN_LENGTH
            ? Order::query()->with('items')->where('link_token', $token)->first()
            : null;
    }
}
