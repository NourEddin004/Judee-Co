<?php

namespace App\Domain\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * One shop page: category, colour and size filters, search and pagination (Appendix D.6.2, D.7).
 * Products show in the owner's display order; there is no sort menu (REQ-DMO-08). Every query
 * reads one page of rows with only the columns the card shows (REQ-DB-17, 18, 19).
 */
class ShopQuery
{
    public const PER_PAGE = 6;                                   // REQ-STR-07, REQ-DMO-07

    /** Columns a product card needs. */
    public const CARD_COLUMNS = [
        'id', 'category_id', 'slug', 'name_en', 'name_ar', 'badge_en', 'badge_ar', 'price', 'is_featured', 'display_order',
    ];

    public function __construct(private readonly CatalogData $catalog, private readonly ProductCards $cards) {}

    /**
     * @param  array<int, string>  $shades  shade keys from ?shade=
     * @param  array<int, string>  $sizes  size labels from ?size=, in any case
     */
    public function run(?Category $category, string $search, array $shades, array $sizes, int $page): ShopResult
    {
        $base = Product::query()->when($category, fn (Builder $query) => $query->where('category_id', $category->id));

        // Only colours and sizes that the category actually has are offered and accepted (REQ-DMO-14, REQ-DMO-92).
        $presentColorIds = ProductColor::query()->whereIn('product_id', (clone $base)->select('products.id'))->distinct()->pluck('color_id')->all();
        $availableShades = $this->catalog->shades()
            ->filter(fn ($shade) => $this->catalog->colors()->whereIn('id', $presentColorIds)->contains('shade_id', $shade->id))
            ->values();
        $presentSizeIds = (clone $base)->join('product_size', 'product_size.product_id', '=', 'products.id')->distinct()->pluck('product_size.size_id')->all();
        $availableSizes = $this->catalog->sizes()->whereIn('id', $presentSizeIds)->values();

        $selectedShades = array_values(array_intersect($availableShades->pluck('key')->all(), $shades));
        $wanted = array_map('mb_strtolower', $sizes);
        $selectedSizes = $availableSizes->filter(fn ($size) => in_array(mb_strtolower($size->label), $wanted, true))->values();
        $search = trim($search);

        $shadeColorIds = $selectedShades === [] ? [] : $this->catalog->colorIdsForShades($selectedShades);
        $searchColorIds = $search === '' ? [] : $this->colorIdsMatching($search);

        $filtered = (clone $base)
            ->when($shadeColorIds !== [], fn (Builder $query) => $query->whereExists($this->hasColor($shadeColorIds)))       // REQ-DMO-15
            ->when($selectedSizes->isNotEmpty(), fn (Builder $query) => $query->whereExists($this->hasSize($selectedSizes->pluck('id')->all())))  // REQ-DMO-93
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search, $searchColorIds));

        $total = (clone $filtered)->count();
        $pageCount = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max($page, 1), $pageCount);  // out of range shows the nearest valid page (D.6.2)

        $products = $filtered
            ->select(self::CARD_COLUMNS)
            ->withExists('sizes as needs_size')
            ->orderBy('display_order')->orderBy('name_'.app()->getLocale())   // REQ-DMO-08
            ->forPage($page, self::PER_PAGE)
            ->get();

        // A card shows its first colour, or the first one the colour filter or search matches (REQ-DMO-97).
        $this->cards->load($products, $shadeColorIds !== [] ? $shadeColorIds : $searchColorIds);

        return new ShopResult($category, $products, $total, $page, $pageCount, $availableShades, $selectedShades, $availableSizes, $selectedSizes->pluck('label')->all(), $search);
    }

    /** Product count per category for the tabs, in one grouped query. */
    public function countsByCategory(): Collection
    {
        return Product::query()->toBase()->selectRaw('category_id, count(*) as aggregate')->groupBy('category_id')->pluck('aggregate', 'category_id');
    }

    /**
     * Name, description, category or the name of one of its colours contains the text, ignoring
     * case (REQ-DMO-12, 13). Name and description use the full-text index (REQ-DB-22); the small
     * category and colour lists are matched in memory and turned into id lists.
     */
    public function applySearch(Builder $query, string $search, ?array $colorIds = null): Builder
    {
        $needle = mb_strtolower($search);
        $categoryIds = $this->catalog->categories()
            ->filter(fn ($category) => str_contains(mb_strtolower($category->name_en.' '.$category->name_ar), $needle))
            ->pluck('id')->all();
        $colorIds ??= $this->colorIdsMatching($search);
        $terms = self::booleanTerms($search);

        return $query->where(function (Builder $query) use ($terms, $categoryIds, $colorIds) {
            $query->when($terms !== '',
                fn (Builder $query) => $query->whereFullText(['name_en', 'name_ar', 'description_en', 'description_ar'], $terms, ['mode' => 'boolean']),
                fn (Builder $query) => $query->whereRaw('0 = 1'))
                ->when($categoryIds !== [], fn (Builder $query) => $query->orWhereIn('category_id', $categoryIds))
                ->when($colorIds !== [], fn (Builder $query) => $query->orWhereExists($this->hasColor($colorIds)));
        });
    }

    /** Colours whose name, or whose family's name, contains the text. */
    public function colorIdsMatching(string $search): array
    {
        $needle = mb_strtolower(trim($search));

        return $this->catalog->colors()
            ->filter(function ($color) use ($needle) {
                $shade = $this->catalog->shadeById($color->shade_id);

                return str_contains(mb_strtolower("{$color->name_en} {$color->name_ar} {$shade?->key} {$shade?->name_en} {$shade?->name_ar}"), $needle);
            })
            ->keys()->all();
    }

    /** "olive no" → "+olive* +no*": every word must match, as a word start. */
    public static function booleanTerms(string $search): string
    {
        // Only letters and numbers reach MySQL, so no input can act as a full-text operator.
        $words = preg_split('/[^\p{L}\p{N}]+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_map(fn ($word) => '+'.$word.'*', $words));
    }

    /** The product comes in one of these colours: an indexed lookup in product_colors (3.7.4). */
    private function hasColor(array $colorIds): \Closure
    {
        return fn (QueryBuilder $query) => $query->selectRaw('1')->from('product_colors')
            ->whereColumn('product_colors.product_id', 'products.id')
            ->whereIn('product_colors.color_id', $colorIds);
    }

    /** The product comes in one of these sizes. */
    private function hasSize(array $sizeIds): \Closure
    {
        return fn (QueryBuilder $query) => $query->selectRaw('1')->from('product_size')
            ->whereColumn('product_size.product_id', 'products.id')
            ->whereIn('product_size.size_id', $sizeIds);
    }
}
