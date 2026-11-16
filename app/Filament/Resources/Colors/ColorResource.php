<?php

namespace App\Filament\Resources\Colors;

use App\Filament\Resources\Colors\Pages\ManageColors;
use App\Filament\Support\Bilingual;
use App\Filament\Support\SafeDelete;
use App\Filament\Support\TranslatedLabels;
use App\Models\Color;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Product colors, each in one of the shop filter's color families, with its own swatch (REQ-CMS-15, REQ-CMS-46). */
class ColorResource extends Resource
{
    use TranslatedLabels;

    protected static ?string $model = Color::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(static::fields());
    }

    /** The color's fields, also used to add a color from the product form. */
    public static function fields(): array
    {
        return [
            Bilingual::input('name', 'Name', required: true, max: 64),
            Select::make('shade_id')->label(__('Color family in the shop filter'))
                ->relationship('shade', 'name_en', fn ($query) => $query->orderBy('position'))
                ->required()->native(false),
            ColorPicker::make('swatch')->label(__('Swatch'))
                ->hex()
                ->regex('/^#[0-9a-fA-F]{6}$/')
                ->helperText(__('The dot shoppers see for this color. Leave empty to use the color family\'s swatch.')),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('shade'))
            ->defaultSort('name_en')
            ->columns([
                ColorColumn::make('swatch')->label('')->state(fn (Color $record) => $record->swatchHex()),
                TextColumn::make('name_en')->label(__('Name'))->description(fn (Color $record) => $record->name_ar)
                    ->searchable(['name_en', 'name_ar'])->sortable(),
                TextColumn::make('shade.name_en')->label(__('Family')),
                TextColumn::make('product_colors_count')->label(__('Products'))->counts('productColors'),
            ])
            ->filters([SelectFilter::make('shade')->label(__('Family'))->relationship('shade', 'name_en')])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(fn (Color $record) => $record->productColors()->exists(), __('Products still use this color.')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageColors::route('/')];
    }
}
