<?php

use App\Domain\Media\ImageStore;
use App\Http\Middleware\SetLocale;
use App\Support\Icons;
use Illuminate\Support\Facades\Route;

if (! function_exists('store_route')) {
    /**
     * A storefront URL. Both languages share one address (the language is the customer's cookie),
     * so $locale changes nothing; it is kept so callers can say which language they mean.
     */
    function store_route(string $name, mixed $parameters = [], ?string $locale = null): string
    {
        return route($name, $parameters);
    }
}

if (! function_exists('category_url')) {
    /**
     * A shop page: all products, or one category. The demo's categories keep their own
     * addresses (/sets, /hijabs, /bundles); categories the owner adds live at /{slug} (REQ-CMS-14).
     */
    function category_url(?string $slug, array $query = [], ?string $locale = null): string
    {
        if ($slug === null) {
            return store_route('shop', $query, $locale);
        }

        return Route::has($slug) ? store_route($slug, $query, $locale) : store_route('category', ['category' => $slug, ...$query], $locale);
    }
}

if (! function_exists('product_url')) {
    /** A product page, opening on one of its colors: /product/wrap-set?color=navy (REQ-DMO-98). */
    function product_url(string $slug, ?string $color = null, ?string $locale = null): string
    {
        return store_route('product', array_filter(['slug' => $slug, 'color' => $color]), $locale);
    }
}

if (! function_exists('switch_language_url')) {
    /**
     * The language button: this same address with ?lang=ar or ?lang=en added. The server saves the
     * choice and sends the browser back to the address without ?lang (REQ-STR-20, REQ-DMO-84).
     */
    function switch_language_url(): string
    {
        $target = app()->getLocale() === 'ar' ? 'en' : 'ar';
        $current = SetLocale::withoutLang(request());

        return $current.(str_contains($current, '?') ? '&' : '?').'lang='.$target;
    }
}

if (! function_exists('canonical_url')) {
    /**
     * This page's address for search engines: the path and the page number only, so filtered and
     * searched lists, and each color of a product, point at their page (search-engine setup, SRS 6.1).
     * Both languages share it.
     */
    function canonical_url(): string
    {
        $route = Route::current();
        $name = $route?->getName();

        if ($name === null || ! Route::has($name)) {
            return url('/');
        }

        $parameters = array_intersect_key($route->parameters(), array_flip($route->parameterNames()));
        $page = (int) request()->query('page', '1');

        if ($page > 1) {
            $parameters['page'] = $page;
        }

        return store_route($name, $parameters);
    }
}

if (! function_exists('icon')) {
    function icon(string $name, string $class = ''): string
    {
        return Icons::svg($name, $class);
    }
}

if (! function_exists('srcset')) {
    /** srcset for a stored image, listing its resized copies (REQ-DB-15). */
    function srcset(?string $path): string
    {
        return app(ImageStore::class)->srcset($path);
    }
}
