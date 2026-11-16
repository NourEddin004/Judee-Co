@extends('layouts.store', ['page' => 'checkout', 'title' => __('store.checkout.tab_title'), 'private' => true])
@inject('delivery', 'App\Domain\Delivery\DeliveryList')
@inject('settings', 'App\Domain\Settings\StoreSettings')
@php
    $fees = $delivery->headlineFees();
    $countries = config('store.phone_countries');
    $default = $countries[0];
    $areas = $cities->mapWithKeys(fn ($city) => [$city->key => $city->areas->map(fn ($area) => $area->t('name'))->values()])->all();
    $paused = $settings->isPaused();
@endphp

@section('main')
    {{-- An empty bag goes to the bag page (D.6.6); the script checks, since the bag lives in the browser. --}}
    <div class="ds-section" data-checkout-page>
        <header class="ds-page-heading ds-page-heading--start">
            <p class="ds-eyebrow">{{ __('store.checkout.kicker') }}</p>
            <h1 class="ds-page-heading__title">{{ __('store.checkout.title') }}</h1>
            <p class="ds-page-heading__lead">{{ $fees['gulf'] === null ? __('store.checkout.lead_jordan', ['jordan' => \App\Support\Money::jod($fees['jordan'])]) : __('store.checkout.lead', ['jordan' => \App\Support\Money::jod($fees['jordan']), 'gulf' => \App\Support\Money::jod($fees['gulf'])]) }}</p>
        </header>

        <div class="ds-checkout-layout">
            <form class="ds-checkout" method="post" novalidate data-checkout action="{{ store_route('checkout.place') }}">
                {{-- Posted, never sent as a GET, so the customer's details stay out of addresses and logs even before the script loads. --}}
                @csrf
                <p class="ds-checkout__alert" id="checkout-alert" role="alert" tabindex="-1" hidden data-checkout-alert>{{ __('store.checkout.error_summary') }}</p>

                <fieldset class="ds-checkout__group">
                    <legend class="ds-checkout__legend">{{ __('store.checkout.contact') }}</legend>
                    <div class="ds-checkout__grid">
                        <label class="ds-field ds-checkout__wide">
                            <span class="ds-field__label">{{ __('store.checkout.name') }}</span>
                            <input class="ds-field__input" name="full_name" autocomplete="name" required maxlength="120" aria-invalid="false" data-field-name="{{ mb_strtolower(__('store.checkout.name')) }}">
                            <span class="ds-field__error" hidden data-error-for="full_name"></span>
                        </label>

                        <div class="ds-field">
                            <label class="ds-field__label" for="phone">{{ __('store.checkout.phone') }}</label>
                            <div class="ds-phone" dir="ltr">
                                <label class="ds-phone__country">
                                    <span class="ds-phone__flag" aria-hidden="true" data-phone-flag>{{ $default['flag'] }}</span>
                                    <span class="ds-phone__dial" aria-hidden="true" data-phone-dial>+{{ $default['dial'] }}</span>
                                    {!! icon('chevron-down', 'ds-icon--sm') !!}
                                    <select class="ds-phone__select" name="country" aria-label="{{ __('store.checkout.country') }}" data-phone-country>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country['code'] }}" @selected($loop->first) data-flag="{{ $country['flag'] }}" data-dial="{{ $country['dial'] }}" data-digits="{{ implode(' ', $country['digits']) }}" data-placeholder="{{ $country['placeholder'] }}" data-invalid="{{ \App\Support\Phone::invalidMessage($country) }}">{{ $country['flag'] }} {{ __('store.countries.'.$country['code']) }} (+{{ $country['dial'] }})</option>
                                        @endforeach
                                    </select>
                                </label>
                                <input id="phone" class="ds-phone__input" name="phone" type="tel" placeholder="{{ $default['placeholder'] }}" autocomplete="tel-national" inputmode="numeric" required
                                       aria-invalid="false" aria-describedby="phone-hint" data-phone-input data-field-name="{{ mb_strtolower(__('store.checkout.phone')) }}">
                            </div>
                            <span class="ds-field__hint" id="phone-hint" data-hint-for="phone">{{ __('store.checkout.phone_hint') }}</span>
                            <span class="ds-field__error" hidden data-error-for="phone"></span>
                        </div>

                        <label class="ds-field">
                            <span class="ds-field__label">{{ __('store.checkout.email') }} <span class="ds-field__optional">{{ __('store.checkout.optional') }}</span></span>
                            <input class="ds-field__input" name="email" type="email" placeholder="{{ __('store.checkout.email_placeholder') }}" autocomplete="email" maxlength="190" aria-invalid="false">
                            <span class="ds-field__error" hidden data-error-for="email"></span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="ds-checkout__group">
                    <legend class="ds-checkout__legend">{{ __('store.checkout.address_group') }}</legend>
                    <div class="ds-checkout__grid">
                        {{-- Jordan, or a Gulf country (REQ-CHK-26). Shown only while a Gulf country is switched on. --}}
                        <label class="ds-field ds-checkout__wide" @if ($gulf->isEmpty()) hidden @endif>
                            <span class="ds-field__label">{{ __('store.checkout.destination') }}</span>
                            <span class="ds-field__select">
                                <select class="ds-field__input" name="destination" autocomplete="country-name" aria-invalid="false" data-destination>
                                    <option value="{{ \App\Http\Requests\PlaceOrderRequest::JORDAN }}" selected>{{ __('store.checkout.jordan') }}</option>
                                    @if ($gulf->isNotEmpty())
                                        <optgroup label="{{ __('store.checkout.gulf_group') }}">
                                            @foreach ($gulf as $country)
                                                <option value="{{ $country->key }}" data-fee="{{ $country->fee }}">{{ $country->t('name') }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                {!! icon('chevron-down', 'ds-icon--sm') !!}
                            </span>
                            <span class="ds-field__error" hidden data-error-for="destination"></span>
                        </label>

                        {{-- A Gulf country: the customer types the city and area (REQ-CHK-28). --}}
                        <label class="ds-field" hidden data-gulf-field>
                            <span class="ds-field__label">{{ __('store.checkout.gulf_city') }}</span>
                            <input class="ds-field__input" name="gulf_city" placeholder="{{ __('store.checkout.gulf_city_placeholder') }}" autocomplete="address-level2" minlength="2" maxlength="80" aria-invalid="false">
                            <span class="ds-field__error" hidden data-error-for="gulf_city"></span>
                        </label>
                        <label class="ds-field" hidden data-gulf-field>
                            <span class="ds-field__label">{{ __('store.checkout.gulf_area') }}</span>
                            <input class="ds-field__input" name="gulf_area" placeholder="{{ __('store.checkout.gulf_area_placeholder') }}" autocomplete="address-level3" minlength="2" maxlength="80" aria-invalid="false">
                            <span class="ds-field__error" hidden data-error-for="gulf_area"></span>
                        </label>

                        <label class="ds-field" data-jordan-field>
                            <span class="ds-field__label">{{ __('store.checkout.city') }}</span>
                            <span class="ds-field__select">
                                <select class="ds-field__input" name="city" autocomplete="address-level2" required aria-invalid="false" data-city-select>
                                    <option value="">{{ __('store.checkout.choose_city') }}</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->key }}" data-fee="{{ $city->fee }}">{{ $city->t('name') }}</option>
                                    @endforeach
                                </select>
                                {!! icon('chevron-down', 'ds-icon--sm') !!}
                            </span>
                            <span class="ds-field__error" hidden data-error-for="city"></span>
                        </label>

                        <div class="ds-field" data-jordan-field>
                            <span class="ds-field__label" id="area-label">{{ __('store.checkout.area') }}</span>
                            <input type="hidden" name="area" data-area-value>
                            <div class="ds-picker" data-area-picker>
                                <button type="button" class="ds-picker__trigger" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="area-label area-picker-value" aria-invalid="false" disabled data-picker-trigger>
                                    <span class="ds-picker__value" id="area-picker-value" data-picker-value>{{ __('store.checkout.city_first') }}</span>
                                    {!! icon('chevron-down', 'ds-icon--sm') !!}
                                </button>
                                <div class="ds-picker__panel" hidden data-picker-panel>
                                    <label class="ds-picker__search">
                                        {!! icon('search', 'ds-icon--sm') !!}
                                        <input class="ds-picker__search-input" type="search" placeholder="{{ __('store.checkout.search_area') }}" aria-label="{{ __('store.checkout.search_area') }}" autocomplete="off" aria-controls="area-list" data-picker-search>
                                    </label>
                                    <ul class="ds-picker__list" id="area-list" role="listbox" aria-labelledby="area-label" data-picker-list></ul>
                                    <p class="ds-picker__empty" hidden data-picker-empty>{{ __('store.checkout.no_area') }}</p>
                                </div>
                            </div>
                            <span class="ds-field__error" hidden data-error-for="area"></span>
                        </div>

                        <label class="ds-field ds-checkout__wide" hidden data-area-other>
                            <span class="ds-field__label">{{ __('store.checkout.area_other_label') }}</span>
                            <input class="ds-field__input" name="area_other" placeholder="{{ __('store.checkout.area_other_placeholder') }}" autocomplete="address-level3" maxlength="120" aria-invalid="false">
                            <span class="ds-field__error" hidden data-error-for="area_other"></span>
                        </label>

                        <label class="ds-field ds-checkout__wide">
                            <span class="ds-field__label">{{ __('store.checkout.address') }}</span>
                            <input class="ds-field__input" name="address" placeholder="{{ __('store.checkout.address_placeholder') }}" autocomplete="street-address" required maxlength="255" aria-invalid="false" data-field-name="{{ mb_strtolower(__('store.checkout.address')) }}">
                            <span class="ds-field__error" hidden data-error-for="address"></span>
                        </label>

                        {{-- The customer's note for the order (REQ-ORD-12). --}}
                        <label class="ds-field ds-checkout__wide">
                            <span class="ds-field__label">{{ __('store.checkout.note') }} <span class="ds-field__optional">{{ __('store.checkout.optional') }}</span></span>
                            <textarea class="ds-field__input ds-field__input--area" name="note" rows="2" maxlength="500" placeholder="{{ __('store.checkout.note_placeholder') }}" aria-invalid="false"></textarea>
                            <span class="ds-field__error" hidden data-error-for="note"></span>
                        </label>

                        {{-- Anti-spam: people never see this field, so only bots fill it in (REQ-SEC-06). --}}
                        <label class="ds-checkout__trap" aria-hidden="true">
                            Website
                            <input name="website" tabindex="-1" autocomplete="off">
                        </label>
                    </div>
                </fieldset>

                <button type="submit" class="ds-btn ds-btn--primary ds-btn--block" data-place-order @disabled($paused)
                        @if ($paused) aria-describedby="place-order-reason" @endif>
                    <span class="ds-btn__label">{{ __('store.checkout.place_order') }}</span>
                    <span class="ds-btn__spinner" aria-hidden="true"></span>
                </button>
                {{-- A disabled button says why, right below it (REQ-UX-27). --}}
                <p class="ds-disabled-reason" id="place-order-reason" @unless ($paused) hidden @endunless data-place-order-reason>{{ __('store.paused.reason') }}</p>
                <p class="ds-visually-hidden" role="status" aria-live="polite" data-place-order-status></p>
                <script type="application/json" data-areas>@json($areas)</script>
            </form>

            <aside class="ds-summary ds-checkout-layout__summary" aria-labelledby="summary-title">
                <h2 class="ds-summary__title" id="summary-title">{{ __('store.checkout.summary') }}</h2>
                <ul class="ds-summary__lines" data-checkout-lines></ul>
                <div class="ds-summary__row"><span>{{ __('store.checkout.subtotal') }}</span><strong data-bag-subtotal>0 JD</strong></div>
                <div class="ds-summary__row">
                    <span>{{ __('store.checkout.delivery') }}</span>
                    <strong data-checkout-shipping>{{ __('store.checkout.choose_city') }}</strong>
                </div>
                <div class="ds-summary__row ds-summary__row--total">
                    <span>{{ __('store.checkout.total') }}</span>
                    <strong data-checkout-total>0 JD</strong>
                </div>
                <div class="ds-pay-note">
                    <p class="ds-pay-note__title">{!! icon('check', 'ds-icon--sm') !!} {{ __('store.checkout.payment') }}: {{ __('store.checkout.payment_method') }}</p>
                    <p class="ds-pay-note__body">{{ __('store.checkout.payment_note') }} <a class="ds-pay-note__link" href="{{ store_route('terms') }}#delivery">{{ __('store.checkout.payment_link') }}</a></p>
                </div>
            </aside>
        </div>
    </div>

    @push('templates')
        <template id="summary-line-template">
            <li class="ds-summary__line">
                <img class="ds-summary__thumb" src="" alt="" loading="lazy" data-line-image>
                <span class="ds-summary__name">
                    <span data-line-name></span> <span class="ds-summary__qty" data-line-qty></span>
                    <span class="ds-summary__size" data-line-size hidden></span>
                </span>
                <span data-line-total></span>
            </li>
        </template>
    @endpush
@endsection
