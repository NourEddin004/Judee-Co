<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\CatalogData;
use App\Domain\Catalog\RelatedProducts;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __invoke(Request $request, string $slug, CatalogData $catalog, RelatedProducts $related): View|RedirectResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->with(['colors.photos', 'sizes:id,label,position', 'collections:id'])
            ->first();

        if ($product === null) {
            return $this->formerAddress($slug);
        }

        // Every product has at least one colour (REQ-CMS-43); without one there is nothing to sell.
        abort_if($product->colors->isEmpty(), 404);

        // The colour names, slugs and swatches come from the cached colour list, not another query.
        $product->colors->each(function (ProductColor $color) use ($product, $catalog) {
            $color->setRelation('product', $product);
            $color->setRelation('color', $catalog->colors()->get($color->color_id));
        });

        // ?color= picks the colour; without it, or with an unknown one, the first shows (REQ-DMO-98).
        $requested = $request->query('color');
        $selected = (is_string($requested) ? $product->colors->first(fn (ProductColor $color) => $color->color?->slug === $requested) : null)
            ?? $product->colors->first();

        $category = $catalog->categoryById($product->category_id);

        return view('storefront.product', [
            'product' => $product,
            'category' => $category,
            'selected' => $selected,
            'sizeGuide' => $product->sizes->isNotEmpty() ? $category?->sizeGuide : null,
            'related' => $related->for($product, $selected, $product->collections->pluck('id')->all()),
        ]);
    }

    /** A demo product that is now one colour of a product keeps its address (REQ-DMO-103). */
    private function formerAddress(string $slug): RedirectResponse
    {
        $color = ProductColor::query()
            ->where('legacy_slug', $slug)
            ->whereHas('product')
            ->with(['product:id,slug', 'color:id,slug'])
            ->first();

        abort_if($color === null, 404);

        return redirect()->to(product_url($color->product->slug, $color->color->slug), 301);
    }
}
