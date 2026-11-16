<?php

namespace App\Domain\Catalog;

use App\Models\ProductColor;
use Illuminate\Support\Collection;

/**
 * The bag's product colours as they are now, for the bag views and checkout: only colours of
 * published products (REQ-DB-31), with what a bag line shows (D.5.9).
 */
class BagProducts
{
    /**
     * @param  list<int>  $ids  product colour ids
     * @return Collection<int, ProductColor> keyed by id
     */
    public function find(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return ProductColor::query()
            ->whereKey(array_values(array_unique($ids)))
            ->whereHas('product')
            ->with([
                // The product's colours tell whether lines carry the colour's name and need ?color=.
                'product' => fn ($query) => $query->select(['id', 'slug', 'name_en', 'name_ar', 'price', 'is_published'])
                    ->with(['colors:id,product_id,position', 'sizes:id,label,position']),
                'color:id,slug,name_en,name_ar',
                'mainPhoto:id,product_color_id,path',
            ])
            ->get()
            ->keyBy('id');
    }
}
