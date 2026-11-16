<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\CatalogData;
use App\Domain\Catalog\ShopQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function show(Request $request, ShopQuery $shop, CatalogData $catalog): View
    {
        $slug = $request->route('category');
        $category = $slug ? $catalog->category($slug) : null;
        abort_if($slug !== null && $category === null, 404);

        // Text values only: an address such as ?q[]=x is treated as if the value were left out (REQ-SEC-35).
        $text = fn (string $key): string => is_string($value = $request->query($key)) ? $value : '';

        // ?shade=a,b and ?shade=a&shade=b are both accepted (demo behaviour); ?size= the same way.
        $list = fn (string $key): array => collect((array) $request->query($key, []))
            ->flatMap(fn ($value) => is_string($value) ? explode(',', $value) : [])
            ->map(fn ($value) => trim($value))
            ->filter()
            ->all();

        // A ?sort= left over from the demo's addresses is ignored: there is no sorting (REQ-DMO-08).
        $result = $shop->run(
            $category,
            $text('q'),
            $list('shade'),
            $list('size'),
            (int) $text('page') ?: 1,
        );

        return view('storefront.shop', [
            'result' => $result,
            'categorySlug' => $category?->slug,
            'counts' => $shop->countsByCategory(),
            'categories' => $catalog->categories(),
        ]);
    }
}
