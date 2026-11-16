<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\TidyProductPhotos;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('View on site'))
                ->color('gray')
                ->url(fn () => store_route('product', $this->getRecord()->slug), shouldOpenInNewTab: true)
                ->visible(fn () => $this->getRecord()->is_published),
            ProductResource::duplicateAction()->record($this->getRecord()),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(TidyProductPhotos::class)($this->getRecord());
        $this->fillForm();
    }
}
