@use('App\Support\Money')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="same-origin">
    <title>Packing slip #{{ $order->number }} · JUDE</title>
    @vite(['resources/css/packing-slip.css', 'resources/js/packing-slip.js'])
</head>
<body class="slip">
    <main class="slip__page">
        <header class="slip__head">
            <span class="slip__brand">JUDE</span>
            <div class="slip__title">
                <h1>Packing slip</h1>
                <p>Order #{{ $order->number }} · Placed {{ $order->placed_at->timezone('Asia/Amman')->format('j M Y, H:i') }}</p>
            </div>
        </header>

        <section class="slip__block" aria-labelledby="deliver-to">
            <h2 id="deliver-to">Deliver to</h2>
            <dl class="slip__details">
                <div><dt>Customer</dt><dd>{{ $order->customer_name }}</dd></div>
                <div><dt>Phone</dt><dd dir="ltr">{{ $order->phone }}</dd></div>
                <div><dt>Address</dt><dd>{{ $order->deliveryAddress() }}</dd></div>
                @if (filled($order->note))
                    <div><dt>Note</dt><dd>{{ $order->note }}</dd></div>
                @endif
                <div><dt>Payment</dt><dd>Cash on delivery</dd></div>
            </dl>
        </section>

        <section class="slip__block" aria-labelledby="items">
            <h2 id="items">Items</h2>
            <table class="slip__items">
                <thead>
                    <tr><th scope="col">Item</th><th scope="col">Color</th><th scope="col">Size</th><th scope="col" class="slip__num">Qty</th><th scope="col" class="slip__num">Price</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->color_name }}</td>
                            <td>{{ $item->size ?? '–' }}</td>
                            <td class="slip__num">{{ $item->quantity }}</td>
                            <td class="slip__num">{{ Money::jod($item->lineTotal()->toDecimal()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th scope="row" colspan="4">Subtotal</th><td class="slip__num">{{ Money::jod($order->subtotal) }}</td></tr>
                    <tr><th scope="row" colspan="4">Delivery to {{ $order->city_name }}</th><td class="slip__num">{{ Money::jod($order->delivery_fee) }}</td></tr>
                    <tr class="slip__total"><th scope="row" colspan="4">Total</th><td class="slip__num">{{ Money::jod($order->total) }}</td></tr>
                </tfoot>
            </table>
        </section>

        <button type="button" class="slip__print" data-print>Print</button>
    </main>
</body>
</html>
