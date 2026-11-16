{{-- The owner's new-order email (REQ-CHK-18). --}}
@use('App\Support\DesignTokens', 'Token')
@php
    $label = 'color:'.Token::color('ink-muted').';font-size:14px;padding:4px 0;vertical-align:top;width:120px;';
    $value = 'font-size:14px;padding:4px 0 4px 16px;font-weight:600;';
@endphp
@component('mail.layout', ['title' => 'New order #'.$order->number])
    <p style="margin:0 0 4px;font-size:20px;">New order #{{ $order->number }}</p>
    <p style="margin:0 0 16px;color:{{ Token::color('ink-muted') }};font-size:14px;">Placed {{ $order->placed_at->timezone('Asia/Amman')->format('j M Y, H:i') }} (Jordan time). Call the customer to confirm it.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="{{ $label }}">Customer</td><td style="{{ $value }}">{{ $order->customer_name }}</td></tr>
        <tr><td style="{{ $label }}">Phone</td><td style="{{ $value }}" dir="ltr">{{ $order->phone }}</td></tr>
        @if ($order->email)
            <tr><td style="{{ $label }}">Email</td><td style="{{ $value }}">{{ $order->email }}</td></tr>
        @endif
        <tr><td style="{{ $label }}">Deliver to</td><td style="{{ $value }}">{{ $order->deliveryAddress() }}</td></tr>
        @if ($order->note)
            <tr><td style="{{ $label }}">Note</td><td style="{{ $value }}">{{ $order->note }}</td></tr>
        @endif
        <tr><td style="{{ $label }}">Payment</td><td style="{{ $value }}">Cash on delivery</td></tr>
        <tr><td style="{{ $label }}">Language</td><td style="{{ $value }}">{{ $order->language === 'ar' ? 'Arabic' : 'English' }}</td></tr>
    </table>

    @include('mail.order-lines', ['order' => $order])

    <p style="margin:24px 0 0;">
        <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 24px;border-radius:8px;background:{{ Token::color('rose') }};color:{{ Token::color('on-brand') }};font-weight:600;text-decoration:none;">Open the order</a>
    </p>
@endcomponent
