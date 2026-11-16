<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Orders\AddOrderNote;
use App\Domain\Orders\OrderStatus;
use App\Domain\Orders\SetOrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Support\TranslatedLabels;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/** Orders for the owner: the list by status, the order sheet, notes and the packing slip (SRS 3.4). */
class OrderResource extends Resource
{
    use TranslatedLabels;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'number';

    /** The number of new orders beside "Orders" in the menu, as in the admin preview. */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Order::query()->where('status', OrderStatus::New)->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('New orders waiting for you to confirm');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::withRowData($query))
            ->defaultSort(fn (Builder $query) => $query->latest('placed_at')->latest('id'))   // REQ-ORD-01
            ->columns(static::rowColumns())
            ->recordUrl(fn (Order $record) => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentList)
            ->emptyStateHeading(__('No orders here'))
            ->emptyStateDescription(__('Orders show up here as soon as a customer places one.'));
    }

    /** The first piece's photo and the number of pieces for each row: batch queries, never one per order (REQ-DB-19). */
    public static function withRowData(Builder $query): Builder
    {
        return $query->with([
            'items' => fn ($query) => $query->select(['id', 'order_id', 'product_id', 'product_color_id', 'quantity'])->orderBy('id'),
            'items.productColor' => fn ($query) => $query->select(['id'])->with('mainPhoto:id,product_color_id,path'),
        ]);
    }

    /** One row per order, as in the admin preview: photo, "#number · name", "city · when · pieces", total and status. */
    public static function rowColumns(): array
    {
        return [
            Split::make([
                ImageColumn::make('photo')->label('')->grow(false)
                    ->state(fn (Order $record) => $record->items->first()?->productColor?->mainPhoto?->smallUrl())
                    ->imageWidth(48)->imageHeight(60)
                    ->extraImgAttributes(['class' => 'jude-order-thumb', 'alt' => '']),
                Stack::make([
                    TextColumn::make('number')->label(__('Order'))
                        ->formatStateUsing(fn (string $state, Order $record) => "#{$state} · ".static::isolate($record->customer_name))
                        ->weight(FontWeight::SemiBold),
                    TextColumn::make('placed_at')->label(__('Placed'))->color('gray')->size(TextSize::Small)
                        ->formatStateUsing(fn (Order $record) => implode(' · ', [
                            static::isolate($record->city_name),
                            $record->placed_at->timezone('Asia/Amman')->format('j M Y, H:i'),
                            trans_choice(':count item|:count items', $record->itemCount()),
                        ])),
                ])->space(1),
                Stack::make([
                    TextColumn::make('total')->label(__('Total'))->formatStateUsing(fn ($state) => Money::jod($state))
                        ->weight(FontWeight::SemiBold)->alignEnd(),
                    TextColumn::make('status')->label(__('Status'))->badge()->alignEnd()
                        ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                        ->color(fn (OrderStatus $state) => $state->color()),
                ])->space(1)->alignment(Alignment::End)->grow(false),
            ]),
        ];
    }

    /**
     * Wraps a name the customer typed so an Arabic one keeps to itself: without this, the date
     * after an Arabic city is pulled into right-to-left order ("24 · إربد Oct 2026").
     */
    private static function isolate(?string $text): string
    {
        return "\u{2068}{$text}\u{2069}";
    }

    /** The order sheet of the admin preview (REQ-ORD-10 to REQ-ORD-20). */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Status'))
                ->description(__('Tap a status to move the order to it.'))
                ->schema([
                    Actions::make(array_map(static::statusAction(...), OrderStatus::cases()))
                        ->key('status')
                        ->extraAttributes(['class' => 'jude-status-chips']),
                ]),
            Section::make(__('Customer'))->columns(['default' => 1, 'sm' => 2])->schema([
                TextEntry::make('customer_name')->label(__('Customer')),
                TextEntry::make('phone')->label(__('Phone'))->extraAttributes(['dir' => 'ltr']),
                TextEntry::make('email')->label(__('Email'))->visible(fn (Order $record) => filled($record->email)),
                TextEntry::make('address')->label(__('Address'))->state(fn (Order $record) => $record->deliveryAddress()),
                TextEntry::make('note')->label(__('Note'))->visible(fn (Order $record) => filled($record->note))->columnSpanFull(),
                TextEntry::make('payment_method')->label(__('Payment'))
                    ->formatStateUsing(fn (string $state) => $state === 'cash_on_delivery' ? __('Cash on delivery') : $state),
                TextEntry::make('language')->label(__('Language'))
                    ->formatStateUsing(fn (string $state) => $state === 'ar' ? __('Arabic') : __('English')),
            ]),
            Section::make(__('Items'))->schema([
                View::make('admin.order-items'),                                             // REQ-ORD-13 to 15
            ]),
            Section::make(__('Notes'))
                ->key('notes')
                ->description(__('Private: customers never see these notes.'))
                ->afterHeader([static::addNoteAction()])
                ->schema([
                    RepeatableEntry::make('notes')->hiddenLabel()
                        ->placeholder(__('No notes yet.'))
                        ->contained(false)
                        ->schema([
                            TextEntry::make('body')->hiddenLabel(),
                            TextEntry::make('written')->hiddenLabel()->color('gray')->size('sm')
                                ->state(fn (OrderNote $record) => ($record->author?->name ?? __('A removed account')).' · '.$record->created_at->timezone('Asia/Amman')->format('j M Y, H:i')),
                        ]),
                ]),                                                                          // REQ-ORD-20
        ]);
    }

    /** One chip per status; the order's own status is shown pressed (REQ-ORD-16, REQ-ORD-18). */
    public static function statusAction(OrderStatus $status): Action
    {
        $current = fn (Order $record) => $record->status === $status;

        return Action::make('status_'.$status->value)
            ->label($status->label())
            ->size('sm')
            ->color(fn (Order $record) => $current($record) ? $status->color() : 'gray')
            ->outlined(fn (Order $record) => ! $current($record))
            ->extraAttributes(fn (Order $record) => ['aria-pressed' => $current($record) ? 'true' : 'false'])
            ->authorize('update')
            ->action(function (Order $record) use ($status) {
                app(SetOrderStatus::class)($record, $status);

                Notification::make()->success()->title(__('Marked as :status', ['status' => $status->label()]))->send();
            });
    }

    public static function addNoteAction(): Action
    {
        return Action::make('addNote')
            ->label(__('Add note'))
            ->icon(Heroicon::OutlinedPlus)
            ->size('sm')
            ->color('gray')
            ->modalHeading(__('Add a note'))
            ->modalSubmitActionLabel(__('Add note'))
            ->schema([
                Textarea::make('body')->label(__('Note'))->required()->rows(3)->maxLength(AddOrderNote::MAX_LENGTH)
                    ->placeholder(__('For example, called the customer, will deliver on Sunday')),
            ])
            ->visible(fn () => Gate::allows('create', OrderNote::class))
            ->action(function (array $data, Order $record) {
                $author = Auth::user();
                abort_unless($author instanceof User && Gate::allows('create', OrderNote::class), 403);

                app(AddOrderNote::class)($record, $author, $data['body']);
                $record->load('notes.author:id,name');

                Notification::make()->success()->title(__('Note added'))->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
