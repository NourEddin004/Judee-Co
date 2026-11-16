<?php

namespace App\Domain\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * The dashboard's order figures, each worked out inside the database with one counting query,
 * never by loading the orders (REQ-ORD-26 to 30, REQ-DB-28). Times are Jordan time (REQ-DB-11).
 */
class OrderFigures
{
    public function waitingForConfirmation(): int
    {
        return Order::query()->where('status', OrderStatus::New)->count();
    }

    /** @return array<string, int> every status, in order, with its number of orders (REQ-ORD-29) */
    public function countsByStatus(): array
    {
        $counts = Order::query()->toBase()->selectRaw('status, count(*) as orders')->groupBy('status')->pluck('orders', 'status');

        return collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])->all();
    }

    /**
     * @return Builder<Order> the most recent orders still waiting for the owner. Once an order is
     *                        moved to another status it leaves the dashboard and is found under that status.
     */
    public function latestNew(int $count = 10): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::New)
            ->select(['id', 'number', 'customer_name', 'city_name', 'status', 'total', 'placed_at'])
            ->latest('placed_at')
            ->latest('id')
            ->limit($count);
    }
}
