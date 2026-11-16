<?php

namespace App\Domain\Rack;

use App\Models\RackItem;
use App\Support\StoreCache;
use Illuminate\Support\Collection;

/** The garments on the home page rail, in the owner's order (REQ-DMO-80). */
class HeroRack
{
    public const MAX_ITEMS = 6;

    public function __construct(private readonly StoreCache $cache) {}

    public function items(): Collection
    {
        return $this->cache->remember('rack', fn () => RackItem::query()
            ->where('is_visible', true)
            ->orderBy('position')
            ->limit(self::MAX_ITEMS)
            // Each garment opens its product on the colour the owner chose (RackItem::url()).
            ->with(['product' => fn ($query) => $query->select(['id', 'slug'])
                ->with(['colors' => fn ($query) => $query->select(['id', 'product_id', 'color_id', 'position'])->with('color:id,slug')])])
            ->get(['id', 'product_id', 'product_color_id', 'name_en', 'name_ar', 'alt_en', 'alt_ar', 'image_path', 'size_adjust']));
    }
}
