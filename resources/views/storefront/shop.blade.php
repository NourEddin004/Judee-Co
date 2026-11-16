@php
    $category = $result->category;
    $categoryTotal = $category ? (int) ($counts[$category->id] ?? 0) : (int) $counts->sum();
    $heading = $category ? $category->t('name') : __('store.shop.all_title');
    $tabTitle = $category ? ($category->t('page_title') ?: $heading) : __('store.shop.all_page_title');
    $clearUrl = category_url($categorySlug);
    $hasProducts = $categoryTotal > 0;
@endphp
@extends('layouts.store', ['page' => 'shop', 'title' => $tabTitle])

@section('main')
    <section class="ds-collection-intro">
        <h1 class="ds-collection-intro__title">{{ $heading }}</h1>
    </section>

    <div class="ds-shop {{ $hasProducts ? '' : 'ds-shop--empty' }}">
        {{-- Filter panel (D.5.7). From 1024 px each change applies at once; below, on "Apply filters". --}}
        <form id="shop-filters" class="ds-filters" action="{{ $clearUrl }}" method="get" aria-label="{{ __('store.shop.product_filters') }}" @if (! $hasProducts) hidden @endif data-filters>
            <div class="ds-filters__head">
                <h2 class="ds-filters__title">{{ __('store.shop.filters') }}</h2>
                <a href="{{ $clearUrl }}" class="ds-filters__clear">{{ __('store.shop.clear') }}</a>
                <button type="button" class="ds-icon-btn ds-filters__close" aria-label="{{ __('store.shop.close_filters') }}" data-filters-close>{!! icon('x') !!}</button>
            </div>
            <div class="ds-filters__scroll">
                @if ($result->availableShades->isNotEmpty())
                    <fieldset class="ds-filters__section">
                        <legend class="ds-filters__label">{{ __('store.shop.color') }}</legend>
                        <label class="ds-option">
                            <input class="ds-option__control" type="checkbox" @checked($result->selectedShades === []) data-filter-all-shades>
                            <span>{{ __('store.shop.all_colors') }}</span>
                        </label>
                        @foreach ($result->availableShades as $shade)
                            <label class="ds-option">
                                <input class="ds-option__control" type="checkbox" name="shade[]" value="{{ $shade->key }}" @checked(in_array($shade->key, $result->selectedShades, true)) data-filter-shade>
                                @include('storefront.partials.swatch', ['shade' => $shade, 'class' => 'ds-swatch--sm'])
                                <span>{{ $shade->t('name') }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                {{-- Only the sizes of this category's products, in size order; no section when none has sizes (REQ-DMO-92). --}}
                @if ($result->availableSizes->isNotEmpty())
                    <fieldset class="ds-filters__section">
                        <legend class="ds-filters__label">{{ __('store.shop.size') }}</legend>
                        <label class="ds-option">
                            <input class="ds-option__control" type="checkbox" @checked($result->selectedSizes === []) data-filter-all-sizes>
                            <span>{{ __('store.shop.all_sizes') }}</span>
                        </label>
                        @foreach ($result->availableSizes as $size)
                            <label class="ds-option">
                                <input class="ds-option__control" type="checkbox" name="size[]" value="{{ mb_strtolower($size->label) }}" @checked(in_array($size->label, $result->selectedSizes, true)) data-filter-size>
                                <span>{{ $size->label }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            </div>
            <div class="ds-filters__actions">
                <a href="{{ $clearUrl }}" class="ds-btn ds-btn--secondary">{{ __('store.shop.clear_all') }}</a>
                <button type="submit" class="ds-btn ds-btn--primary">{{ __('store.shop.apply_filters') }}</button>
            </div>
        </form>

        <section class="ds-results" id="results">
            @if ($hasProducts)
                <div class="ds-results__toolbar">
                    <div class="ds-results__search" role="search">
                        <label class="ds-searchbar">
                            {!! icon('search') !!}
                            <input class="ds-searchbar__input" type="search" name="q" value="{{ $result->search }}" placeholder="{{ __('store.shop.search_products') }}" aria-label="{{ __('store.shop.search_products') }}" autocomplete="off" form="shop-filters">
                        </label>
                        <button type="submit" class="ds-btn ds-btn--primary ds-searchbar__submit" form="shop-filters">{{ __('store.shop.search') }}</button>
                    </div>
                    {{-- Switching tabs keeps the search and clears the colours and sizes (REQ-DMO-21, 22). --}}
                    <nav class="ds-category-tabs" aria-label="{{ __('store.shop.categories') }}">
                        <a href="{{ store_route('shop', $result->query(['shade' => '', 'size' => '', 'page' => 1])) }}" class="ds-category-tabs__tab" @if (! $category) aria-current="page" @endif>
                            {{ __('store.shop.all_tab') }} <span class="ds-category-tabs__count">{{ $counts->sum() }}</span>
                        </a>
                        @foreach ($categories as $option)
                            <a href="{{ category_url($option->slug, $result->query(['shade' => '', 'size' => '', 'page' => 1])) }}" class="ds-category-tabs__tab" @if ($category?->is($option)) aria-current="page" @endif>
                                {{ $option->t('name') }} <span class="ds-category-tabs__count">{{ $counts[$option->id] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </nav>
                    <div class="ds-results__meta">
                        @if ($result->search !== '')
                            <a class="ds-results__chip" href="{{ category_url($categorySlug, $result->query(['q' => '', 'page' => 1])) }}" aria-label="{{ __('store.shop.clear_search', ['text' => $result->search]) }}">“{{ $result->search }}” {!! icon('x', 'ds-icon--sm') !!}</a>
                        @endif
                        <button type="button" class="ds-results__filters-btn" aria-controls="shop-filters" aria-expanded="false" data-filters-open>
                            {!! icon('sliders') !!} {{ __('store.shop.filters') }}
                            @if ($result->filterCount() > 0)
                                <span class="ds-results__filters-count">{{ $result->filterCount() }}</span>
                            @endif
                        </button>
                    </div>
                </div>
            @endif

            @if ($result->products->isEmpty())
                @if (! $hasProducts)
                    @include('storefront.partials.empty', [
                        'icon' => 'sparkles',
                        'heading' => $category?->t('empty_title') ?: __('store.shop.empty_title'),
                        'body' => $category?->t('empty_body') ?: '',
                        'action' => __('store.shop.shop_collection'),
                        'url' => store_route('shop'),
                    ])
                @else
                    @include('storefront.partials.empty', [
                        'icon' => 'search',
                        'heading' => __('store.shop.empty_title'),
                        'body' => __('store.shop.empty_body'),
                        'action' => __('store.shop.clear_filters'),
                        'url' => $clearUrl,
                    ])
                @endif
            @else
                <div class="ds-grid ds-grid--catalog">
                    {{-- The first row on a phone. On a computer the next two load as soon as the page is laid out. --}}
                    @include('storefront.partials.card-list', ['products' => $result->products, 'eager' => 2])
                </div>
                @include('storefront.partials.pagination')
            @endif
        </section>
    </div>
@endsection
