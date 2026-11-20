{{-- The order's PDF receipt (Appendix D.6.12), drawn by mPDF: tables for layout, sizes in pt and mm. --}}
@use('App\Support\Money')
@php
    $start = $rtl ? 'right' : 'left';
    $end = $rtl ? 'left' : 'right';
    $body = $rtl ? 'plexarabic' : 'montserrat';
    $semibold = $rtl ? 'plexarabicsemibold' : 'montserratsemibold';
    $title = $rtl ? 'plexarabic' : 'merriweatherlight';
    $placed = $order->placed_at->timezone('Asia/Amman')->locale($rtl ? 'ar_JO' : 'en');
    // Phone numbers, order numbers, amounts and handles read left to right in both languages. On an
    // Arabic receipt they are wrapped in Unicode isolates (LRI ... PDI): the PDF footer ignores dir
    // attributes, and on an English receipt nothing needs wrapping.
    $ltr = fn (?string $text) => $rtl ? "\u{2066}".e($text)."\u{2069}" : e($text);
    $sentence = fn (string $key, string $name, string $html) => str_replace("%{$name}%", $html, e(__($key, [$name => "%{$name}%"])));
@endphp
<!DOCTYPE html>
<html lang="{{ $order->language }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('store.receipt.title') }} #{{ $order->number }}</title>
    <style>
        @page { footer: html_receiptFooter; }
        body { font-family: {{ $body }}; font-size: 10pt; line-height: 1.45; color: {{ $color('ink') }}; }
        @if ($rtl)
            {{-- Without these two, some digits and letter shapes could not be copied or read aloud from the PDF. --}}
            body, div, td, th, span, strong { font-feature-settings: "locl" 0, "calt" 0; }
        @endif
        table { border-collapse: collapse; }
        td, th { vertical-align: top; }
        .head td { vertical-align: bottom; padding: 0 0 4mm; }
        .head .end { text-align: {{ $end }}; }
        .wordmark { font-family: {{ $title }}; font-size: 22pt; letter-spacing: 3pt; color: {{ $color('wine') }}; }
        .title { font-family: {{ $title }}; font-size: 18pt; line-height: 1.2; }
        .number { font-family: {{ $semibold }}; font-size: 10pt; color: {{ $color('wine') }}; padding-top: 1mm; }
        .rule { border-top: 0.75pt solid {{ $color('line-strong') }}; }
        .details td { padding: 6mm 0 0; }
        .label { font-size: 9pt; color: {{ $color('ink-muted') }}; padding-bottom: 0.8mm; }
        .value { font-family: {{ $semibold }}; }
        .items { margin-top: 8mm; }
        .items th { font-family: {{ $semibold }}; font-size: 9pt; font-weight: normal; background-color: {{ $color('blush-100') }}; padding: 2.4mm 3mm; text-align: {{ $start }}; }
        .items td { padding: 3mm; border-bottom: 0.75pt solid {{ $color('line') }}; }
        .items th.middle, .items td.middle { text-align: center; }
        .items th.amount, .items td.amount, .totals td.amount { text-align: {{ $end }}; white-space: nowrap; }
        .items td.amount, .totals td.amount { font-feature-settings: "tnum" 1{!! $rtl ? ', "locl" 0, "calt" 0' : '' !!}; }
        .totals { margin-top: 3mm; }
        .totals td { padding: 1.4mm 3mm; }
        .totals .grand td { font-family: {{ $body }}; font-weight: bold; font-size: 12pt; border-top: 0.75pt solid {{ $color('line-strong') }}; padding-top: 2.6mm; }
        .note { margin-top: 8mm; padding: 3.5mm 4mm; background-color: {{ $color('blush-50') }}; border-radius: 2mm; }
        .footer { border-top: 0.75pt solid {{ $color('line') }}; padding-top: 3mm; font-size: 9pt; color: {{ $color('ink-muted') }}; text-align: center; line-height: 1.6; }
    </style>
</head>
<body>
    <htmlpagefooter name="receiptFooter">
        <div class="footer">
            @if ($phone)
                <div>{!! $sentence('store.receipt.questions', 'phone', $ltr($phone)) !!}</div>
            @endif
            @if ($instagram)
                <div>{!! $sentence('store.receipt.instagram', 'handle', $ltr($instagram)) !!}</div>
            @endif
            <div>{{ __('store.receipt.thanks', ['brand' => $brand]) }}</div>
        </div>
    </htmlpagefooter>

    {{-- 1. Logo on the start side; title and order number on the end side. --}}
    <table class="head" width="100%">
        <tr>
            <td width="45%">
                @if ($logo)
                    <img src="var:logo" style="height: 12mm;" alt="{{ $brand }}">
                @else
                    <span class="wordmark">{{ $brand }}</span>
                @endif
            </td>
            <td width="55%" class="end">
                <div class="title">{{ __('store.receipt.title') }}</div>
                <div class="number">{{ __('store.confirmation.order') }} {!! $ltr('#'.$order->number) !!}</div>
                <div class="label">{{ __('store.confirmation.keep_number') }}</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- 2. When, how it is paid, where it goes and who to call. --}}
    <table class="details" width="100%">
        <tr>
            <td width="50%">
                <div class="label">{{ __('store.receipt.date') }}</div>
                <div class="value">{{ $placed->translatedFormat($rtl ? 'j F Y، H:i' : 'j F Y, H:i') }}</div>
            </td>
            <td width="50%">
                <div class="label">{{ __('store.confirmation.payment') }}</div>
                <div class="value">{{ __('store.checkout.payment_method') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('store.confirmation.deliver_to') }}</div>
                <div class="value">{{ $order->customer_name }}</div>
                <div>{{ $order->deliveryAddress() }}</div>
            </td>
            <td>
                <div class="label">{{ __('store.confirmation.phone') }}</div>
                <div class="value">{!! $ltr($order->phone) !!}</div>
            </td>
        </tr>
    </table>

    {{-- 3. One row per piece, named as saved with the order. --}}
    <table class="items" width="100%">
        <thead>
            <tr>
                <th width="46%">{{ __('store.receipt.item') }}</th>
                <th width="12%" class="middle">{{ __('store.receipt.size') }}</th>
                <th width="10%" class="middle">{{ __('store.receipt.qty') }}</th>
                <th width="16%" class="amount">{{ __('store.receipt.price') }}</th>
                <th width="16%" class="amount">{{ __('store.receipt.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="middle">{!! $item->size ? $ltr($item->size) : '—' !!}</td>
                    <td class="middle">{{ $item->quantity }}</td>
                    <td class="amount">{!! $ltr(Money::jod($item->unit_price)) !!}</td>
                    <td class="amount">{!! $ltr($item->lineTotal()->format()) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 4. Subtotal, delivery and total on the end side. --}}
    <table class="totals" width="100%">
        <tr>
            <td width="52%"></td>
            <td width="48%" style="padding: 0;">
                <table width="100%">
                    <tr>
                        <td>{{ __('store.checkout.subtotal') }}</td>
                        <td class="amount">{!! $ltr(Money::jod($order->subtotal)) !!}</td>
                    </tr>
                    <tr>
                        <td>{{ __('store.checkout.delivery') }}</td>
                        <td class="amount">{!! $ltr(Money::fee($order->delivery_fee)) !!}</td>
                    </tr>
                    <tr class="grand">
                        <td>{{ __('store.checkout.total') }}</td>
                        <td class="amount">{!! $ltr(Money::jod($order->total)) !!}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- 5. Cash on delivery, in one line. --}}
    <div class="note">{!! $sentence('store.receipt.pay_on_delivery', 'total', '<strong>'.$ltr(Money::jod($order->total)).'</strong>') !!}</div>
</body>
</html>
