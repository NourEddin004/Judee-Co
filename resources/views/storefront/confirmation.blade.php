@extends('layouts.store', ['page' => 'confirmation', 'title' => __('store.confirmation.tab_title'), 'private' => true])

@section('main')
    {{-- The bag is emptied by the checkout script once the order is saved (REQ-DMO-74). --}}
    <div class="ds-section">
        <div class="ds-confirmation">
            <span class="ds-confirmation__icon" aria-hidden="true">{!! icon('check', 'ds-icon--lg') !!}</span>
            <header class="ds-confirmation__head">
                <p class="ds-eyebrow">{{ __('store.confirmation.kicker') }}</p>
                <h1 class="ds-confirmation__title">{{ __('store.confirmation.title') }}</h1>
                <p class="ds-confirmation__number">{{ __('store.confirmation.order') }} #{{ $order->number }}</p>
                <p class="ds-confirmation__note">{{ __('store.confirmation.keep_number') }}</p>
                @if ($order->email)
                    <p class="ds-confirmation__note">{{ __('store.confirmation.email_sent', ['email' => $order->email]) }}</p>
                @endif
            </header>

            <div class="ds-summary ds-confirmation__summary">
                <h2 class="ds-summary__title">{{ __('store.checkout.summary') }}</h2>
                <ul class="ds-summary__lines">
                    @foreach ($order->items as $item)
                        <li class="ds-summary__line">
                            @if ($photo = $item->productColor?->mainPhoto)
                                <img class="ds-summary__thumb" src="{{ $photo->smallUrl() }}" alt="" loading="lazy">
                            @endif
                            <span class="ds-summary__name">
                                {{ $item->product_name }} <span class="ds-summary__qty">{{ __('store.checkout.qty', ['qty' => $item->quantity]) }}</span>
                                @if ($item->size)
                                    <span class="ds-summary__size">{{ __('store.checkout.size', ['size' => $item->size]) }}</span>
                                @endif
                            </span>
                            <span>@include('storefront.partials.price', ['amount' => $item->lineTotal()->toDecimal()])</span>
                        </li>
                    @endforeach
                </ul>
                <div class="ds-summary__row"><span>{{ __('store.checkout.subtotal') }}</span><strong>@include('storefront.partials.price', ['amount' => $order->subtotal])</strong></div>
                <div class="ds-summary__row"><span>{{ __('store.checkout.delivery') }}</span><strong>@include('storefront.partials.price', ['amount' => $order->delivery_fee])</strong></div>
                <div class="ds-summary__row ds-summary__row--total"><span>{{ __('store.checkout.total') }}</span><strong>@include('storefront.partials.price', ['amount' => $order->total])</strong></div>
                <dl class="ds-confirmation__details">
                    <div class="ds-confirmation__detail"><dt>{{ __('store.confirmation.deliver_to') }}</dt><dd>{{ $order->deliveryAddress() }}</dd></div>
                    <div class="ds-confirmation__detail"><dt>{{ __('store.confirmation.phone') }}</dt><dd dir="ltr">{{ $order->phone }}</dd></div>
                    <div class="ds-confirmation__detail"><dt>{{ __('store.confirmation.payment') }}</dt><dd>{{ __('store.checkout.payment_method') }}</dd></div>
                </dl>
                {{-- The PDF receipt, in the language of the order (REQ-CHK-23, D.6.12). --}}
                <a href="{{ store_route('receipt', $order->link_token) }}" class="ds-btn ds-btn--secondary ds-btn--block" download data-receipt>
                    {{ __('store.confirmation.download_receipt') }} {!! icon('download') !!}
                </a>
                <a href="{{ store_route('shop') }}" class="ds-btn ds-btn--primary ds-btn--block ds-confirmation__continue">{{ __('store.confirmation.continue') }}</a>
            </div>
        </div>
    </div>
@endsection
