<?php

namespace App\Filament\Resources\SizeGuides;

use App\Domain\Catalog\SizeFit;
use App\Domain\Catalog\SizeGuideTable;
use App\Filament\Resources\SizeGuides\Pages\ManageSizeGuides;
use App\Filament\Support\TranslatedLabels;
use App\Models\SizeGuide;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** One size table per category, shown on its product pages (REQ-CMS-18). */
class SizeGuideResource extends Resource
{
    use TranslatedLabels;

    protected static ?string $model = SizeGuide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('category_id')->label(__('Category'))
                ->relationship('category', 'name_en')
                ->required()->unique(ignoreRecord: true)->native(false),
            Grid::make(['default' => 1, 'md' => 2])->schema([
                static::tableField('content_en', 'Size table (English)')->required(),
                static::tableField('content_ar', 'Size table (Arabic)')->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']),
            ]),
            static::fitField(),
        ]);
    }

    /**
     * Height and weight per size, for "Find my size" on the product page. A new guide starts with
     * general starting values; replace them with JUDE's own chart.
     */
    private static function fitField(): Repeater
    {
        $number = fn (string $name, string $label, int $min, int $max) => TextInput::make($name)->label(__($label))
            ->numeric()->integer()->required()->minValue($min)->maxValue($max);

        return Repeater::make('fit')
            ->label(__('Find my size: height and weight per size'))
            ->helperText(__('Customers type their height and weight on the product page and see the size that fits. Write the range each size fits, in cm and kg.'))
            ->default(SizeFit::defaultsFor(array_keys(SizeFit::DEFAULTS)))
            ->schema([
                TextInput::make('size')->label(__('Size'))->required()->maxLength(10),
                $number('height_min', 'Height from (cm)', 100, 220),
                $number('height_max', 'Height to (cm)', 100, 220),
                $number('weight_min', 'Weight from (kg)', 25, 200),
                $number('weight_max', 'Weight to (kg)', 25, 200),
            ])
            ->columns(['default' => 2, 'md' => 5])
            ->addActionLabel(__('Add a size'))
            ->reorderable()
            ->collapsible(false)
            ->dehydrateStateUsing(fn (?array $state) => SizeFit::clean($state) ?: null);
    }

    private static function tableField(string $name, string $label): Textarea
    {
        return Textarea::make($name)
            ->label($label)
            ->rows(8)
            ->formatStateUsing(fn ($state) => is_array($state) ? SizeGuideTable::toText($state) : $state)
            ->dehydrateStateUsing(fn (?string $state) => SizeGuideTable::fromText($state))
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                if ($problem = SizeGuideTable::problem(is_string($value) ? $value : null)) {
                    $fail($problem);
                }
            })
            ->helperText(__('First line: the column titles. Then one line per size. Separate cells with |, for example: S | 84–88 | 66–70'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                TextColumn::make('category.name_en')->label(__('Category')),
                TextColumn::make('content_en')->label(__('Columns'))
                    ->state(fn (SizeGuide $record) => implode(', ', $record->content_en['columns'] ?? [])),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSizeGuides::route('/')];
    }
}
