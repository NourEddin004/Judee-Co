<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\Bilingual;
use App\Filament\Support\SafeDelete;
use App\Filament\Support\TranslatedLabels;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shop categories: Sets, Hijabs, Bundles (REQ-CMS-14). */
class CategoryResource extends Resource
{
    use TranslatedLabels;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Bilingual::input('name', 'Name', required: true, max: 64),
            TextInput::make('slug')->label(__('Web address'))->prefix('/')->required()->maxLength(64)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true),
            Bilingual::input('page_title', 'Page title', required: true, max: 96),
            Bilingual::input('empty_title', 'Title when no products match', max: 128),
            Bilingual::input('empty_body', 'Text when no products match'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name_en')->label(__('Name'))->description(fn (Category $record) => $record->name_ar)->searchable(['name_en', 'name_ar']),
                TextColumn::make('slug')->label(__('Web address'))->prefix('/'),
                TextColumn::make('products_count')->label(__('Products'))->counts([
                    'products' => fn ($query) => $query->withoutGlobalScopes(),
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(
                    fn (Category $record) => Product::withoutGlobalScopes()->where('category_id', $record->id)->exists(),
                    __('Move or delete this category\'s products first.'),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
