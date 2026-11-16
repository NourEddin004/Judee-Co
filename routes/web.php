<?php

use App\Http\Controllers\Storefront\BagController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchEngineController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
| One address per page for both languages: the language comes from the customer's cookie
| (SetLocale), so the language button never changes the address.
*/
$storefront = function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/shop', [ShopController::class, 'show'])->defaults('category', null)->name('shop');
    Route::get('/sets', [ShopController::class, 'show'])->defaults('category', 'sets')->name('sets');
    Route::get('/hijabs', [ShopController::class, 'show'])->defaults('category', 'hijabs')->name('hijabs');
    Route::get('/bundles', [ShopController::class, 'show'])->defaults('category', 'bundles')->name('bundles');

    Route::get('/product/{slug}', ProductController::class)->where('slug', '[a-z0-9-]+')->name('product');

    Route::get('/cart', [PageController::class, 'cart'])->name('cart');
    Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/order', fn () => redirect()->to(store_route('shop')));
    Route::get('/order/{token}', [CheckoutController::class, 'confirmation'])->where('token', '[A-Za-z0-9]{1,64}')->name('confirmation');
    // The PDF receipt, by the same random token (REQ-SEC-43); making a PDF is work, so it is rate limited.
    Route::get('/order/{token}/receipt', [CheckoutController::class, 'receipt'])->where('token', '[A-Za-z0-9]{1,64}')
        ->middleware('throttle:storefront')->name('receipt');

    // Endpoints the storefront's own scripts use (REQ-SEC-33): 60 requests a minute per IP
    // (REQ-SEC-34), from the store's own pages only (REQ-SEC-40).
    Route::middleware(['throttle:storefront', 'same-origin'])->group(function () {
        Route::post('/bag/check', [BagController::class, 'check'])->name('bag.check');
        Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
    });
};

Route::middleware('locale')->group($storefront);

/*
| The old Arabic addresses (/ar/...) switch the language to Arabic and open the same page at its
| one address. A temporary redirect, because it also saves the customer's language choice.
*/
Route::get('ar/{path?}', function (?string $path = null) {
    $query = request()->server('QUERY_STRING');

    return redirect()->to(url($path ?? '/').($query ? '?'.$query : ''), 302)
        ->withCookie(SetLocale::cookie('ar'));
})->where('path', '.*')->name('arabic.redirect');

// Search-engine setup (SRS 6.1): one sitemap and robots.txt for both languages.
Route::get('sitemap.xml', [SearchEngineController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SearchEngineController::class, 'robots'])->name('robots');

/*
| Demo addresses keep working (Appendix D.6.11).
*/
foreach ([
    'index.html' => 'home', 'shop.html' => 'shop', 'sets.html' => 'sets', 'hijabs.html' => 'hijabs',
    'bundles.html' => 'bundles', 'cart.html' => 'cart', 'checkout.html' => 'checkout',
    'contact.html' => 'contact', 'terms.html' => 'terms', 'confirmation.html' => 'shop',
] as $old => $name) {
    Route::get($old, fn () => redirect()->to(route($name).(request()->getQueryString() ? '?'.request()->getQueryString() : ''), 301));
}
Route::get('product.html', fn () => request()->filled('slug')
    ? redirect()->route('product', ['slug' => request()->string('slug')->lower()->value()], 301)
    : redirect()->route('shop', status: 301));
Route::get('search', fn () => redirect()->route('shop', request()->filled('q') ? ['q' => request('q')] : [], 301));
Route::get('search.html', fn () => redirect()->route('shop', request()->filled('q') ? ['q' => request('q')] : [], 301));

/*
| Categories the owner adds in the admin live at /{slug} (REQ-CMS-14). Registered last, so every
| fixed page and demo address above wins.
*/
$categories = fn () => Route::get('/{category}', [ShopController::class, 'show'])
    ->where('category', '(?!(?:ar|admin|up|storage|build|fonts|images)$|livewire)[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('category');

Route::middleware('locale')->group($categories);
