@inject('settings', 'App\Domain\Settings\StoreSettings')
@inject('content', 'App\Domain\Content\SiteContent')
@php
    $columns = [
        __('store.footer.shop') => ['shop' => __('store.footer.all'), 'sets' => __('store.footer.sets'), 'hijabs' => __('store.footer.hijabs'), 'bundles' => __('store.footer.bundles')],
        __('store.footer.help') => ['track' => __('store.track.nav'), 'contact' => __('store.footer.contact'), 'terms' => __('store.footer.terms')],
        __('store.footer.bag') => ['cart' => __('store.footer.cart')],
    ];
    $phone = $settings->get('phone_display');
@endphp
<footer class="ds-footer">
    <div class="ds-footer__grid">
        <div class="ds-footer__brand">
            <a href="{{ store_route('home') }}" class="ds-footer__logo" aria-label="{{ __('store.header.home_link', ['brand' => $content->text('brand.full_name')]) }}">@include('storefront.partials.brand')</a>
            <a href="{{ $settings->get('instagram_url') }}" target="_blank" rel="noreferrer" class="ds-footer__link ds-footer__link--brand">
                <img class="ds-footer__brand-icon" src="{{ asset('images/instagram.svg') }}" alt=""> <span dir="ltr">{{ $settings->get('instagram_handle') }}</span>
            </a>
            <a href="https://wa.me/{{ $settings->get('whatsapp_international') }}" target="_blank" rel="noreferrer" class="ds-footer__link ds-footer__link--brand" aria-label="{{ __('store.whatsapp') }} {{ $phone }}">
                <img class="ds-footer__brand-icon" src="{{ asset('images/whatsapp.svg') }}" alt=""> <span dir="ltr">{{ $phone }}</span>
            </a>
        </div>
        <nav class="ds-footer__nav" aria-label="{{ __('store.footer.nav') }}">
            @foreach ($columns as $heading => $links)
                <div class="ds-footer__column">
                    <h2 class="ds-footer__heading">{{ $heading }}</h2>
                    <ul class="ds-footer__list">
                        @foreach ($links as $route => $label)
                            <li><a href="{{ store_route($route) }}" class="ds-footer__link">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
    </div>
    <div class="ds-footer__bottom">
        <div class="ds-footer__legal">
            <p>{{ __('store.footer.copyright') }}</p>
            <p>{{ __('store.footer.legal') }}</p>
        </div>
        <div class="rt-badge-row rt-badge-row--store">
            @include('partials.rafaqtech-badge')
        </div>
    </div>
</footer>
