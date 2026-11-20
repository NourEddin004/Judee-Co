<?php

namespace App\Filament\Pages;

use App\Domain\Orders\OrderSearch;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

/** The admin home, laid out like the admin preview (REQ-ORD-26 to REQ-ORD-33). */
class Dashboard extends BaseDashboard
{
    public function getTitle(): string|Htmlable
    {
        return __('Dashboard');
    }

    public function getHeading(): string|Htmlable
    {
        return __('Hi :name, here\'s your store', ['name' => Filament::auth()->user()?->name]);
    }

    /** Statuses across the top; new orders take two thirds of the row below, the two cards one third. */
    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }

    protected function getHeaderActions(): array
    {
        return [
            self::findOrderAction(),
            Action::make('viewStore')->label(__('View store'))->color('gray')->size('sm')
                ->url(route('home'), shouldOpenInNewTab: true),
        ];
    }

    /** "Find order": the owner types the number a customer quotes and goes straight to that order (REQ-ORD-35). */
    public static function findOrderAction(): Action
    {
        return Action::make('findOrder')->label(__('Find order'))->icon('heroicon-o-magnifying-glass')->size('sm')
            ->modalHeading(__('Find an order'))
            ->modalDescription(__('Type the order number the customer gives you, for example 482915.'))
            ->modalSubmitActionLabel(__('Open order'))
            ->modalWidth('sm')
            ->schema([
                TextInput::make('number')->label(__('Order number'))->required()->autofocus()
                    ->maxLength(10)->placeholder(__('#482915'))->extraInputAttributes(['autocapitalize' => 'characters', 'autocomplete' => 'off']),
            ])
            ->action(function (array $data, Action $action) {
                $number = OrderSearch::normalizeNumber((string) $data['number']);
                $order = $number === null ? null : Order::query()->where('number', $number)->first();

                if ($order === null) {
                    Notification::make()->danger()->title(__('No order with this number'))
                        ->body(__('Check the 6 digits with the customer, then try again.'))->send();
                    $action->halt();

                    return;
                }

                $action->redirect(OrderResource::getUrl('view', ['record' => $order]));
            });
    }
}
