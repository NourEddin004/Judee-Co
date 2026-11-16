<div class="ds-modal ds-modal--menu" data-modal="mobile-menu" hidden>
    <div class="ds-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="{{ __('store.header.mobile_nav') }}">
        <div class="ds-menu__head">
            @include('storefront.partials.brand')
            <button type="button" class="ds-icon-btn" aria-label="{{ __('store.header.close_menu') }}" data-modal-close>{!! icon('x') !!}</button>
        </div>
        <nav class="ds-menu__nav" aria-label="{{ __('store.header.main_nav') }}">@include('storefront.partials.nav', ['class' => 'ds-menu__link'])</nav>
        <div class="ds-menu__utilities">
            <a href="{{ store_route('cart') }}" class="ds-menu__utility">{!! icon('shopping-bag') !!} {{ __('store.header.cart') }} <span class="ds-menu__count" data-count-text="cart">0</span></a>
        </div>
        <div class="ds-menu__languages">
            @include('storefront.partials.language', ['class' => 'ds-menu__language'])
            <div class="ds-menu__currency" role="group" aria-label="{{ __('store.header.currency') }}">
                @foreach (['JOD', 'USD'] as $code)
                    <button type="button" class="ds-menu__currency-option" aria-pressed="{{ $code === 'JOD' ? 'true' : 'false' }}" title="{{ __('store.currencies.'.$code) }}" data-currency="{{ $code }}">{{ $code }}</button>
                @endforeach
            </div>
        </div>
    </div>
</div>
