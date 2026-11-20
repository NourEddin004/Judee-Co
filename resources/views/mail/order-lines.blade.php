{{-- The order's lines and totals, shared by both order emails. --}}
@use('App\Support\DesignTokens', 'Token')
@use('App\Support\Money')
@php
    $end = app()->getLocale() === 'ar' ? 'left' : 'right';
    $row = 'padding:8px 0;border-top:1px solid '.Token::color('blush-100').';font-size:14px;';
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
    @foreach ($order->items as $item)
        <tr>
            <td style="{{ $row }}">
                {{ $item->product_name }}
                @if ($item->size)
                    ({{ __('store.checkout.size', ['size' => $item->size]) }})
                @endif
                <span style="color:{{ Token::color('ink-muted') }};">{{ __('store.checkout.qty', ['qty' => $item->quantity]) }}</span>
            </td>
            <td style="{{ $row }}text-align:{{ $end }};white-space:nowrap;" dir="ltr">{{ $item->lineTotal()->format() }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="{{ $row }}">{{ __('store.checkout.subtotal') }}</td>
        <td style="{{ $row }}text-align:{{ $end }};" dir="ltr">{{ Money::jod($order->subtotal) }}</td>
    </tr>
    <tr>
        <td style="{{ $row }}">{{ __('store.checkout.delivery') }}</td>
        <td style="{{ $row }}text-align:{{ $end }};" dir="ltr">{{ Money::fee($order->delivery_fee) }}</td>
    </tr>
    <tr>
        <td style="{{ $row }}font-weight:600;">{{ __('store.checkout.total') }}</td>
        <td style="{{ $row }}text-align:{{ $end }};font-weight:600;" dir="ltr">{{ Money::jod($order->total) }}</td>
    </tr>
</table>
