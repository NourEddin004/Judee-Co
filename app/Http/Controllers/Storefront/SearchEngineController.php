<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\CatalogData;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;

/**
 * Search-engine setup (SRS 6.1, M3): a sitemap of every public page (one address
 * per page serves both languages), and a
 * robots.txt that keeps search engines out of the admin, the bag and the order pages.
 */
class SearchEngineController extends Controller
{
    /** Products are read in pages of this size, never the whole table at once (REQ-DB-17). */
    private const CHUNK = 500;

    public function sitemap(CatalogData $catalog): Response
    {
        $pages = [
            ['route' => 'home', 'parameters' => []],
            ['route' => 'shop', 'parameters' => []],
            ...$catalog->categories()->map(fn ($category) => ['category' => $category->slug])->all(),
            ['route' => 'contact', 'parameters' => []],
            ['route' => 'terms', 'parameters' => []],
        ];

        $urls = array_map(fn (array $page) => [
            'loc' => isset($page['category']) ? category_url($page['category']) : store_route($page['route'], $page['parameters']),
            'lastmod' => null,
        ], $pages);

        Product::query()->select(['id', 'slug', 'updated_at'])->chunkById(self::CHUNK, function ($products) use (&$urls) {
            foreach ($products as $product) {
                $urls[] = [
                    'loc' => store_route('product', $product->slug),
                    'lastmod' => $product->updated_at?->toAtomString(),
                ];
            }
        });

        return response()->view('seo.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $private = ['/admin', '/cart', '/checkout', '/order/', '/bag/'];
        $lines = ['User-agent: *'];

        foreach ($private as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
