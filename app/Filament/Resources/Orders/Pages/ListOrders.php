<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Orders\OrderFigures;
use App\Domain\Orders\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** @var array<string, int>|null orders per status, counted once per request */
    private ?array $counts = null;

    public function getSubheading(): string|Htmlable|null
    {
        return __('Cash on delivery');
    }

    /** All, then one chip per status with its number of orders, as in the admin preview (REQ-ORD-04). */
    public function getTabs(): array
    {
        $this->counts ??= app(OrderFigures::class)->countsByStatus();

        $tabs = ['all' => Tab::make(__('All'))->badge(array_sum($this->counts))];

        foreach (OrderStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->label())
                ->badge($this->counts[$status->value])
                ->badgeColor($status->color())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return $tabs;
    }
}
