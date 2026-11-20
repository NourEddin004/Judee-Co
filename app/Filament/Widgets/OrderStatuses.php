<?php

namespace App\Filament\Widgets;

use App\Domain\Orders\OrderFigures;
use App\Domain\Orders\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Widgets\Widget;

/** The top of the dashboard: one box per order status with its number of orders, each opening that list. */
class OrderStatuses extends Widget
{
    protected static ?int $sort = 1;

    /** Drawn with the page, so the figures show at once on a phone, without a second request. */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'admin.widgets.order-statuses';

    public static function canView(): bool
    {
        return OrderResource::canViewAny();
    }

    protected function getViewData(): array
    {
        $counts = app(OrderFigures::class)->countsByStatus();

        return [
            'statuses' => array_map(fn (OrderStatus $status) => [
                'status' => $status,
                'count' => $counts[$status->value],
                'url' => OrderResource::getUrl('index', ['tab' => $status->value]),
            ], OrderStatus::cases()),
        ];
    }
}
