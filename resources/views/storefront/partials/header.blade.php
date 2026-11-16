@inject('content', 'App\Domain\Content\SiteContent')
<header class="ds-header">
    <div class="ds-header__bar">
        <button type="button" class="ds-icon-btn ds-header__menu" aria-label="{{ __('store.header.open_menu') }}" aria-controls="mobile-menu" aria-expanded="false" data-modal-open="mobile-menu">
            {!! icon('menu') !!}
        </button>
        <a href="{{ store_route('home') }}" class="ds-header__logo" aria-label="{{ __('store.header.home_link', ['brand' => $content->text('brand.full_name')]) }}">@include('storefront.partials.brand')</a>
        <nav class="ds-header__nav" aria-label="{{ __('store.header.main_nav') }}">@include('storefront.partials.nav', ['class' => 'ds-header__link'])</nav>
        <div class="ds-header__tools">
            @include('storefront.partials.language', ['class' => 'ds-text-btn ds-header__desktop-only'])
            <div class="ds-dropdown ds-header__desktop-only" data-dropdown>
                <button type="button" class="ds-text-btn ds-currency__toggle" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('store.header.currency') }}: JOD, {{ __('store.currencies.JOD') }}" data-dropdown-toggle data-currency-toggle>
                    <span data-currency-code>JOD</span> {!! icon('chevron-down', 'ds-icon--sm') !!}
                </button>
                <div class="ds-dropdown__menu ds-dropdown__menu--end" role="menu" hidden data-dropdown-menu>
                    @foreach (['JOD', 'USD'] as $code)
                        <button type="button" role="menuitemradio" aria-checked="{{ $code === 'JOD' ? 'true' : 'false' }}" class="ds-dropdown__item" data-currency="{{ $code }}" data-currency-name="{{ __('store.currencies.'.$code) }}">
                            <span>{{ $code }} <span class="ds-currency__name">{{ __('store.currencies.'.$code) }}</span></span>
                            <span class="ds-dropdown__check" data-currency-check>{!! icon('check', 'ds-icon--sm') !!}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <a href="{{ store_route('cart') }}" class="ds-icon-btn" aria-label="{{ __('store.header.open_bag') }}" data-count-label="cart" aria-controls="cart-drawer" aria-expanded="false" data-modal-open="cart-drawer">
                {!! icon('shopping-bag') !!}
                <span class="ds-count" data-count="cart" hidden>0</span>
            </a>
        </div>
    </div>
</header>
