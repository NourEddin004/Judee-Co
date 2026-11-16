<?php

namespace App\Filament\Widgets;

use App\Domain\Orders\OrderFigures;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\RackItems\RackItemResource;
use App\Models\Product;
use App\Models\ProductColor;
use Filament\Widgets\Widget;

/** Two cards, one above the other: new orders waiting, and products on the site. */
class StoreSummary extends Widget
{
    protected static ?int $sort = 3;

    /** Drawn with the page, so the figures show at once on a phone, without a second request. */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected string $view = 'admin.widgets.store-summary';

    protected function getViewData(): array
    {
        // Counting queries only (REQ-DB-28); "on the site" means published (REQ-DB-31).
        $soldOut = ProductColor::query()->where('in_stock', false)->whereHas('product')->count();

        return [
            'newOrders' => app(OrderFigures::class)->waitingForConfirmation(),
            'newOrdersUrl' => OrderResource::canViewAny() ? OrderResource::getUrl('index', ['activeTab' => 'new']) : null,
            'products' => Product::query()->count(),
            'productsNote' => $soldOut > 0 ? trans_choice(':count color out of stock|:count colors out of stock', $soldOut) : __('All in stock'),
            'productsUrl' => ProductResource::canViewAny() ? ProductResource::getUrl() : null,
            'addProduct' => ProductResource::canCreate() ? ProductResource::getUrl('create') : null,
            'editRack' => RackItemResource::canAccess() ? RackItemResource::getUrl() : null,
        ];
    }
}
