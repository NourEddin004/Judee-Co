<?php

namespace App\Filament\Widgets;

use App\Domain\Orders\OrderFigures;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * New orders on the dashboard. Once the owner moves an order to another status it leaves this
 * list and is found under that status on the Orders page.
 */
class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    /** Drawn with the page, so the figures show at once on a phone, without a second request. */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    public static function canView(): bool
    {
        return OrderResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Latest new orders'))
            ->query(fn () => OrderResource::withRowData(app(OrderFigures::class)->latestNew()))
            ->paginated(false)
            ->columns(OrderResource::rowColumns())
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('all')->label(__('All orders'))->color('gray')->size('sm')->url(OrderResource::getUrl()),
            ])
            ->emptyStateHeading(__('No new orders'))
            ->emptyStateDescription(__('New orders show up here as soon as a customer places one.'));
    }
}
