<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\BagProducts;
use App\Http\Controllers\Controller;
use App\Models\ProductColor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The bag is kept in the customer's browser (REQ-CHK-01). This endpoint gives the browser
 * current data for the product colours in it; it returns no customer or admin data (REQ-SEC-36, REQ-SEC-37).
 */
class BagController extends Controller
{
    private const MAX_LINES = 50;

    public function check(Request $request, BagProducts $bagProducts): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:'.self::MAX_LINES],
            'ids.*' => ['integer', 'min:1'],
        ])['ids'];

        return response()->json([
            'products' => $bagProducts->find($ids)->map(fn (ProductColor $color) => [
                'id' => $color->id,
                'name' => $color->name(),           // "Navy Wrap Set" (D.8.1)
                'url' => $color->url(),             // opens on this colour (D.5.9)
                'image' => $color->mainPhoto?->smallUrl(),
                'price' => $color->product->price === null ? null : (float) $color->product->price,
                'purchasable' => $color->isPurchasable(),
                'sizes' => $color->product->sizes->pluck('label')->all(),
            ])->values(),
        ]);
    }
}
