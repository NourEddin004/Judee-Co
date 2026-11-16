{{-- The customer's order email (REQ-DMO-77). --}}
@use('App\Support\DesignTokens', 'Token')
@php
    $label = 'color:'.Token::color('ink-muted').';font-size:14px;padding:4px 0;vertical-align:top;';
    $value = 'font-size:14px;padding:4px 0 4px 16px;font-weight:600;';
@endphp
@component('mail.layout', ['title' => __('store.email.subject', ['number' => $order->number])])
    <p style="margin:0 0 16px;">{{ __('store.email.greeting', ['name' => $order->customer_name]) }}</p>
    <p style="margin:0;">{{ __('store.email.intro', ['number' => $order->number]) }}</p>
    <p style="margin:8px 0 0;">{{ __('store.confirmation.keep_number') }}</p>

    @include('mail.order-lines', ['order' => $order])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="{{ $label }}">{{ __('store.confirmation.deliver_to') }}</td><td style="{{ $value }}">{{ $order->customer_name }}{{ $order->comma() }}{{ $order->deliveryAddress() }}</td></tr>
        <tr><td style="{{ $label }}">{{ __('store.confirmation.phone') }}</td><td style="{{ $value }}" dir="ltr">{{ $order->phone }}</td></tr>
        <tr><td style="{{ $label }}">{{ __('store.confirmation.payment') }}</td><td style="{{ $value }}">{{ __('store.checkout.payment_method') }}</td></tr>
    </table>

    <p style="margin:24px 0 0;">{{ __('store.email.sign_off') }}<br>{{ __('store.email.signature') }}</p>
@endcomponent
