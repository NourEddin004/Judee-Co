<?php

namespace App\Filament\Resources\RackItems\Pages;

use App\Domain\Rack\AddRackItemAction;
use App\Domain\Rack\HeroRack;
use App\Domain\Rack\RackItemData;
use App\Filament\Resources\RackItems\RackItemResource;
use App\Models\RackItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ManageRackItems extends ManageRecords
{
    protected static string $resource = RackItemResource::class;

    public function getSubheading(): ?string
    {
        return __('Up to 6 shown garments hang on the home page, in this order. Use the arrows to move them.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add garment'))
                ->modalHeading(__('Add a garment'))
                ->modalSubmitActionLabel(__('Add'))
                ->createAnother(false)
                ->using(fn (array $data) => app(AddRackItemAction::class)->execute(RackItemData::fromForm($data)))
                ->after(fn (RackItem $record) => RackItemResource::warnIfNotTransparent($record)),
        ];
    }

    /** The live preview sits above the list and redraws after every change (REQ-RCK-11). */
    public function content(Schema $schema): Schema
    {
        $schema = parent::content($schema);

        return $schema->components([
            View::make('admin.rack-preview')->viewData(fn () => [
                'rack' => RackItem::query()
                    ->where('is_visible', true)
                    ->orderBy('position')
                    ->limit(HeroRack::MAX_ITEMS)
                    ->get(),
            ]),
            ...$schema->getComponents(withHidden: true),
        ]);
    }
}
