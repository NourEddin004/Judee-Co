<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/** The order sheet (REQ-ORD-10). */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        /** @var Order $order */
        $order = parent::resolveRecord($key);

        // Each piece with the photo of its colour, even if the product is hidden from the site now (REQ-DB-19).
        return $order->load([
            'items.productColor' => fn ($query) => $query->select(['id'])->with('mainPhoto:id,product_color_id,path'),
            'notes.author:id,name',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('Order #:number', ['number' => $this->getRecord()->number]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Placed :when (Jordan time)', ['when' => $this->getRecord()->placed_at->timezone('Asia/Amman')->format('j M Y, H:i')]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('packingSlip')
                ->label(__('Print packing slip'))
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->authorize('view')
                ->url(fn (Order $record) => route('filament.admin.orders.packing-slip', $record), shouldOpenInNewTab: true),   // REQ-ORD-22
        ];
    }
}
