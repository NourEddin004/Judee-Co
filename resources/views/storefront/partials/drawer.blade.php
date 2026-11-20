{{-- The bag lives in the browser; the script fills the body and totals (REQ-CHK-01). --}}
<div class="ds-modal ds-modal--drawer" data-modal="cart-drawer" hidden>
    <div class="ds-drawer" id="cart-drawer" role="dialog" aria-modal="true" aria-label="{{ __('store.drawer.label') }}" data-cart-drawer>
        <div class="ds-drawer__head">
            <div>
                <h2 class="ds-drawer__title">{{ __('store.drawer.title') }}</h2>
                <p class="ds-drawer__meta" data-bag-meta>{{ __('store.drawer.items', ['count' => 0]) }}</p>
            </div>
            <button type="button" class="ds-icon-btn" aria-label="{{ __('store.drawer.close') }}" data-modal-close>{!! icon('x') !!}</button>
        </div>
        @include('storefront.partials.free-delivery')
        <div class="ds-drawer__body" data-bag-lines="drawer">
            <div class="ds-drawer__empty" data-bag-empty>
                {!! icon('shopping-bag', 'ds-icon--xl') !!}
                <h3 class="ds-drawer__empty-title">{{ __('store.drawer.empty_title') }}</h3>
                <p class="ds-drawer__empty-body">{{ __('store.drawer.empty_body') }}</p>
            </div>
        </div>
        <div class="ds-drawer__footer">
            <div class="ds-drawer__row">
                <span>{{ __('store.drawer.subtotal') }}</span>
                <strong data-bag-subtotal>0 JD</strong>
            </div>
            <a href="{{ store_route('checkout') }}" class="ds-btn ds-btn--primary ds-btn--block" data-bag-checkout aria-disabled="true">{{ __('store.drawer.checkout') }}</a>
            {{-- Closes the drawer and returns focus to the button that opened it (REQ-DMO-101). --}}
            <button type="button" class="ds-btn ds-btn--secondary ds-btn--block" data-modal-close>{{ __('store.drawer.continue') }}</button>
        </div>
    </div>
</div>
@include('storefront.partials.bag-line-template')
