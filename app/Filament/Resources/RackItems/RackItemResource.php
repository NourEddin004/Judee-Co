<?php

namespace App\Filament\Resources\RackItems;

use App\Domain\Rack\MoveRackItem;
use App\Domain\Rack\ReplaceRackImageAction;
use App\Filament\Resources\RackItems\Pages\ManageRackItems;
use App\Filament\Support\Bilingual;
use App\Filament\Support\ImageUpload;
use App\Filament\Support\TranslatedLabels;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\RackItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/** The garments hanging on the home page rail (REQ-RCK-01 to REQ-RCK-16). Owner only (C.1). */
class RackItemResource extends Resource
{
    use TranslatedLabels;

    protected static ?string $model = RackItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Hero rack';

    protected static ?string $modelLabel = 'garment';

    protected static ?string $pluralModelLabel = 'hero rack';

    protected static ?string $slug = 'hero-rack';

    protected static ?int $navigationSort = 2;

    /**
     * Kept short on purpose: the picture, the product it opens, and whether it shows. The name
     * comes from the product (it can be changed), the screen-reader text follows the name, and
     * the size is set with the Smaller and Bigger buttons in the list.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ImageUpload::base('image_path')
                ->label(__('Picture'))
                ->storeFiles(false)
                ->required()
                ->visibleOn('create')
                ->helperText(__('Best: a PNG or WEBP with no background. JPG, PNG or WEBP, up to 5 MB. It is sized and hung on the rail for you.')),
            ImageUpload::base('new_image')
                ->label(__('New picture'))
                ->storeFiles(false)
                ->visibleOn('edit')
                ->helperText(__('Leave empty to keep the current picture.')),
            Select::make('product_id')
                ->label(__('Product'))
                ->relationship('product', 'name_en')
                ->searchable()
                ->preload()
                ->live()
                // An empty name is filled in from the product; a name already typed is kept.
                ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                    $set('product_color_id', null);
                    $product = blank($state) ? null : Product::query()->find((int) $state, ['id', 'name_en', 'name_ar']);

                    if ($product !== null && blank($get('name_en'))) {
                        $set('name_en', $product->name_en);
                        $set('name_ar', $get('name_ar') ?: $product->name_ar);
                    }
                })
                ->helperText(__('Tapping the garment opens this product.')),
            Select::make('product_color_id')
                ->label(__('Color'))
                ->placeholder(__('First color'))
                ->options(fn (Get $get) => static::colorOptions($get('product_id')))
                ->rule(fn (Get $get) => Rule::exists('product_colors', 'id')->where('product_id', (int) $get('product_id')))
                ->visible(fn (Get $get) => count(static::colorOptions($get('product_id'))) > 1)
                ->dehydratedWhenHidden(),   // so a new product clears the old product's colour
            Bilingual::input('name', 'Name', required: true, max: 96),
            Toggle::make('is_visible')->label(__('Show on the home page'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'productColor.color']))
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                ImageColumn::make('image_path')->label('')->disk('public')->imageHeight(72),
                TextColumn::make('name_en')->label(__('Garment'))
                    ->description(fn (RackItem $record) => __('Size :percent%', ['percent' => round((float) $record->size_adjust * 100)])),
                ToggleColumn::make('is_visible')->label(__('Shown')),            // REQ-RCK-09
            ])
            ->recordActions([
                static::moveAction('up', -1, Heroicon::OutlinedArrowUp),     // REQ-RCK-08
                static::moveAction('down', 1, Heroicon::OutlinedArrowDown),
                static::resizeAction('smaller', -1, Heroicon::OutlinedMinus),  // REQ-RCK-16
                static::resizeAction('bigger', 1, Heroicon::OutlinedPlus),
                EditAction::make()
                    ->using(function (RackItem $record, array $data) {
                        $file = $data['new_image'] ?? null;
                        unset($data['new_image']);

                        if ($file) {
                            app(ReplaceRackImageAction::class)->execute($record, $file);   // REQ-RCK-04
                        }

                        // The screen-reader text follows the name.
                        $record->update([...$data, 'alt_en' => $data['name_en'], 'alt_ar' => $data['name_ar'] ?? null]);

                        return $record;
                    })
                    ->after(fn (RackItem $record) => static::warnIfNotTransparent($record)),
                DeleteAction::make(),          // REQ-RCK-10
            ]);
    }

    /** The chosen product's colours, in the owner's order, keyed by product colour id. */
    private static function colorOptions(mixed $productId): array
    {
        if (blank($productId)) {
            return [];
        }

        return ProductColor::query()
            ->where('product_id', (int) $productId)
            ->with('color:id,name_en')
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (ProductColor $color) => [$color->id => $color->color->name_en])
            ->all();
    }

    private static function moveAction(string $name, int $direction, Heroicon $icon): Action
    {
        return Action::make("move_{$name}")
            ->label(__('Move '.$name))
            ->iconButton()
            ->tooltip(__('Move '.$name))
            ->icon($icon)
            ->authorize('reorder')
            ->action(fn (RackItem $record) => app(MoveRackItem::class)($record, $direction));
    }

    private static function resizeAction(string $name, int $direction, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label(__(ucfirst($name)))
            ->color('gray')
            ->icon($icon)
            ->authorize('update')
            ->action(function (RackItem $record) use ($direction) {
                $size = (float) $record->size_adjust + $direction * config('store.rack.size_step');
                $record->update(['size_adjust' => round(min(config('store.rack.size_max'), max(config('store.rack.size_min'), $size)), 2)]);
            });
    }

    /** REQ-RCK-12 */
    public static function warnIfNotTransparent(RackItem $item): void
    {
        if ($item->hadTransparentBackground === false) {
            Notification::make()
                ->warning()
                ->title(__('This image has no see-through background'))
                ->body(__('It will hang on the rail as a rectangle. For the best look, upload a PNG or WEBP with the background removed.'))
                ->persistent()
                ->send();
        }
    }

    public static function getPages(): array
    {
        return ['index' => ManageRackItems::route('/')];
    }
}
