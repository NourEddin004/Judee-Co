<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Support\Collection;

/**
 * Same category, best match first: +4 when it comes in a colour of the same family as the colour
 * shown, +2 featured, +1 per shared collection; ties by display order, then name (REQ-DMO-24,
 * REQ-DMO-25). At most 6 (REQ-DMO-26, which the client chose over REQ-STR-13's 4). Scored inside
 * MySQL so only 6 rows come back.
 */
class RelatedProducts
{
    public const LIMIT = 6;

    public function __construct(private readonly CatalogData $catalog, private readonly ProductCards $cards) {}

    public function for(Product $product, ProductColor $shown, array $collectionIds): Collection
    {
        $shade = $this->catalog->shadeOfColor($shown->color_id);
        $sameShadeColorIds = $shade ? $this->catalog->colors()->where('shade_id', $shade->id)->keys()->all() : [];

        $score = '(CASE WHEN EXISTS (SELECT 1 FROM product_colors pc WHERE pc.product_id = products.id AND pc.color_id IN ('.$this->placeholders($sameShadeColorIds).')) THEN 4 ELSE 0 END)'
            .' + (CASE WHEN is_featured THEN 2 ELSE 0 END)'
            .' + (SELECT COUNT(*) FROM collection_product cp WHERE cp.product_id = products.id AND cp.collection_id IN ('.$this->placeholders($collectionIds).'))';

        $related = Product::query()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->select(ShopQuery::CARD_COLUMNS)
            ->selectRaw("{$score} as related_score", [...($sameShadeColorIds ?: [0]), ...($collectionIds ?: [0])])
            ->withExists('sizes as needs_size')
            ->orderByDesc('related_score')
            ->orderBy('display_order')
            ->orderBy('name_'.app()->getLocale())
            ->limit(self::LIMIT)
            ->get();

        return $this->cards->load($related);
    }

    private function placeholders(array $values): string
    {
        return implode(',', array_fill(0, max(1, count($values)), '?'));
    }
}
