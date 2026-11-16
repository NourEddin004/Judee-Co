<?php

namespace Tests\Concerns;

use App\Models\ProductColor;

/**
 * The demo's pieces by their old addresses: "black-wrap-set" is the Black color of the Wrap Set,
 * and a one-color product such as "rosemary-hijab" is its only color (REQ-DMO-103).
 */
trait DemoPieces
{
    protected function piece(string $slug): ProductColor
    {
        return ProductColor::query()
            ->where(fn ($query) => $query->where('legacy_slug', $slug)
                ->orWhereHas('product', fn ($query) => $query->withoutGlobalScopes()->where('slug', $slug)))
            ->with(['product' => fn ($query) => $query->withoutGlobalScopes(), 'color'])
            ->orderBy('position')
            ->firstOrFail();
    }
}
