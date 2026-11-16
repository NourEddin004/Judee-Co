<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\DuplicateProduct;
use App\Filament\Resources\Colors\ColorResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\Bilingual;
use App\Filament\Support\ImageUpload;
use App\Filament\Support\TranslatedLabels;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Scopes\PublishedScope;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Products, each holding its colors in one form: a color's photos and stock state live on its
 * row in the Colors section (REQ-CMS-01 to REQ-CMS-13, REQ-CMS-43 to REQ-CMS-46).
 */
class ProductResource extends Resource
{
    use TranslatedLabels;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name_en';

    /** Hidden products are still products to the owner (REQ-CMS-06). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(PublishedScope::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Product'))->columns(2)->schema([
                Bilingual::input('name', 'Name', required: true, max: 128, english: fn (TextInput $input) => $input
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state))
                        : null)),
                TextInput::make('slug')
                    ->label(__('Web address'))
                    ->prefix('/product/')
                    ->required()
                    ->maxLength(128)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->helperText(__('Lowercase letters, numbers and dashes. Filled in from the English name.')),
                Select::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name_en')
                    ->required()
                    ->native(false),
                TextInput::make('price')
                    ->label(__('Price'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(99999)
                    ->step(0.01)
                    ->suffix('JOD')
                    ->required(),
                Bilingual::textarea('description', 'Description', rows: 4),
                Bilingual::input('note', 'Short note'),
                Bilingual::input('badge', 'Badge', max: 32),
                CheckboxList::make('sizes')
                    ->relationship('sizes', 'label', fn (Builder $query) => $query->orderBy('position'))
                    ->columns(5)
                    ->bulkToggleable(),
            ]),
            Section::make(__('On the website'))->columns(['default' => 1, 'md' => 2])->schema([
                Toggle::make('is_published')->label(__('Show on the website'))->default(true)
                    ->helperText(__('Turn off to hide the product without deleting it.')),
                Toggle::make('is_featured')->label(__('Featured'))
                    ->helperText(__('Ranks the product higher in "Related products".')),   // REQ-DMO-25
                TextInput::make('display_order')->label(__('Display order'))->numeric()->integer()->default(0)
                    ->helperText(__('Lower numbers come first in the shop.')),   // REQ-DMO-08
            ]),
            Section::make(__('Colors'))
                ->description(__('Add each color the product comes in, with its own photos and stock. The first color is the one the site shows first.'))
                ->schema([
                    Repeater::make('colors')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('position')                                  // REQ-CMS-45
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => static::colorLabel($state))
                        ->addActionLabel(__('Add color'))                              // REQ-CMS-44
                        ->defaultItems(1)
                        ->minItems(1)
                        ->validationMessages(['min' => 'Add at least one color.'])
                        // The color's photos go with it when the product is saved (REQ-CMS-41).
                        ->deleteAction(fn (Action $action) => $action->requiresConfirmation()
                            ->modalHeading(__('Remove this color?'))
                            ->modalDescription(__('Its photos are removed too when you save the product.')))
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                Select::make('color_id')
                                    ->label(__('Color'))
                                    ->relationship('color', 'name_en', fn (Builder $query) => $query->orderBy('name_en'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    // A product cannot have the same color twice (REQ-DB-37).
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->validationMessages(['distinct' => 'This color is already in the list.'])
                                    ->rule(fn (Select $component, ?ProductColor $record) => static::noColorSwap($component, $record))
                                    ->createOptionForm(ColorResource::fields())     // REQ-CMS-46
                                    ->createOptionModalHeading(__('New color')),
                                Toggle::make('in_stock')->label(__('In stock'))->default(true)->inline(false),   // REQ-CMS-08
                            ]),
                            Repeater::make('photos')
                                ->label(__('Photos (up to :count)', ['count' => config('store.max_photos_per_color')]))
                                ->maxItems(config('store.max_photos_per_color'))      // REQ-CMS-10: 1 main + up to 3 more
                                ->relationship()
                                ->orderColumn('position')                          // REQ-CMS-13
                                ->reorderableWithButtons()
                                ->grid(['default' => 1, 'sm' => 2, 'lg' => 3])
                                ->addActionLabel(__('Add photo'))                      // REQ-CMS-10
                                ->defaultItems(0)
                                ->schema([
                                    ImageUpload::make('path', 'products')->hiddenLabel()->required()->imagePreviewHeight('180'),
                                    Toggle::make('is_main')->label(__('Main photo'))->live()     // REQ-CMS-11
                                        ->afterStateUpdated(fn (bool $state, Set $set) => $state ? $set('is_hover', false) : null),
                                    Toggle::make('is_hover')->label(__('Hover photo'))->live()   // REQ-CMS-12
                                        ->disabled(fn (Get $get) => (bool) $get('is_main'))
                                        ->helperText(fn (Get $get) => $get('is_main') ? __('The main photo can\'t also be the hover photo.') : null),   // REQ-UX-27
                                ])
                                ->helperText(__('Up to 4 photos for each color: 1 main photo and up to 3 more. The main photo shows on product cards; the hover photo shows when a shopper points at the card.')),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'colors.color', 'colors.mainPhoto']))
            ->defaultSort('display_order')
            ->columns([
                ImageColumn::make('photo')->label('')->disk('public')->imageHeight(56)
                    ->state(fn (Product $record) => $record->colors->first()?->mainPhoto?->path),
                TextColumn::make('name_en')->label(__('Name'))->searchable(['name_en', 'name_ar'])->sortable()
                    ->description(fn (Product $record) => $record->name_ar),
                // Searching finds products by the name of one of their colors too (REQ-CMS-01).
                TextColumn::make('color_names')->label(__('Colors'))
                    ->state(fn (Product $record) => $record->colors->map(fn (ProductColor $color) => $color->in_stock
                        ? $color->color->name_en
                        : __(':color (out of stock)', ['color' => $color->color->name_en]))->all())
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('colors.color', fn (Builder $query) => $query
                        ->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%"))),
                TextColumn::make('category.name_en')->label(__('Category')),
                TextColumn::make('price')->formatStateUsing(fn ($state) => Money::jod($state))->sortable(),
                ToggleColumn::make('is_published')->label(__('On site')),
                ToggleColumn::make('is_featured')->label(__('Featured')),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name_en'),
                TernaryFilter::make('stock')->label(__('Stock'))                     // REQ-CMS-08
                    ->trueLabel(__('Every color in stock'))
                    ->falseLabel(__('A color out of stock'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereDoesntHave('colors', fn (Builder $query) => $query->where('in_stock', false)),
                        false: fn (Builder $query) => $query->whereHas('colors', fn (Builder $query) => $query->where('in_stock', false)),
                    ),
                TernaryFilter::make('is_published')->label(__('On the website'))->trueLabel(__('Shown'))->falseLabel(__('Hidden')),
            ])
            ->recordActions([
                EditAction::make(),
                static::duplicateAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('Duplicate'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->requiresConfirmation()
            ->modalDescription(__('The copy keeps the colors, photos, sizes and price, and starts hidden from the website.'))
            ->authorize('replicate')
            ->action(fn (Product $record, Action $action) => $action->redirect(
                static::getUrl('edit', ['record' => app(DuplicateProduct::class)($record)]),
            ));
    }

    /** "Navy, out of stock" on a collapsed color row. */
    private static function colorLabel(array $state): string
    {
        $name = filled($state['color_id'] ?? null) ? Color::query()->whereKey($state['color_id'])->value('name_en') : null;

        if ($name === null) {
            return __('New color');
        }

        return ($state['in_stock'] ?? true) ? $name : __(':color, out of stock', ['color' => $name]);
    }

    /**
     * Saved rows are updated one at a time, in the form's order, and the database takes each
     * color once per product (REQ-DB-37). So a row cannot take a color that a later saved row
     * gives up in the same save, for example two rows trading colors.
     */
    private static function noColorSwap(Select $component, ?ProductColor $record): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($component, $record) {
            if ($record === null || (int) $value === $record->color_id) {
                return;
            }

            $holder = ProductColor::query()->where('product_id', $record->product_id)->where('color_id', (int) $value)->first();
            $rows = $component->getParentRepeater()?->getRawState() ?? [];
            $keys = array_keys($rows);
            $later = $holder !== null && array_search("record-{$holder->id}", $keys, true) > array_search("record-{$record->id}", $keys, true);

            // A row that keeps the color is already caught by the "distinct" check.
            if ($later && (int) ($rows["record-{$holder->id}"]['color_id'] ?? 0) !== (int) $value) {
                $fail(__('Another row still has this color until the product is saved. Pick a different color for now, save, then choose this one.'));
            }
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
