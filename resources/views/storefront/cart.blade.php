@extends('layouts.store', ['page' => 'cart', 'title' => __('store.cart.tab_title'), 'private' => true])
@inject('delivery', 'App\Domain\Delivery\DeliveryList')
@php($fees = $delivery->headlineFees())

@section('main')
    {{-- The bag lives in the browser; the script fills the lines and shows one of the two states (REQ-CHK-01). --}}
    <div class="ds-section" data-cart-page>
        <header class="ds-list-head">
            <h1 class="ds-list-head__title">{{ __('store.cart.title') }}</h1>
            <span class="ds-list-head__count" data-bag-count-label>{{ __('store.cart.count_many', ['count' => 0]) }}</span>
            <a href="{{ store_route('shop') }}" class="ds-list-head__link" hidden data-bag-filled>{{ __('store.cart.continue') }}</a>
        </header>
        <div data-bag-empty-page>
            @include('storefront.partials.empty', [
                'icon' => 'shopping-bag',
                'heading' => __('store.cart.empty_title'),
                'body' => __('store.cart.empty_body'),
                'action' => __('store.cart.go_to_shop'),
                'url' => store_route('shop'),
            ])
        </div>
        <div class="ds-cart" hidden data-bag-filled>
            <div class="ds-cart__lines">
                <div class="ds-cart__columns" aria-hidden="true">
                    <span>{{ __('store.cart.product') }}</span>
                    <span>{{ __('store.cart.quantity') }}</span>
                    <span>{{ __('store.cart.total') }}</span>
                </div>
                <div data-bag-lines="page"></div>
            </div>
            <aside class="ds-summary ds-summary--sticky">
                <h2 class="ds-summary__title">{{ __('store.cart.summary') }}</h2>
                @include('storefront.partials.free-delivery')
                <div class="ds-summary__row"><span>{{ __('store.cart.items_label') }}</span><strong data-bag-pieces>0</strong></div>
                <div class="ds-summary__row"><span>{{ __('store.cart.subtotal') }}</span><strong data-bag-subtotal>0 JD</strong></div>
                <div class="ds-summary__row"><span>{{ __('store.cart.delivery') }}</span><span>{{ $fees['gulf'] === null ? __('store.cart.delivery_note_jordan', ['jordan' => \App\Support\Money::jod($fees['jordan'])]) : __('store.cart.delivery_note', ['jordan' => \App\Support\Money::jod($fees['jordan']), 'gulf' => \App\Support\Money::jod($fees['gulf'])]) }}</span></div>
                <a href="{{ store_route('checkout') }}" class="ds-btn ds-btn--primary ds-btn--block">{{ __('store.cart.checkout') }}</a>
                <p class="ds-summary__note">{{ __('store.cart.note') }}</p>
            </aside>
        </div>
    </div>
@endsection
