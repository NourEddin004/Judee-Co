@inject('settings', 'App\Domain\Settings\StoreSettings')
@inject('content', 'App\Domain\Content\SiteContent')
@php
    $locale = app()->getLocale();
    $brand = $content->text('brand.full_name') ?: 'JUDE Boutique';
    $tabTitle = isset($title) && $title !== null
        ? __('store.page_title', ['title' => $title, 'brand' => $brand])
        : __('store.site_title', ['brand' => $brand, 'tagline' => $content->text('brand.tagline')]);
    // Settings the browser script needs; read from a JSON block, so no inline script runs (REQ-SEC-29).
    $config = [
        'locale' => $locale,
        'maxQuantity' => config('store.max_quantity'),
        'toastMs' => config('store.toast_ms'),
        'backToTopOffset' => config('store.back_to_top_offset'),
        'storage' => config('store.storage_keys'),
        'usdRate' => $settings->usdRate(),
        'routes' => [
            'check' => store_route('bag.check'),
            'cart' => store_route('cart'),
            'checkout' => store_route('checkout'),
            'shop' => store_route('shop'),
        ],
        'text' => [
            'pricePending' => __('store.price_pending'),
            'added' => __('store.toast.added'),
            'updated' => __('store.toast.updated'),
            'sizeFirst' => __('store.toast.size_first'),
            'item' => __('store.drawer.item'),
            'items' => __('store.drawer.items'),
            'cartItem' => __('store.cart.count_one'),
            'cartItems' => __('store.cart.count_many'),
            'size' => __('store.drawer.size'),
            'remove' => __('store.drawer.remove'),
            'decrease' => __('store.drawer.decrease'),
            'increase' => __('store.drawer.increase'),
            'maxQuantity' => __('store.drawer.max_quantity'),
            'qty' => __('store.checkout.qty'),
            'choose_city' => __('store.checkout.choose_city'),
            'choose_area' => __('store.checkout.choose_area'),
            'city_first' => __('store.checkout.city_first'),
            'area_other' => __('store.checkout.area_other'),
            'required' => __('store.checkout.required'),
            'invalid_city' => __('store.checkout.invalid_city'),
            'invalid_area' => __('store.checkout.invalid_area'),
            'invalid_email' => __('store.checkout.invalid_email'),
            'invalid_gulf_city' => __('store.checkout.invalid_gulf_city'),
            'invalid_gulf_area' => __('store.checkout.invalid_gulf_area'),
            'placing' => __('store.checkout.placing'),
            'bag_changed' => __('store.checkout.bag_changed'),
            'failed' => __('store.checkout.failed'),
            'currency' => __('store.header.currency'),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="is-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tabTitle }}</title>
    <meta name="description" content="{{ $description ?? ($content->text('seo.'.($page ?? '')) ?: $content->text('brand.description')) }}">
    @if (! empty($private))
        {{-- Bag, checkout and order pages stay out of search results; the order link never leaves the site. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="referrer" content="same-origin">
    @else
        <link rel="canonical" href="{{ canonical_url() }}">
    @endif
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('icon-192.png') }}" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @stack('preload')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="ds-body" data-page="{{ $page ?? 'page' }}">
    <script type="application/json" id="store-config">@json($config)</script>
    <a href="#main-content" class="ds-skip-link">{{ __('store.skip') }}</a>
    <div class="ds-page-shell" data-page-shell>
        @include('storefront.partials.header')
        <main id="main-content" class="ds-main" tabindex="-1">
            @yield('main')
        </main>
        @include('storefront.partials.footer')
    </div>
    @include('storefront.partials.menu')
    @include('storefront.partials.drawer')
    <button type="button" class="ds-back-to-top" data-back-to-top aria-label="{{ __('store.back_to_top') }}" hidden>{!! icon('arrow-up') !!}</button>
    <div class="ds-toast" role="status" aria-live="polite" data-toast>
        {!! icon('check') !!}
        <span data-toast-message></span>
    </div>
    @stack('templates')
</body>
</html>
