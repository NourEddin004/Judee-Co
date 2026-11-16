<?php

namespace App\Filament\Support;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\RackItems\RackItemResource;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

/** The phone menu along the bottom of the screen, as in the admin preview (SRS 4.1). */
final class BottomNav
{
    /** @return list<array{label: string, url: string, icon: Heroicon, active: bool}> */
    public static function items(): array
    {
        $items = [
            ['Home', Dashboard::class, Heroicon::OutlinedHome, 'filament.admin.pages.dashboard'],
            ['Rack', RackItemResource::class, Heroicon::OutlinedPhoto, 'filament.admin.resources.hero-rack.*'],
            ['Products', ProductResource::class, Heroicon::OutlinedShoppingBag, 'filament.admin.resources.products.*'],
            ['Orders', OrderResource::class, Heroicon::OutlinedClipboardDocumentList, 'filament.admin.resources.orders.*'],
        ];

        return array_values(array_map(
            fn (array $item) => [
                'label' => __($item[0]),
                'url' => $item[1]::getUrl(),
                'icon' => $item[2],
                'active' => request()->routeIs($item[3]),
            ],
            array_filter($items, fn (array $item) => $item[1]::canAccess()),
        ));
    }

    public static function shouldShow(): bool
    {
        return Filament::auth()->check() && ! request()->routeIs('filament.admin.auth.*');
    }
}
